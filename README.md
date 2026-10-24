# BrainUp ERP

A multi-branch management system for education centers. It covers branches, study groups, attendance, assignments and submissions, and a gamification layer (XP, levels, streaks, badges and a coin shop) that keeps students motivated.

Built with **Laravel 13**, Blade, Tailwind CSS and Alpine.js. The interface is available in **Uzbek, English and Russian**.

## Features

- **Multi-branch tenancy.** Every branch sees only its own groups, attendance and assignments. Super admins can switch between branches or view all of them.
- **Four roles.** Super admin, branch admin, teacher and student, each with its own dashboard and permissions.
- **Study groups and courses.** Groups belong to a course and a branch, with one current teacher and a list of students.
- **Attendance.** Teachers mark attendance for today and a configurable number of previous days. Admins are not limited.
- **Assignments and submissions.** Teachers publish assignments, students submit work with file attachments, and teachers grade it. Files are served only through authorized routes.
- **Gamification.**
  - XP for attendance and submissions, with rules editable by the super admin.
  - Levels, daily streaks and animated badges.
  - Branch and group leaderboards.
  - Award logic is idempotent, so re-syncing never double-counts.
- **Coin shop.** Students spend earned coins on items. Branch admins fulfil or cancel orders, and cancelling refunds the coins and the stock.
- **Notifications.** In-app notifications for new assignments, graded submissions and shop orders.
- **Reports and search** for administrators.
- **Localization.** Uzbek (Latin) is the source language, with English and Russian translations. Users pick the language from the interface, and the choice is saved.

## Roles

| Role | Scope | Can do |
| --- | --- | --- |
| Super admin | All branches | Manage branches, gamification rules, global settings and everything below |
| Branch admin | One branch | Manage teachers, students, groups, assignments, shop items and orders |
| Teacher | One or more branches | Mark attendance, create assignments, grade submissions for their groups |
| Student | One branch | View groups and assignments, submit work, track XP and badges, use the shop |

## Tech stack

- PHP 8.3+ and Laravel 13
- MySQL 8.4 and Redis (cache and queues)
- Blade, Tailwind CSS and Alpine.js, built with Vite
- Laravel Sail (Docker) for local development
- PHPUnit for tests, Laravel Pint for code style

## Getting started

Everything runs through [Laravel Sail](https://laravel.com/docs/sail), so you only need Docker. Run PHP, Composer, Artisan and npm commands through `vendor/bin/sail`, not on the host: `.env` points at the `mysql` and `redis` containers.

```sh
git clone git@github.com:khvrshdvc/erp-brainup.git
cd erp-brainup

cp .env.example .env
# Install PHP dependencies without a local PHP, using a throwaway container
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html \
  laravelsail/php84-composer:latest composer install --ignore-platform-reqs

vendor/bin/sail up -d
vendor/bin/sail artisan key:generate
vendor/bin/sail artisan migrate --seed
vendor/bin/sail npm install
vendor/bin/sail npm run dev
```

The app is then available at <http://localhost>.

For scheduled jobs (for example, pruning old read notifications) run `vendor/bin/sail artisan schedule:work` locally. In production, run `schedule:run` every minute.

### Demo accounts

`migrate --seed` creates levels, XP rules, badges, branches, courses and, outside production, demo data. All demo passwords are `password`.

| Role | Email |
| --- | --- |
| Super admin | `admin@erp.test` |
| Branch admin | `filial.admin@brainup.uz` |
| Teacher | `robia@brainup.uz`, `sabina@brainup.uz`, `utkirbek@brainup.uz`, `olimjon@brainup.uz` |

Demo students have generated emails; pick one from the students list in the admin panel.

> Change or remove these accounts before deploying anywhere public.

### Other seeders

```sh
vendor/bin/sail artisan db:seed --class=ChirchiqSeeder    # real Chirchiq branch data (idempotent)
vendor/bin/sail artisan db:seed --class=LoadTestSeeder    # 2000 students and 3 months of data for performance checks
```

## Useful commands

```sh
vendor/bin/sail test                          # run the whole test suite
vendor/bin/sail test --filter=AttendanceTest  # run a single test class
vendor/bin/sail pint                          # fix code style

vendor/bin/sail artisan gamification:rebuild [--branch=ID] [--fresh]
                                              # recompute XP, levels, streaks and badges
```

Run `gamification:rebuild` after changing XP rules so existing students are recalculated.

Tests use a separate `testing` MySQL database, which Sail creates automatically.

## Project structure

```
app/
  Enums/               Role, statuses and other enums (with localized labels)
  Http/Controllers/    Admin/, Teacher/, Student/ and shared staff controllers
  Http/Middleware/     EnsureRole, EnsureUserIsActive, SetLocale
  Listeners/           Event listeners (XP awards, notifications)
  Models/              Eloquent models; Concerns/BelongsToBranch adds the branch scope
  Policies/            Authorization rules
  Services/            CurrentBranch, GamificationService, LeaderboardService, ShopService, AttachmentService
database/seeders/      Reference data, demo data and load-test data
lang/                  en.json, ru.json and PHP files for validation, auth and dates
resources/views/       Blade views and shared components
tests/                 Feature and unit tests
```

## How it works

### Branch scoping

`CurrentBranch` resolves the active branch for the signed-in user from the session, checked against the branches that user may access. Models that use the `BelongsToBranch` trait (`StudyGroup`, `Assignment`, `Attendance`, `Submission`) are filtered automatically and get their `branch_id` filled on create. Code that must cross branches, such as gamification and console commands, opts out with `withoutGlobalScopes()`.

### Gamification

Controllers fire domain events (`AttendanceMarked`, `SubmissionUpdated`, `AssignmentPublished`), and listeners turn them into XP and notifications. Each award is stored as an `XpTransaction` keyed by `(user, reason, source)`, so repeating a sync overwrites the earlier award instead of adding another. After each sync, the student's XP, level, streak and badges are recalculated.

## Localization

Uzbek text is the translation key: write `__('Uzbek text')` in views and code, then add the English and Russian versions to `lang/en.json` and `lang/ru.json`. `TranslationCoverageTest` and `LocalizedPagesTest` fail when a translation is missing or Uzbek text leaks into another language.

## Contributing

1. Create a branch from `main`.
2. Make your change and add or update tests.
3. Run `vendor/bin/sail pint` and `vendor/bin/sail test`.
4. Open a pull request.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT). This project has no license file yet; add one before accepting outside contributions.
