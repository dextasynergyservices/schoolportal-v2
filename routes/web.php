<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AiCreditController;
use App\Http\Controllers\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BulkResultController;
use App\Http\Controllers\Admin\ClassController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\GameController as AdminGameController;
use App\Http\Controllers\Admin\HelpController;
use App\Http\Controllers\Admin\InsightsController as AdminInsightsController;
use App\Http\Controllers\Admin\LevelController;
use App\Http\Controllers\Admin\NoticeController;
use App\Http\Controllers\Admin\ParentController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\QuizController as AdminQuizController;
use App\Http\Controllers\Admin\ResultController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\StudentImportController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\AnnouncementReadController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Parent\ChildAssignmentController;
use App\Http\Controllers\Parent\ChildController;
use App\Http\Controllers\Parent\ChildGameStatsController;
use App\Http\Controllers\Parent\ChildQuizResultController;
use App\Http\Controllers\Parent\ChildResultController;
use App\Http\Controllers\Parent\DashboardController as ParentDashboardController;
use App\Http\Controllers\Parent\NoticeController as ParentNoticeController;
use App\Http\Controllers\Parent\OverviewController as ParentOverviewController;
use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\Student\AssignmentController as StudentAssignmentController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\GameController as StudentGameController;
use App\Http\Controllers\Student\NoticeController as StudentNoticeController;
use App\Http\Controllers\Student\ProfileController as StudentProfileController;
use App\Http\Controllers\Student\QuizController as StudentQuizController;
use App\Http\Controllers\Student\ResultController as StudentResultController;
use App\Http\Controllers\SuperAdmin\AnnouncementController as SuperAdminAnnouncementController;
use App\Http\Controllers\SuperAdmin\CreditController as SuperAdminCreditController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\EmailController as SuperAdminEmailController;
use App\Http\Controllers\SuperAdmin\ParentController as SuperAdminParentController;
use App\Http\Controllers\SuperAdmin\SchoolController as SuperAdminSchoolController;
use App\Http\Controllers\SuperAdmin\StudentController as SuperAdminStudentController;
use App\Http\Controllers\SuperAdmin\TeacherController as SuperAdminTeacherController;
use App\Http\Controllers\Teacher\AssignmentController as TeacherAssignmentController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Teacher\GameController as TeacherGameController;
use App\Http\Controllers\Teacher\InsightsController as TeacherInsightsController;
use App\Http\Controllers\Teacher\NoticeController as TeacherNoticeController;
use App\Http\Controllers\Teacher\QuizController as TeacherQuizController;
use App\Http\Controllers\Teacher\ResultController as TeacherResultController;
use App\Http\Controllers\Teacher\StudentController as TeacherStudentController;
use App\Http\Controllers\Teacher\SubmissionController as TeacherSubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // If this is a school's custom domain, redirect to login
    $host = request()->getHost();
    $platformHost = parse_url(config('app.url', ''), PHP_URL_HOST);

    if ($host !== $platformHost && $host !== 'localhost' && $host !== '127.0.0.1' && ! str_ends_with($host, '.test')) {
        return redirect('/portal/login');
    }

    return view('landing');
})->name('home');

// Paystack webhook (no auth, no CSRF — verified by HMAC signature)
Route::post('webhooks/paystack', PaystackWebhookController::class)->name('webhooks.paystack');

