# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

BrainUp ERP is a multi-branch education-center management app (branches, study groups, attendance, assignments/submissions, gamification). It runs on Laravel 13 (PHP 8.3+) with Breeze-style Blade views, Tailwind and Alpine through Vite. It runs in Docker through **Laravel Sail** (`compose.yaml`: app on PHP 8.5, MySQL 8.4, Redis, Mailpit). The interface speaks three languages (Uzbek, English, Russian; see *Languages* below). **Uzbek (Latin script) is the source language**: write every new user-facing string in Uzbek and translate it.

## Commands

Run every PHP, Composer, Artisan and npm command through Sail, never on the host. `.env` points at the `mysql` and `redis` containers, so host-side `php artisan` can't reach the database.

```sh
vendor/bin/sail up -d          # start containers (stop with: vendor/bin/sail down)
vendor/bin/sail composer install
vendor/bin/sail npm run dev    # Vite dev server (or: sail npm run build)
vendor/bin/sail artisan schedule:work   # runs the daily notifications:prune (read notifications older than 60 days) and shop:expire-orders (pending shop orders older than 3 days are cancelled and refunded); in production run schedule:run every minute

vendor/bin/sail test                                         # whole suite
vendor/bin/sail test --filter=AttendanceTest                 # single test class
vendor/bin/sail test --filter=test_teacher_can_mark_attendance   # single method
vendor/bin/sail test tests/Feature/GamificationTest.php

vendor/bin/sail pint           # code style (Laravel Pint)

vendor/bin/sail artisan migrate:fresh --seed   # seeds levels/XP rules/badges, branches, courses, and DemoSeeder outside production
vendor/bin/sail artisan gamification:rebuild [--branch=ID] [--fresh]   # recompute XP, levels, streaks and badges from attendance and submissions
vendor/bin/sail artisan db:seed --class=ChirchiqSeeder   # real Chirchiq branch: 4 teachers, 13 groups, 104 Uzbek-named students, 3 assignments per group; idempotent, not part of DatabaseSeeder
vendor/bin/sail artisan db:seed --class=LoadTestSeeder   # 2000 students and 3 months of data for local performance checks; not part of DatabaseSeeder
vendor/bin/sail artisan tinker
```

Tests use PHPUnit (not Pest) with `RefreshDatabase`. They run against the separate MySQL `testing` database, which Sail's MySQL init script creates (`DB_DATABASE=testing` in `phpunit.xml`). `QUEUE_CONNECTION=sync` in tests, so listeners run inline. Demo users from `DemoSeeder` all use the password `password`.

## Architecture

