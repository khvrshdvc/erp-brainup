<?php

use App\Http\Controllers\Admin\AssignmentController as AdminAssignmentController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\CredentialController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\GamificationController;
use App\Http\Controllers\Admin\GroupController as AdminGroupController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ShopItemController;
use App\Http\Controllers\Admin\ShopOrderController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\TeacherController as AdminTeacherController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BranchSwitchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ShopImageController;
use App\Http\Controllers\Student\AchievementController;
use App\Http\Controllers\Student\AssignmentController as StudentAssignmentController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\GroupController as StudentGroupController;
use App\Http\Controllers\Student\ShopController as StudentShopController;
use App\Http\Controllers\Student\SubmissionController as StudentSubmissionController;
use App\Http\Controllers\SubmissionListController;
use App\Http\Controllers\Teacher\AssignmentController as TeacherAssignmentController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Teacher\SubmissionController as TeacherSubmissionController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::post('/locale', LocaleController::class)->name('locale.switch');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/branch/switch', BranchSwitchController::class)->name('branch.switch');
    Route::get('/attachments/{attachment}', [AttachmentController::class, 'download'])->name('attachments.download');
    Route::get('/shop/items/{shopItem}/image', ShopImageController::class)->name('shop.items.image');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');

    Route::middleware('role:super_admin,branch_admin,teacher')->group(function () {
        Route::get('/search', SearchController::class)->name('search');
        Route::get('/submissions', SubmissionListController::class)->name('submissions.index');
        Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::get('/groups/{studyGroup}/attendance', [AttendanceController::class, 'create'])->name('attendance.create');
        Route::post('/groups/{studyGroup}/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
    });

    Route::middleware('role:super_admin,branch_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::resource('groups', AdminGroupController::class)
            ->except(['destroy'])
            ->parameters(['groups' => 'studyGroup']);
        Route::get('/groups/{studyGroup}/candidates', [AdminGroupController::class, 'candidates'])->name('groups.candidates');
        Route::patch('/groups/{studyGroup}/status', [AdminGroupController::class, 'toggleStatus'])->name('groups.status');
        Route::post('/groups/{studyGroup}/students', [AdminGroupController::class, 'addStudent'])->name('groups.students.add');
        Route::delete('/groups/{studyGroup}/students/{student}', [AdminGroupController::class, 'removeStudent'])->name('groups.students.remove');

        Route::post('/credentials', CredentialController::class)->name('credentials.generate');

        Route::resource('students', AdminStudentController::class)->except(['destroy']);
        Route::patch('/students/{student}/status', [AdminStudentController::class, 'toggleStatus'])->name('students.status');
        Route::post('/students/{student}/password', [AdminStudentController::class, 'resetPassword'])->name('students.password');

        Route::resource('teachers', AdminTeacherController::class)->except(['show', 'destroy']);
        Route::patch('/teachers/{teacher}/status', [AdminTeacherController::class, 'toggleStatus'])->name('teachers.status');
        Route::post('/teachers/{teacher}/password', [AdminTeacherController::class, 'resetPassword'])->name('teachers.password');

        Route::get('/assignments', [AdminAssignmentController::class, 'index'])->name('assignments.index');
        Route::get('/assignments/{assignment}', [AdminAssignmentController::class, 'show'])->name('assignments.show');

        Route::get('/shop/orders', [ShopOrderController::class, 'index'])->name('shop.orders.index');
        Route::patch('/shop/orders/{shopOrder}/deliver', [ShopOrderController::class, 'deliver'])->name('shop.orders.deliver');
        Route::patch('/shop/orders/{shopOrder}/cancel', [ShopOrderController::class, 'cancel'])->name('shop.orders.cancel');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');

        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings/center', [SettingsController::class, 'updateCenter'])->name('settings.center');
        Route::put('/settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
        Route::put('/settings/staff/{staff}', [SettingsController::class, 'updateStaff'])->name('settings.staff');
        Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    });

    Route::middleware('role:super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('branches', BranchController::class)->except(['show', 'destroy']);
        Route::patch('/branches/{branch}/status', [BranchController::class, 'toggleStatus'])->name('branches.status');

        Route::resource('shop-items', ShopItemController::class)
            ->except(['show', 'destroy'])
            ->parameters(['shop-items' => 'shopItem']);
        Route::patch('/shop-items/{shopItem}/status', [ShopItemController::class, 'toggleStatus'])->name('shop-items.status');

        Route::get('/gamification', [GamificationController::class, 'index'])->name('gamification.index');
        Route::put('/gamification', [GamificationController::class, 'update'])->name('gamification.update');
    });

    Route::middleware('role:teacher')->prefix('teacher')->name('teacher.')->group(function () {
        Route::get('/', TeacherDashboardController::class)->name('dashboard');

        Route::resource('assignments', TeacherAssignmentController::class)->except(['destroy']);
        Route::post('/assignments/{assignment}/publish', [TeacherAssignmentController::class, 'publish'])->name('assignments.publish');
        Route::post('/assignments/{assignment}/close', [TeacherAssignmentController::class, 'close'])->name('assignments.close');
        Route::post('/assignments/{assignment}/reopen', [TeacherAssignmentController::class, 'reopen'])->name('assignments.reopen');
        Route::put('/submissions/{submission}', [TeacherSubmissionController::class, 'update'])->name('submissions.update');
    });

    Route::middleware('role:student')->prefix('student')->name('student.')->group(function () {
        Route::get('/', StudentDashboardController::class)->name('dashboard');
        Route::get('/achievements', AchievementController::class)->name('achievements');
        Route::get('/group', [StudentGroupController::class, 'index'])->name('groups.index');

        Route::get('/assignments', [StudentAssignmentController::class, 'index'])->name('assignments.index');
        Route::get('/assignments/{assignment}', [StudentAssignmentController::class, 'show'])->name('assignments.show');
        Route::get('/assignments/{assignment}/submit', [StudentAssignmentController::class, 'create'])->name('assignments.submit');
        Route::post('/assignments/{assignment}/submit', [StudentAssignmentController::class, 'store'])->name('assignments.submit.store');
        Route::get('/submissions', [StudentSubmissionController::class, 'index'])->name('submissions.index');

        Route::get('/shop', [StudentShopController::class, 'index'])->name('shop.index');
        Route::get('/shop/orders', [StudentShopController::class, 'orders'])->name('shop.orders');
        Route::post('/shop/{shopItem}/buy', [StudentShopController::class, 'buy'])->name('shop.buy');
        Route::delete('/shop/orders/{shopOrder}', [StudentShopController::class, 'cancel'])->name('shop.orders.cancel');
    });
});

require __DIR__.'/auth.php';