// All portal routes live under /portal
Route::prefix('portal')->group(function () {

    // Force password change (accessible when must_change_password = true)
    Route::middleware('auth')->group(function () {
        Route::get('password/change', [ChangePasswordController::class, 'show'])->name('password.change');
        Route::post('password/change', [ChangePasswordController::class, 'update'])->name('password.change.update')->middleware('throttle:password-change');
    });

    // Authenticated routes — all require verified email
    Route::middleware(['auth', 'verified'])->group(function () {
        // Default dashboard redirect based on role
        Route::get('dashboard', function () {
            $user = auth()->user();

            return match ($user->role) {
                'super_admin' => redirect('/portal/super-admin/dashboard'),
                'school_admin' => redirect('/portal/admin/dashboard'),
                'teacher' => redirect('/portal/teacher/dashboard'),
                'student' => redirect('/portal/student/dashboard'),
                'parent' => redirect('/portal/parent/dashboard'),
                default => redirect('/'),
            };
        })->name('dashboard');

        // ── Notifications (all roles) ──
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');
            Route::post('mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
            Route::delete('clear-all', [NotificationController::class, 'destroyAll'])->name('notifications.clear-all');
            Route::post('{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
            Route::delete('{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
        });

        // Dismiss school announcement (all roles)
        Route::post('announcement/{announcement}/dismiss', [AnnouncementReadController::class, 'dismissSchool'])->name('announcement.dismiss');

        // ── School Admin Routes ──
        Route::prefix('admin')->middleware('role:school_admin')->group(function () {
            Route::get('dashboard', AdminDashboardController::class)->name('admin.dashboard');
            Route::get('analytics', AdminAnalyticsController::class)->name('admin.analytics');
            Route::get('insights', AdminInsightsController::class)->name('admin.insights');

            // Students
            Route::resource('students', StudentController::class)->names('admin.students');
            Route::post('students/{student}/reset-password', [StudentController::class, 'resetPassword'])->name('admin.students.reset-password');
            Route::post('students/{student}/deactivate', [StudentController::class, 'deactivate'])->name('admin.students.deactivate');
            Route::post('students/{student}/activate', [StudentController::class, 'activate'])->name('admin.students.activate');
            Route::get('students-import', [StudentImportController::class, 'create'])->name('admin.students.import');
            Route::post('students-import/preview', [StudentImportController::class, 'preview'])->name('admin.students.import.preview')->middleware('throttle:file-upload');
            Route::post('students-import', [StudentImportController::class, 'store'])->name('admin.students.import.store')->middleware('throttle:sensitive-action');
            Route::get('students-import/template', [StudentImportController::class, 'template'])->name('admin.students.import.template');

            // Teachers
            Route::resource('teachers', TeacherController::class)->names('admin.teachers')->except('show');
            Route::post('teachers/{teacher}/reset-password', [TeacherController::class, 'resetPassword'])->name('admin.teachers.reset-password');
            Route::post('teachers/{teacher}/deactivate', [TeacherController::class, 'deactivate'])->name('admin.teachers.deactivate');
            Route::post('teachers/{teacher}/activate', [TeacherController::class, 'activate'])->name('admin.teachers.activate');

            // Parents
            Route::resource('parents', ParentController::class)->names('admin.parents')->except('show');
            Route::post('parents/{parent}/reset-password', [ParentController::class, 'resetPassword'])->name('admin.parents.reset-password');
            Route::post('parents/{parent}/deactivate', [ParentController::class, 'deactivate'])->name('admin.parents.deactivate');
            Route::post('parents/{parent}/activate', [ParentController::class, 'activate'])->name('admin.parents.activate');

            // Classes
            Route::resource('classes', ClassController::class)->names('admin.classes')->except('show');

            // School Levels
            Route::resource('levels', LevelController::class)->names('admin.levels')->except('show');

            // Academic Sessions & Terms
            Route::resource('sessions', SessionController::class)->names('admin.sessions')->except('show');
            Route::post('sessions/{session}/activate', [SessionController::class, 'activate'])->name('admin.sessions.activate');
            Route::post('terms/{term}/activate', [SessionController::class, 'activateTerm'])->name('admin.terms.activate');
            Route::put('terms/{term}', [SessionController::class, 'updateTerm'])->name('admin.terms.update');

            // Results
            Route::resource('results', ResultController::class)->names('admin.results')->except('edit', 'update');
            Route::get('results-bulk', [BulkResultController::class, 'create'])->name('admin.results.bulk');
            Route::post('results-bulk/preview', [BulkResultController::class, 'preview'])->name('admin.results.bulk.preview')->middleware('throttle:file-upload');
            Route::post('results-bulk', [BulkResultController::class, 'store'])->name('admin.results.bulk.store')->middleware('throttle:file-upload');

            // Assignments
            Route::resource('assignments', AssignmentController::class)->names('admin.assignments')->except('show');

            // Notices
            Route::resource('notices', NoticeController::class)->names('admin.notices')->except('show');
            Route::post('notices/{notice}/unpublish', [NoticeController::class, 'unpublish'])->name('admin.notices.unpublish');
            Route::post('notices/{notice}/publish', [NoticeController::class, 'publish'])->name('admin.notices.publish');

            // Approvals
            Route::get('approvals', [ApprovalController::class, 'index'])->name('admin.approvals.index');
            Route::post('approvals/{action}/approve', [ApprovalController::class, 'approve'])->name('admin.approvals.approve');
            Route::post('approvals/{action}/reject', [ApprovalController::class, 'reject'])->name('admin.approvals.reject');

            // Promotions
            Route::get('promotions', [PromotionController::class, 'index'])->name('admin.promotions.index');
            Route::post('promotions/preview', [PromotionController::class, 'preview'])->name('admin.promotions.preview');
            Route::post('promotions', [PromotionController::class, 'store'])->name('admin.promotions.store')->middleware('throttle:sensitive-action');

            // Audit Logs
            Route::get('audit-logs', [AuditLogController::class, 'index'])->name('admin.audit-logs.index');

            // Quizzes
            Route::get('quizzes', [AdminQuizController::class, 'index'])->name('admin.quizzes.index');
            Route::get('quizzes/{quiz}', [AdminQuizController::class, 'show'])->name('admin.quizzes.show');
            Route::post('quizzes/{quiz}/publish', [AdminQuizController::class, 'publish'])->name('admin.quizzes.publish');
            Route::post('quizzes/{quiz}/unpublish', [AdminQuizController::class, 'unpublish'])->name('admin.quizzes.unpublish');
            Route::get('quizzes/{quiz}/results', [AdminQuizController::class, 'results'])->name('admin.quizzes.results');
            Route::get('quizzes/{quiz}/results/export', [AdminQuizController::class, 'exportCsv'])->name('admin.quizzes.results.export');
            Route::delete('quizzes/{quiz}', [AdminQuizController::class, 'destroy'])->name('admin.quizzes.destroy');

            // Games
            Route::get('games', [AdminGameController::class, 'index'])->name('admin.games.index');
            Route::get('games/{game}', [AdminGameController::class, 'show'])->name('admin.games.show');
            Route::post('games/{game}/publish', [AdminGameController::class, 'publish'])->name('admin.games.publish');
            Route::post('games/{game}/unpublish', [AdminGameController::class, 'unpublish'])->name('admin.games.unpublish');
            Route::get('games/{game}/stats', [AdminGameController::class, 'stats'])->name('admin.games.stats');
            Route::delete('games/{game}', [AdminGameController::class, 'destroy'])->name('admin.games.destroy');

            // AI Credits
            Route::get('credits', [AiCreditController::class, 'index'])->name('admin.credits.index');
            Route::get('credits/purchase', [AiCreditController::class, 'purchase'])->name('admin.credits.purchase');
            Route::post('credits/purchase', [AiCreditController::class, 'processPurchase'])->name('admin.credits.purchase.process')->middleware('throttle:credit-purchase');
            Route::get('credits/purchase/callback', [AiCreditController::class, 'purchaseCallback'])->name('admin.credits.purchase.callback');
            Route::post('credits/allocate', [AiCreditController::class, 'allocate'])->name('admin.credits.allocate');

            // School Settings
            Route::get('settings', [SettingsController::class, 'index'])->name('admin.settings.index');
            Route::put('settings', [SettingsController::class, 'update'])->name('admin.settings.update');
            Route::put('settings/branding', [SettingsController::class, 'updateBranding'])->name('admin.settings.branding');
            Route::post('settings/upload-logo', [SettingsController::class, 'uploadLogo'])->name('admin.settings.upload-logo');
            Route::delete('settings/remove-logo', [SettingsController::class, 'removeLogo'])->name('admin.settings.remove-logo');
            Route::put('settings/portal', [SettingsController::class, 'updatePortal'])->name('admin.settings.portal');

            // Help Guide
            Route::get('help', HelpController::class)->name('admin.help');

            // Announcements
            Route::get('announcements', [AdminAnnouncementController::class, 'index'])->name('admin.announcements.index');
            Route::get('announcements/create', [AdminAnnouncementController::class, 'create'])->name('admin.announcements.create');
            Route::post('announcements', [AdminAnnouncementController::class, 'store'])->name('admin.announcements.store');
            Route::get('announcements/{announcement}/edit', [AdminAnnouncementController::class, 'edit'])->name('admin.announcements.edit');
            Route::put('announcements/{announcement}', [AdminAnnouncementController::class, 'update'])->name('admin.announcements.update');
            Route::post('announcements/{announcement}/deactivate', [AdminAnnouncementController::class, 'deactivate'])->name('admin.announcements.deactivate');
            Route::post('announcements/{announcement}/activate', [AdminAnnouncementController::class, 'activate'])->name('admin.announcements.activate');
            Route::delete('announcements/{announcement}', [AdminAnnouncementController::class, 'destroy'])->name('admin.announcements.destroy');

            // Mark platform announcement as read
            Route::post('platform-announcement/{announcement}/read', [AnnouncementReadController::class, 'markPlatformRead'])->name('admin.platform-announcement.read');
        });

        // ── Teacher Routes ──
        Route::prefix('teacher')->middleware('role:teacher')->group(function () {
            Route::get('dashboard', TeacherDashboardController::class)->name('teacher.dashboard');

            // Students (read-only)
            Route::get('students', [TeacherStudentController::class, 'index'])->name('teacher.students.index');

            // Results
            Route::get('results', [TeacherResultController::class, 'index'])->name('teacher.results.index');
            Route::get('results/create', [TeacherResultController::class, 'create'])->name('teacher.results.create');
            Route::post('results', [TeacherResultController::class, 'store'])->name('teacher.results.store');
            Route::get('results/{result}/edit', [TeacherResultController::class, 'edit'])->name('teacher.results.edit');
            Route::put('results/{result}', [TeacherResultController::class, 'update'])->name('teacher.results.update');

            // Assignments
            Route::get('assignments', [TeacherAssignmentController::class, 'index'])->name('teacher.assignments.index');
            Route::get('assignments/create', [TeacherAssignmentController::class, 'create'])->name('teacher.assignments.create');
            Route::post('assignments', [TeacherAssignmentController::class, 'store'])->name('teacher.assignments.store');
            Route::get('assignments/{assignment}/edit', [TeacherAssignmentController::class, 'edit'])->name('teacher.assignments.edit');
            Route::put('assignments/{assignment}', [TeacherAssignmentController::class, 'update'])->name('teacher.assignments.update');

            // Notices
            Route::get('notices', [TeacherNoticeController::class, 'index'])->name('teacher.notices.index');
            Route::get('notices/create', [TeacherNoticeController::class, 'create'])->name('teacher.notices.create');
            Route::post('notices', [TeacherNoticeController::class, 'store'])->name('teacher.notices.store');
            Route::get('notices/{notice}/edit', [TeacherNoticeController::class, 'edit'])->name('teacher.notices.edit');
            Route::put('notices/{notice}', [TeacherNoticeController::class, 'update'])->name('teacher.notices.update');
            Route::post('notices/{notice}/unpublish', [TeacherNoticeController::class, 'unpublish'])->name('teacher.notices.unpublish');
            Route::post('notices/{notice}/publish', [TeacherNoticeController::class, 'publish'])->name('teacher.notices.publish');
            Route::delete('notices/{notice}', [TeacherNoticeController::class, 'destroy'])->name('teacher.notices.destroy');

            // Quizzes
            Route::get('quizzes', [TeacherQuizController::class, 'index'])->name('teacher.quizzes.index');
            Route::get('quizzes/create', [TeacherQuizController::class, 'create'])->name('teacher.quizzes.create');
            Route::post('quizzes/generate', [TeacherQuizController::class, 'generate'])->name('teacher.quizzes.generate')->middleware('throttle:ai-generation');
            Route::post('quizzes', [TeacherQuizController::class, 'store'])->name('teacher.quizzes.store');
            Route::get('quizzes/{quiz}', [TeacherQuizController::class, 'show'])->name('teacher.quizzes.show');
            Route::get('quizzes/{quiz}/edit', [TeacherQuizController::class, 'edit'])->name('teacher.quizzes.edit');
            Route::put('quizzes/{quiz}', [TeacherQuizController::class, 'update'])->name('teacher.quizzes.update');
            Route::get('quizzes/{quiz}/results', [TeacherQuizController::class, 'results'])->name('teacher.quizzes.results');
            Route::get('quizzes/{quiz}/results/export', [TeacherQuizController::class, 'exportCsv'])->name('teacher.quizzes.results.export');
            Route::delete('quizzes/{quiz}', [TeacherQuizController::class, 'destroy'])->name('teacher.quizzes.destroy');

            // Games
            Route::get('games', [TeacherGameController::class, 'index'])->name('teacher.games.index');
            Route::get('games/create', [TeacherGameController::class, 'create'])->name('teacher.games.create');
            Route::post('games/generate', [TeacherGameController::class, 'generate'])->name('teacher.games.generate')->middleware('throttle:ai-generation');
            Route::post('games', [TeacherGameController::class, 'store'])->name('teacher.games.store');
            Route::get('games/{game}', [TeacherGameController::class, 'show'])->name('teacher.games.show');
            Route::get('games/{game}/edit', [TeacherGameController::class, 'edit'])->name('teacher.games.edit');
            Route::put('games/{game}', [TeacherGameController::class, 'update'])->name('teacher.games.update');
            Route::get('games/{game}/stats', [TeacherGameController::class, 'stats'])->name('teacher.games.stats');
            Route::delete('games/{game}', [TeacherGameController::class, 'destroy'])->name('teacher.games.destroy');

            // Submissions (approval tracking)
            Route::get('submissions', [TeacherSubmissionController::class, 'index'])->name('teacher.submissions.index');

            // Insights
            Route::get('insights', TeacherInsightsController::class)->name('teacher.insights');
        });

        // ── Student Routes ──
        Route::prefix('student')->middleware('role:student')->group(function () {
            Route::get('dashboard', StudentDashboardController::class)->name('student.dashboard');
            Route::get('profile', StudentProfileController::class)->name('student.profile');

            // Results
            Route::get('results', [StudentResultController::class, 'index'])->name('student.results.index');
            Route::get('results/{result}', [StudentResultController::class, 'show'])->name('student.results.show');

            // Assignments
            Route::get('assignments', [StudentAssignmentController::class, 'index'])->name('student.assignments.index');

            // Quizzes
            Route::get('quizzes', [StudentQuizController::class, 'index'])->name('student.quizzes.index');
            Route::post('quizzes/{quiz}/start', [StudentQuizController::class, 'start'])->name('student.quizzes.start');
            Route::get('quizzes/attempt/{attempt}', [StudentQuizController::class, 'take'])->name('student.quizzes.take');
            Route::post('quizzes/attempt/{attempt}/answer', [StudentQuizController::class, 'saveAnswer'])->name('student.quizzes.save-answer');
            Route::post('quizzes/attempt/{attempt}/submit', [StudentQuizController::class, 'submit'])->name('student.quizzes.submit');
            Route::get('quizzes/attempt/{attempt}/results', [StudentQuizController::class, 'results'])->name('student.quizzes.results');

            // Games
            Route::get('games', [StudentGameController::class, 'index'])->name('student.games.index');
            Route::get('games/{game}', [StudentGameController::class, 'play'])->name('student.games.play');
            Route::post('games/{game}/complete', [StudentGameController::class, 'complete'])->name('student.games.complete');
            Route::get('games/{game}/leaderboard', [StudentGameController::class, 'leaderboard'])->name('student.games.leaderboard');

            // Notices
            Route::get('notices', [StudentNoticeController::class, 'index'])->name('student.notices.index');
            Route::get('notices/{notice}', [StudentNoticeController::class, 'show'])->name('student.notices.show');
        });

        // ── Parent Routes ──
        Route::prefix('parent')->middleware('role:parent')->group(function () {
            Route::get('dashboard', ParentDashboardController::class)->name('parent.dashboard');

            // Overview pages (all children)
            Route::get('results', [ParentOverviewController::class, 'results'])->name('parent.results.index');
            Route::get('assignments', [ParentOverviewController::class, 'assignments'])->name('parent.assignments.index');
            Route::get('quizzes', [ParentOverviewController::class, 'quizzes'])->name('parent.quizzes.index');
            Route::get('games', [ParentOverviewController::class, 'games'])->name('parent.games.index');

            // Children
            Route::get('children/{child}', [ChildController::class, 'show'])->name('parent.children.show');

            // Child Results
            Route::get('children/{child}/results', [ChildResultController::class, 'index'])->name('parent.children.results');
            Route::get('children/{child}/results/{result}', [ChildResultController::class, 'show'])->name('parent.children.results.show');

            // Child Assignments
            Route::get('children/{child}/assignments', [ChildAssignmentController::class, 'index'])->name('parent.children.assignments');

            // Child Quizzes
            Route::get('children/{child}/quizzes', [ChildQuizResultController::class, 'index'])->name('parent.children.quizzes');

            // Child Games
            Route::get('children/{child}/games', [ChildGameStatsController::class, 'index'])->name('parent.children.games');

            // Notices
            Route::get('notices', [ParentNoticeController::class, 'index'])->name('parent.notices.index');
            Route::get('notices/{notice}', [ParentNoticeController::class, 'show'])->name('parent.notices.show');
        });

        // ── Super Admin Routes ──
        Route::prefix('super-admin')->middleware('role:super_admin')->group(function () {
            Route::get('dashboard', SuperAdminDashboardController::class)->name('super-admin.dashboard');

            // Schools
            Route::get('schools', [SuperAdminSchoolController::class, 'index'])->name('super-admin.schools.index');
            Route::get('schools/create', [SuperAdminSchoolController::class, 'create'])->name('super-admin.schools.create');
            Route::post('schools', [SuperAdminSchoolController::class, 'store'])->name('super-admin.schools.store');
            Route::get('schools/{school}', [SuperAdminSchoolController::class, 'show'])->name('super-admin.schools.show');
            Route::get('schools/{school}/edit', [SuperAdminSchoolController::class, 'edit'])->name('super-admin.schools.edit');
            Route::put('schools/{school}', [SuperAdminSchoolController::class, 'update'])->name('super-admin.schools.update');
            Route::post('schools/{school}/activate', [SuperAdminSchoolController::class, 'activate'])->name('super-admin.schools.activate');
            Route::post('schools/{school}/deactivate', [SuperAdminSchoolController::class, 'deactivate'])->name('super-admin.schools.deactivate');
            Route::delete('schools/{school}', [SuperAdminSchoolController::class, 'destroy'])->name('super-admin.schools.destroy')->middleware('throttle:sensitive-action');
            Route::post('schools/{school}/reset-admin-password', [SuperAdminSchoolController::class, 'resetAdminPassword'])->name('super-admin.schools.reset-admin-password');
            Route::get('schools/{school}/admins/create', [SuperAdminSchoolController::class, 'createAdmin'])->name('super-admin.schools.create-admin');
            Route::post('schools/{school}/admins', [SuperAdminSchoolController::class, 'storeAdmin'])->name('super-admin.schools.store-admin');
            Route::delete('schools/{school}/admins/{admin}', [SuperAdminSchoolController::class, 'destroyAdmin'])->name('super-admin.schools.destroy-admin');
            Route::post('schools/{school}/verify-domain', [SuperAdminSchoolController::class, 'verifyDomain'])->name('super-admin.schools.verify-domain');
            Route::post('schools/{school}/upload-logo', [SuperAdminSchoolController::class, 'uploadLogo'])->name('super-admin.schools.upload-logo');
            Route::delete('schools/{school}/remove-logo', [SuperAdminSchoolController::class, 'removeLogo'])->name('super-admin.schools.remove-logo');

            // AI Credits
            Route::get('credits', [SuperAdminCreditController::class, 'index'])->name('super-admin.credits.index');
            Route::post('credits/{school}/adjust', [SuperAdminCreditController::class, 'adjust'])->name('super-admin.credits.adjust');

            // Students (cross-school)
            Route::get('students', [SuperAdminStudentController::class, 'index'])->name('super-admin.students.index');
            Route::get('students/create', [SuperAdminStudentController::class, 'create'])->name('super-admin.students.create');
            Route::post('students', [SuperAdminStudentController::class, 'store'])->name('super-admin.students.store');
            Route::delete('students/{student}', [SuperAdminStudentController::class, 'destroy'])->name('super-admin.students.destroy');

            // Teachers (cross-school)
            Route::get('teachers', [SuperAdminTeacherController::class, 'index'])->name('super-admin.teachers.index');
            Route::get('teachers/create', [SuperAdminTeacherController::class, 'create'])->name('super-admin.teachers.create');
            Route::post('teachers', [SuperAdminTeacherController::class, 'store'])->name('super-admin.teachers.store');
            Route::delete('teachers/{teacher}', [SuperAdminTeacherController::class, 'destroy'])->name('super-admin.teachers.destroy');

            // Parents (cross-school)
            Route::get('parents', [SuperAdminParentController::class, 'index'])->name('super-admin.parents.index');
            Route::get('parents/create', [SuperAdminParentController::class, 'create'])->name('super-admin.parents.create');
            Route::post('parents', [SuperAdminParentController::class, 'store'])->name('super-admin.parents.store');
            Route::delete('parents/{parent}', [SuperAdminParentController::class, 'destroy'])->name('super-admin.parents.destroy');

            // Announcements
            Route::get('announcements', [SuperAdminAnnouncementController::class, 'index'])->name('super-admin.announcements.index');
            Route::get('announcements/create', [SuperAdminAnnouncementController::class, 'create'])->name('super-admin.announcements.create');
            Route::post('announcements', [SuperAdminAnnouncementController::class, 'store'])->name('super-admin.announcements.store');
            Route::get('announcements/{announcement}', [SuperAdminAnnouncementController::class, 'show'])->name('super-admin.announcements.show');
            Route::get('announcements/{announcement}/edit', [SuperAdminAnnouncementController::class, 'edit'])->name('super-admin.announcements.edit');
            Route::put('announcements/{announcement}', [SuperAdminAnnouncementController::class, 'update'])->name('super-admin.announcements.update');
            Route::post('announcements/{announcement}/deactivate', [SuperAdminAnnouncementController::class, 'deactivate'])->name('super-admin.announcements.deactivate');
            Route::post('announcements/{announcement}/activate', [SuperAdminAnnouncementController::class, 'activate'])->name('super-admin.announcements.activate');
            Route::delete('announcements/{announcement}', [SuperAdminAnnouncementController::class, 'destroy'])->name('super-admin.announcements.destroy');

            // Emails
            Route::get('emails', [SuperAdminEmailController::class, 'index'])->name('super-admin.emails.index');
            Route::get('emails/create', [SuperAdminEmailController::class, 'create'])->name('super-admin.emails.create');
            Route::post('emails', [SuperAdminEmailController::class, 'store'])->name('super-admin.emails.store');
            Route::get('emails/{email}', [SuperAdminEmailController::class, 'show'])->name('super-admin.emails.show');
        });
    });

}); // End of /portal prefix group

require __DIR__.'/settings.php';