### Roles and routing
`App\Enums\Role` has four roles: `super_admin`, `branch_admin`, `teacher` and `student`, stored in `users.role`. Routes in `routes/web.php` are grouped by the `role:` middleware alias (`EnsureRole`, registered in `bootstrap/app.php`), and controllers live in matching namespaces: `Admin\`, `Teacher\`, `Student\`, plus shared staff controllers at the root (`GroupController`, `AttendanceController`). `/dashboard` (`DashboardController`) redirects to the dashboard for the user's role. `EnsureUserIsActive` is appended to the whole `web` group, so a deactivated user is kicked out on every request.

Super-admin-only routes (branches, gamification) share the `admin.` prefix and name with branch-admin routes but sit in a separate middleware group.

### Branch multi-tenancy (the key cross-cutting concern)
- `App\Services\CurrentBranch` (a singleton) resolves the active branch for the logged-in user. It uses `session('branch_id')` (set by `BranchSwitchController` / `x-branch-switcher`) and checks it against `User::availableBranchIds()`:
  - super admin: all branches. With no branch selected, `id()` returns `null`, which means **no filtering**.
  - teacher: branches attached through the `branch_user` pivot.
  - branch admin and student: their single `users.branch_id`.
- Models using the `Models\Concerns\BelongsToBranch` trait (`StudyGroup`, `Assignment`, `Attendance`, `Submission`) get the global `BranchScope` and have `branch_id` filled automatically on create. If no branch can be resolved, creation throws `RuntimeException('Filial tanlanmagan')`.
- Code that has to cross branches (gamification, console commands, cross-branch lookups) calls `withoutGlobalScopes()`. Keep this in mind when queries unexpectedly return nothing, for example in tests or CLI, where there is no authenticated user.
- `CurrentBranch` caches its result per user id. Call `forget()` after changing the session branch inside one request or test.
- `User` is **not** branch-scoped. Filter users explicitly with `scopeInBranch`, `students()`, `teachers()` and `active()`.
- `Setting::get($key)` looks up the current branch's value and falls back to the global row (`branch_id` null). Results are cached per (branch, key) for an hour and cleared by the model's `saved`/`deleted` hooks, so write settings with `Setting::put()` or Eloquent, never raw queries.
- `attendance_edit_days` (global setting, default 3, editable by the super admin on the settings page, read with `Setting::attendanceEditDays()`): teachers may mark or edit attendance only for today and the previous N days. `StoreAttendanceRequest` enforces it with an Uzbek message; super admin and branch admin are unlimited.
- **Teacher access follows the group's current teacher**, not `assignments.teacher_id`: `Assignment::isTaughtBy()` backs `AssignmentPolicy::manage`, `SubmissionPolicy::grade` and `AttachmentPolicy`, and the teacher assignment and submission lists filter with `whereHas('studyGroup', teacher_id = me)`. Changing a group's teacher in `Admin\GroupController::update` also moves the group's non-closed assignments to the new teacher; closed ones keep their author but are still reachable by the new teacher through the group.
- Branch admins may change a teacher's email and password only when all of the teacher's branches are among theirs (`UserPolicy::manageTeacherCredentials`).

### Events → listeners → gamification
Controllers fire domain events, and listeners are auto-discovered from `app/Listeners`:
- `AttendanceMarked` → `AwardAttendanceXp` (runs inline, deliberately not queued: the production host may have no worker; `AttendanceController` fires the event after commit)
- `SubmissionUpdated` → `SyncSubmissionXp`, `SendSubmissionNotification`
- `AssignmentPublished` → `NotifyStudentsOfAssignment`

`GamificationService` is **idempotent**. Each award is an `XpTransaction` upserted on `(user_id, reason, source_id)`, so re-syncing the same attendance or submission overwrites the earlier award instead of adding a second one. After each sync it recalculates the user's cached `xp`, `level_id`, `streak` and badges. Point values come from the `XpRule` table (`XpRule::pointsFor('attendance_present')` and so on, seeded by `GamificationSeeder`, editable by the super admin). `pointsFor()` is cached forever per code and forgotten by the model's `saved`/`deleted` hooks, so always change rules through Eloquent, not raw queries. If you change the rules, run `gamification:rebuild` to recompute. `LeaderboardService` ranks students within a scope.

### Other conventions
- Enums in `app/Enums` use the `HasValues` concern and provide `label()` methods with Uzbek text. Use them for casts and validation.
- File uploads go through `AttachmentService` and the polymorphic `Attachment` model (`HasAttachments` trait). They are downloaded through the authorized `attachments.download` route, never through public URLs.
- Use `App\Support\UzDate` to format dates and durations; it follows the interface language (`lang/{uz,en,ru}/dates.php`).

### Languages (uz / en / ru)
- The Uzbek text is the translation **key**: views and PHP code call `__('Uzbek text')` (placeholders as `:name`), and `lang/en.json` / `lang/ru.json` hold the translations. A missing key shows the Uzbek text. Only validation, auth, passwords, pagination and dates use PHP files (`lang/{uz,en,ru}/*.php`).
- When you add or change a user-facing string (Blade text, `placeholder`/`title`/`aria-label`, flash messages, `messages()`/`attributes()` of form requests, enum `label()`s, notifications), wrap it in `__()` and add it to **both** `lang/en.json` and `lang/ru.json`. `TranslationCoverageTest` fails when a literal `__('…')` key lacks a translation, `LocalizedPagesTest` opens the pages of every role in English and Russian and fails on leftover Uzbek spelling, and `LocalizationTest` covers switching.
- Do not build sentences from fragments (`__('Jami') . $n . __('ta')`): word order differs between languages. Use one key with placeholders (`__('Jami :count ta guruh', ['count' => $n])`). Russian/English texts avoid count-dependent nouns ("Groups: :count") instead of plural forms.
- Text stored in the database that comes from seeders (level names, badge and XP rule titles, seeded course names) is translated when shown, by passing it through `__()`. Notifications store a key plus parameters (`body_key`, `body_params`) and are translated when read.
- The language is chosen by `App\Http\Middleware\SetLocale`: the signed-in user's `users.locale`, else the `locale` cookie, else `app.locale` (`uz`). The language button posts to `locale.switch` and saves both. Supported locales live in `config/locales.php`. Scripts get their texts from `window.ERP_I18N` (`layouts/partials/i18n-js.blade.php`).
- Notifications use the database channel (`notifications` table) and are shown at `/notifications`.
- Shared Blade components live in `resources/views/components` (`stat-card`, `badge`, `pager`, `file-chip`, `branch-switcher`). The authenticated layout is `layouts/app.blade.php` with `partials/sidebar` and `partials/topbar`.

## Laravel Boost

`AGENTS.md` contains Laravel Boost bootstrap instructions. Boost isn't installed yet (it's not in `composer.json`). If it gets installed (`vendor/bin/sail composer require laravel/boost --dev && vendor/bin/sail artisan boost:install`), merge its generated guidelines into this file rather than overwriting it.
