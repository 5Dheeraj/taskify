<?php

use App\Models\ActivityLog;
use App\Models\PaymentMethod;
use App\Http\Middleware\Authorize;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskTimeEntry;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\TagsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\NotesController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\TasksController;
use App\Http\Controllers\TaxesController;
use App\Http\Controllers\TodosController;
use App\Http\Controllers\UnitsController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\SubscriptionPlan;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\UpdaterController;
use App\Http\Controllers\ExpensesController;
use App\Http\Controllers\FrontEndController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\MeetingsController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\PayslipsController;
use App\Http\Controllers\PriorityController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TaskListController;
use App\Http\Controllers\ContractsController;
use App\Http\Controllers\InstallerController;
use App\Http\Middleware\CustomRoleMiddleware;
use App\Http\Controllers\AllowancesController;
use App\Http\Controllers\DeductionsController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\WorkspacesController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\TimeTrackerController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\LeaveRequestController;
use Illuminate\Validation\UnauthorizedException;
use App\Http\Controllers\NotificationsController;
use Spatie\Permission\Middlewares\RoleMiddleware;
use App\Http\Controllers\PaymentMethodsController;
use App\Http\Controllers\SuperAdmin\PlanController;
use App\Http\Controllers\EstimatesInvoicesController;
use App\Http\Controllers\SuperAdmin\ManagerController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\SuperAdmin\CustomerController;
use Spatie\Permission\Middlewares\PermissionMiddleware;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Http\Controllers\SuperAdmin\TransactionController;
use App\Http\Controllers\SuperAdmin\SubscriptionController;
use App\Http\Controllers\SuperAdmin\PaymentMethodController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\TrainingContentController;
use App\Http\Controllers\TrainingSlideController;
use Spatie\Permission\PermissionRegistrar;
use App\Http\Controllers\SuperAdmin\HomeController as SuperAdminHomeController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
//---------------------------------------------------------------

Route::get('/clear-cache', function () {
    Artisan::call('optimize:clear');
    return redirect()->back()->with('message', 'Cache cleared successfully.');
});
Route::get('/migrate', function () {
    Artisan::call('migrate', [
        '--path' => 'database/migrations/2025_02_25_045207_create_menu_orders_table.php'
    ]);

    return redirect()->back()->with('message', 'Migrate successfully.');
});
Route::get('/create-symlink', function () {
    if (config('constants.ALLOW_MODIFICATION') === 1) {
        $storageLinkPath = public_path('storage');
        if (is_dir($storageLinkPath)) {
            File::deleteDirectory($storageLinkPath);
        }
        Artisan::call('storage:link');
        return redirect()->back()->with('message', 'Symbolik link created successfully.');
    } else {
        return redirect()->back()->with('error', 'This operation is not allowed in demo mode.');
    }
});
Route::get('/install', [InstallerController::class, 'index'])->middleware('guest');
Route::post('/installer/config-db', [InstallerController::class, 'config_db'])->middleware('guest');
Route::post('/installer/install', [InstallerController::class, 'install'])->middleware('guest');
Route::post('/logout', [UserController::class, 'logout'])->middleware(['multiguard'])->name('logout');
Route::get("settings/languages/switch/{code}", [LanguageController::class, 'switch'])->name('languages.switch');
Route::put("settings/languages/set-default", [LanguageController::class, 'set_default'])->name('languages.set_default');
Route::put('/profile/update_photo/{userOrClient}', [ProfileController::class, 'update_photo'])->name('profile.update_photo');
Route::get('/search', [SearchController::class, 'search'])->name('search.search');
Route::put('profile/update/{userOrClient}', [ProfileController::class, 'update'])->name('profile.update')->middleware(['demo_restriction']);
Route::middleware(['CheckInstallation', 'checkRole',])->group(function () {
    Route::get('/', [FrontEndController::class, 'index'])->name('frontend.index');
    Route::get('/about-us', [FrontEndController::class, 'about_us'])->name('frontend.about_us');
    Route::get('/contact-us', [FrontEndController::class, 'contact_us'])->name('frontend.contact_us');
    Route::post('/send-mail', [FrontEndController::class, 'send_mail'])->name('frontend.send_mail');
    Route::get('/faqs', [FrontEndController::class, 'faqs'])->name('frontend.faqs');
    Route::get('/privacy-policy', [FrontEndController::class, 'privacy_policy'])->name('frontend.privacy_policy');
    Route::get('/terms-and-condition', [FrontEndController::class, 'terms_and_condition'])->name('frontend.terms_and_condition');
    Route::get('/features', [FrontEndController::class, 'features'])->name('frontend.features');
    Route::get('/pricing', [FrontEndController::class, 'pricing'])->name('frontend.pricing');
    Route::get('/refund-policy', [FrontEndController::class, 'refund_policy'])->name('frontend.refund_policy');
    Route::get('/login', [UserController::class, 'login'])->name('login');
    Route::post('/users/register', [UserController::class, 'register'])->name('users.register');
    Route::post('/users/authenticate', [UserController::class, 'authenticate'])->name('users.authenticate');
    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->middleware('guest')->name('forgot-password');
    Route::post('/forgot-password-mail', [ForgotPasswordController::class, 'sendResetLinkEmail'])->middleware('guest')->name('forgot-password-mail');
    Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetPasswordForm'])->middleware('guest')->name('password.reset');
    Route::post('/reset-password', [ForgotPasswordController::class, 'ResetPassword'])->middleware('guest')->name('password.update');
    Route::get('/email/verify', [UserController::class, 'email_verification'])->name('verification.notice')->middleware(['auth:web,client']);
    Route::get('/email/verify/{id}/{hash}', [ClientController::class, 'verify_email'])->middleware(['auth:web,client', 'signed'])->name('verification.verify');
    Route::get('/email/verification-notification', [UserController::class, 'resend_verification_link'])->middleware(['auth:web,client', 'throttle:6,1'])->name('verification.send');
    // ,'custom-verified'
    Route::prefix('master-panel')->middleware(['multiguard', 'custom-verified', 'check.subscription', 'subscription.modules'])->group(function () {
        Route::get('/home', [HomeController::class, 'index'])->name('home.index');
        Route::get('/home/upcoming-birthdays', [HomeController::class, 'upcoming_birthdays'])->name('home.upcoming_birthdays');

        Route::get('/home/upcoming-work-anniversaries', [HomeController::class, 'upcoming_work_anniversaries'])->name('home.upcoming_work_anniversaries');
        Route::get('/home/members-on-leave', [HomeController::class, 'members_on_leave'])->name('home.members_on_leave');
        Route::get('/home/upcoming-birthdays-calendar', [HomeController::class, 'upcoming_birthdays_calendar']);
        Route::get('/home/upcoming-work-anniversaries-calendar', [HomeController::class, 'upcoming_work_anniversaries_calendar']);
        Route::get('/home/members-on-leave-calendar', [HomeController::class, 'members_on_leave_calendar']);
        //Projects--------------------------------------------------------
        Route::middleware(['has_workspace', 'customcan:manage_projects'])->group(function () {
            Route::get('/projects/{type?}', [ProjectsController::class, 'index'])->where('type', 'favorite')->name('projects.index');

            Route::get('/projects/list/{type?}', [ProjectsController::class, 'list_view'])->where('type', 'favorite')->name('projects.list_view');

            Route::get('/projects/information/{id}', [ProjectsController::class, 'show'])->middleware(['checkAccess:App\Models\Project,projects,id,projects'])->name('projects.info');
            Route::get('/projects/create', [ProjectsController::class, 'create'])->middleware(['customcan:create_projects', 'check.maxCreate'])->name('projects.create');

            Route::post('/projects/store', [ProjectsController::class, 'store'])->middleware(['customcan:create_projects', 'log.activity', 'check.maxCreate'])->name('projects.store');
            Route::get('/projects/edit/{id}', [ProjectsController::class, 'edit'])
                ->middleware(['customcan:edit_projects', 'checkAccess:App\Models\Project,projects,id,projects'])->name('projects.edit');
            Route::get('/projects/get/{id}', [ProjectsController::class, 'get'])->middleware(['checkAccess:App\Models\Project,projects,id,projects'])->name('project.get');
            Route::get('/projects/get/{id}', [ProjectsController::class, 'get'])->middleware(['checkAccess:App\Models\Project,projects,id,projects'])->name('project.get');
            Route::put('/projects/update', [ProjectsController::class, 'update'])
                ->middleware(['customcan:edit_projects', 'log.activity'])->name('projects.update');
            Route::middleware(['customcan:manage_media'])->group(function () {
                Route::post('/projects/upload-media', [ProjectsController::class, 'upload_media'])
                    ->middleware(['customcan:create_media', 'log.activity'])->name('projects.upload_media');
                Route::get('/projects/get-media/{id}', [ProjectsController::class, 'get_media'])->name('projects.get_media');
                Route::delete('/projects/delete-media/{id}', [ProjectsController::class, 'delete_media'])
                    ->middleware(['customcan:delete_media', 'log.activity'])->name('projects.delete_media');
                Route::delete('/projects/delete-multiple-media', [ProjectsController::class, 'delete_multiple_media'])
                    ->middleware(['customcan:delete_media', 'log.activity'])->name('projects.delete_multiple_media');
            });
            Route::delete('/projects/destroy/{id}', [ProjectsController::class, 'destroy'])
                ->middleware(['customcan:delete_projects', 'demo_restriction', 'checkAccess:App\Models\Project,projects,id,projects', 'log.activity'])->name('projects.destroy');
            Route::delete('/projects/destroy_multiple', [ProjectsController::class, 'destroy_multiple'])
            ->middleware(['customcan:delete_projects', 'demo_restriction', 'log.activity'])->name('projects.delete_multiple');
            Route::get('/projects/listing/{id?}', [ProjectsController::class, 'list'])->name('projects.list');
            Route::post('/projects/update-favorite/{id}', [ProjectsController::class, 'update_favorite'])->name('projects.update_favorite');
            Route::get('/projects/duplicate/{id}', [ProjectsController::class, 'duplicate'])
                ->middleware(['customcan:create_projects', 'checkAccess:App\Models\Project,projects,id,projects', 'log.activity', 'check.maxCreate'])->name('projects.duplicate');
            Route::post('/projects/quick/{id}', [ProjectsController::class, 'quick_view'])->name('projects.quick_view');
            Route::post('update-project-status', [ProjectsController::class, 'update_status'])
            ->middleware(['customcan:edit_projects', 'log.activity'])->name('update-project-status');
            Route::post('update-project-priority', [ProjectsController::class, 'update_priority'])
            ->middleware(['customcan:edit_projects', 'log.activity'])->name('update-project-priority');
            Route::get('/projects/kanban-view/', [ProjectsController::class, 'kanban_view'])->name('projects.kanban_view');
            Route::post('/projects/information/{id}/comments', [ProjectsController::class, 'comments'])->name('comments.store');
            Route::get('/projects/comments/get/{id}', [ProjectsController::class, 'get_comment'])->name('comments.get');
            Route::post('/projects/comments/update', [ProjectsController::class, 'update_comment'])->name('comments.update');
            Route::delete('/projects/comments/destroy', [ProjectsController::class, 'destroy_comment'])->name('comments.destroy');
            Route::any('/projects/comments/destroy-attachment/{id}', [ProjectsController::class, 'destroy_comment_attachment'])->name('comments.destroy_attachment');
            Route::get('/projects/get-users', [ProjectsController::class, 'get_users']);

            Route::get('/mention', function () {
                return view('Mention');
            });

            Route::get('/projects/gantt-chart-view', [ProjectsController::class, 'gantt_chart'])->name('projects.gantt_chart');
            Route::get('/projects/fetch-gantt-data', [ProjectsController::class, 'fetch_gantt_data'])->name('projects.fetch_gantt_data');
            Route::post('projects/gantt-chart-view/update-module-dates', [ProjectsController::class, 'update_module_dates'])->name('projects.update_module_dates');
            // Mind Map Route
            Route::get('/projects/mind-map/{id}', [ProjectsController::class, 'mind_map'])->name('projects.mind_map');
            Route::any('/mind-map/export-mindmap/{id}', [ProjectsController::class, 'export_mindmap'])->name('projects.export_mindmap');
            Route::get('/projects/tasks/create/{id}', [TasksController::class, 'create'])
                ->middleware(['customcan:manage_tasks', 'customcan:create_tasks', 'checkAccess:App\Models\Project,projects,id,projects'])->name('projects.tasks.create');
            Route::get('/projects/tasks/edit/{id}', [TasksController::class, 'edit'])
                ->middleware(['customcan:manage_tasks', 'customcan:edit_tasks', 'checkAccess:App\Models\Task,tasks,id,tasks'])->name('projects.tasks.edit');
            Route::get('/projects/tasks/list/{id}', [TasksController::class, 'index'])
                ->middleware(['customcan:manage_tasks'])->name('projects.tasks.index');
            Route::get('/projects/tasks/draggable/{id}', [TasksController::class, 'dragula'])
                ->middleware(['customcan:manage_tasks', 'checkAccess:App\Models\Project,projects,id,projects'])->name('projects.tasks.draggable');
            Route::put('/save-projects-view-preference', [ProjectsController::class, 'saveViewPreference'])->name('save-projects-view-preference');
            Route::middleware(['customcan:manage_tags'])->group(function () {
                Route::get('/tags/manage', [TagsController::class, 'index'])->name('tags.index');
                Route::post('/tags/store', [TagsController::class, 'store'])->middleware(['customcan:create_tags', 'log.activity'])->name('tags.store');
                Route::get('/tags/list', [TagsController::class, 'list'])->name('tags.list');
                Route::get('/tags/get/{id}', [TagsController::class, 'get'])->name('tags.get');
                Route::post('/tags/update', [TagsController::class, 'update'])->middleware(['customcan:edit_tags', 'log.activity'])->name('tags.update');
                Route::get('/tags/get-suggestion', [TagsController::class, 'get_suggestions'])->name('tags.get_suggestions');
                Route::post('/tags/get-ids', [TagsController::class, 'get_ids'])->name('tags.get_ids');
                Route::delete('/tags/destroy/{id}', [TagsController::class, 'destroy'])->middleware(['customcan:delete_tags', 'demo_restriction', 'log.activity'])->name('tags.destroy');
                Route::delete('/tags/destroy_multiple', [TagsController::class, 'destroy_multiple'])->middleware(['customcan:delete_tags', 'demo_restriction', 'log.activity'])->name('tags.destroy_multiple');
// ------------------- LMS Routes -------------------------
Route::middleware(['has_workspace', 'customcan:manage_training'])
    ->prefix('master-panel')
    ->group(function () {

    // ✅ Training Main
    Route::get('/training', [TrainingController::class, 'index'])->name('training.index');
    Route::get('/training/create', [TrainingController::class, 'create'])->name('training.create');
    Route::post('/training/store', [TrainingController::class, 'store'])->name('training.store');
    Route::get('/training/{id}', [TrainingController::class, 'show'])->name('training.show');
    Route::get('/training/{id}/edit', [TrainingController::class, 'edit'])->name('training.edit');
    Route::put('/training/{id}', [TrainingController::class, 'update'])->name('training.update');
    Route::delete('/training/{id}', [TrainingController::class, 'destroy'])->name('training.destroy');

    // ✅ Training Content
    Route::prefix('/training/content')->group(function () {
        Route::get('/{id}/edit', [TrainingContentController::class, 'edit'])->name('training.content.edit');
        Route::put('/{id}/update', [TrainingContentController::class, 'update'])->name('training.content.update');
        Route::delete('/{id}/delete', [TrainingContentController::class, 'destroy'])->name('training.content.delete');
        Route::post('/mark-as-done', [TrainingContentController::class, 'markContentCompleted'])->name('training.content.complete');
    });
    Route::get('/training/{id}/content', [TrainingContentController::class, 'index'])->name('training.content.index');
    Route::get('/training/{id}/content/create', [TrainingContentController::class, 'create'])->name('training.content.create');
    Route::post('/training/{id}/content/store', [TrainingContentController::class, 'store'])->name('training.content.store');

    // ✅ Training Slides
    Route::get('/training/{id}/slides', [TrainingSlideController::class, 'show'])->name('training.slides.show');
    Route::get('/training/{id}/slides/create', [TrainingSlideController::class, 'create'])->name('training.slides.create');
    Route::post('/training/slides/store', [TrainingSlideController::class, 'store'])->name('training.slides.store');
    Route::get('/training/slides/{id}/edit', [TrainingSlideController::class, 'edit'])->name('training.slides.edit');
    Route::put('/training/slides/{id}/update', [TrainingSlideController::class, 'update'])->name('training.slides.update');
    Route::delete('/training/slides/{id}/delete', [TrainingSlideController::class, 'destroy'])->name('training.slides.delete');
    Route::get('/training/{id}/slides/player', [TrainingSlideController::class, 'player'])->name('training.slides.player');
    Route::get('/training/{training}/slides', [TrainingSlideController::class, 'index'])->name('training.slides.index');

    // ✅ Assignments
    Route::prefix('/training/{id}/assignments')->group(function () {
        Route::get('/', [TrainingAssignmentController::class, 'index'])->name('training.assignments.index');
        Route::get('/create', [TrainingAssignmentController::class, 'create'])->name('training.assignments.create');
        Route::post('/store', [TrainingAssignmentController::class, 'store'])->name('training.assignments.store');
    });

    // ✅ Quiz
    Route::prefix('/training/{id}/quiz')->group(function () {
        Route::get('/', [TrainingQuizController::class, 'index'])->name('training.quiz.index');
        Route::get('/create', [TrainingQuizController::class, 'create'])->name('training.quiz.create');
        Route::post('/store', [TrainingQuizController::class, 'store'])->name('training.quiz.store');
    });

    // ✅ Certificate
    Route::prefix('/training/{id}/certificate')->group(function () {
        Route::get('/', [TrainingCertificateController::class, 'index'])->name('training.certificate.index');
        Route::post('/generate', [TrainingCertificateController::class, 'generate'])->name('training.certificate.generate');
    });

    // ✅ Notices
    Route::prefix('/training/{id}/notices')->group(function () {
        Route::get('/', [TrainingNoticeController::class, 'index'])->name('training.notices.index');
        Route::get('/create', [TrainingNoticeController::class, 'create'])->name('training.notices.create');
        Route::post('/store', [TrainingNoticeController::class, 'store'])->name('training.notices.store');
    });

    // ✅ Progress Dashboard
    Route::get('/training/progress/{id}', [TrainingProgressController::class, 'index'])->name('training.progress.index');

    // ✅ Permission Debug Route (For Checking Assigned Permissions)
    Route::get('/debug-permissions', function () {
        $user = Auth::user();
        return $user ? $user->getAllPermissions()->pluck('name') : response('Not logged in', 401);
    })->name('training.debug.permissions');
});
// ------------------- LMS Routes End -------------------------
            });
        });
        // Milestones
        Route::middleware(['has_workspace', 'customcan:manage_milestones'])->group(function () {
            Route::post('/projects/store-milestone', [ProjectsController::class, 'store_milestone'])->middleware(['customcan:create_milestones', 'log.activity'])->name('projects.store_milestone');
            Route::get('/projects/get-milestones/{id}', [ProjectsController::class, 'get_milestones'])->name('projects.get_milestones');
            Route::get('/projects/get-milestone/{id}', [ProjectsController::class, 'get_milestone'])
            ->name('projects.get_milestone');
            Route::post('/projects/update-milestone', [ProjectsController::class, 'update_milestone'])->middleware(['customcan:edit_milestones', 'log.activity'])->name('projects.update_milestone');
            Route::delete('/projects/delete-milestone/{id}', [ProjectsController::class, 'delete_milestone'])->middleware(['customcan:edit_milestones', 'demo_restriction', 'log.activity'])->name('projects.delete_milestone');
            Route::delete('/projects/delete-multiple-milestone', [ProjectsController::class, 'delete_multiple_milestones'])->middleware(['customcan:edit_milestones', 'demo_restriction', 'log.activity'])->name('projects.delete_multiple_milestone');
        });
        Route::middleware(['has_workspace', 'customcan:manage_statuses'])->group(function () {
            // Status
            Route::get('/status/manage', [StatusController::class, 'index'])->name('status.index');
            Route::post('/status/store', [StatusController::class, 'store'])->middleware(['customcan:create_statuses', 'demo_restriction', 'log.activity'])->name('status.store');
            Route::get('/status/list', [StatusController::class, 'list'])->name('status.list');
            Route::post('/status/update', [StatusController::class, 'update'])->middleware(['customcan:edit_statuses', 'demo_restriction', 'log.activity'])->name('status.update');
            Route::get('/status/get/{id}', [StatusController::class, 'get'])->name('status.get');
            Route::delete('/status/destroy/{id}', [StatusController::class, 'destroy'])->middleware(['customcan:delete_statuses', 'demo_restriction', 'log.activity'])->name('status.destroy');
            Route::delete('/status/destroy_multiple', [StatusController::class, 'destroy_multiple'])->middleware('customcan:delete_priorities', 'demo_restriction', 'log.activity')->name('status.destroy_multiple');
        });
        Route::middleware(['customcan:manage_priorities'])->group(function () {
            // Priorities
            Route::get('/priority/manage', [PriorityController::class, 'index'])->name('priority.manage');
            Route::post('/priority/store', [PriorityController::class, 'store'])->middleware(['customcan:create_priorities', 'demo_restriction', 'log.activity'])->name('priority.store');
            Route::get('/priority/list', [PriorityController::class, 'list'])->name('priority.list');
            Route::post('/priority/update', [PriorityController::class, 'update'])->middleware(['customcan:edit_priorities', 'demo_restriction', 'log.activity'])->name('priority.update');
            Route::get('/priority/get/{id}', [PriorityController::class, 'get'])->name('priority.get');
            Route::delete('/priority/destroy/{id}', [PriorityController::class, 'destroy'])->middleware(['customcan:delete_priorities', 'demo_restriction', 'log.activity'])->name('priority.destroy');
            Route::delete('/priority/destroy_multiple', [PriorityController::class, 'destroy_multiple'])->middleware(['customcan:delete_priorities', 'demo_restriction', 'log.activity'])->name('priority.destroy_multiple');
        });
        //Tasks-------------------------------------------------------------
        Route::middleware(['has_workspace', 'customcan:manage_tasks'])->group(function () {
            Route::get('/tasks', [TasksController::class, 'index'])->name('tasks.index');
            Route::get('/tasks/group-by-task-list', [TasksController::class, 'group_by_task_list'])->name('tasks.groupByTaskList');
            Route::get('/tasks/information/{id}', [TasksController::class, 'show'])
                ->middleware(['checkAccess:App\Models\Task,tasks,id,tasks'])->name('tasks.info');
            Route::post('/tasks/information/{id}/comments', [TasksController::class, 'comments'])->name('tasks.comments.store');
            Route::get('/tasks/comments/get/{id}', [TasksController::class, 'get_comment'])->name('tasks.comments.get');
            Route::post('/tasks/comments/update', [TasksController::class, 'update_comment'])->name('tasks.comments.update');
            Route::delete('/tasks/comments/destroy', [TasksController::class, 'destroy_comment'])->name('tasks.comments.destroy');
            Route::get('/tasks/create', [TasksController::class, 'create'])
                ->middleware(['customcan:create_tasks'])->name('tasks.create');
            Route::post('/tasks/store', [TasksController::class, 'store'])
                ->middleware(['customcan:create_tasks', 'log.activity'])->name('tasks.store');
            Route::get('/tasks/duplicate/{id}', [TasksController::class, 'duplicate'])
                ->middleware(['customcan:create_tasks', 'checkAccess:App\Models\Task,tasks,id,tasks', 'log.activity', 'check.maxCreate'])->name('tasks.duplicate');
            Route::get('/tasks/get/{id}', [TasksController::class, 'get'])->name('tasks.get');
            Route::get('/tasks/edit/{id}', [TasksController::class, 'edit'])
                ->middleware(['customcan:edit_tasks', 'checkAccess:App\Models\Task,tasks,id,tasks'])->name('tasks.edit');
            Route::put('/tasks/update', [TasksController::class, 'update'])
                ->middleware(['customcan:edit_tasks', 'log.activity'])->name('tasks.update');
            Route::middleware(['customcan:manage_media'])->group(function () {
                Route::post('/tasks/upload-media', [TasksController::class, 'upload_media'])
                    ->middleware(['customcan:create_media', 'log.activity'])->name('tasks.upload_media');
                Route::get('/tasks/get-media/{id}', [TasksController::class, 'get_media'])->name('tasks.get_media');
                Route::delete('/tasks/delete-media/{id}', [TasksController::class, 'delete_media'])
                    ->middleware(['customcan:delete_media', 'log.activity'])->name('tasks.delete_media');
                Route::delete('/tasks/delete-multiple-media', [TasksController::class, 'delete_multiple_media'])
                ->middleware(['customcan:delete_media', 'log.activity'])->name('tasks.delete_multiple_media');
            });
            Route::delete('/tasks/destroy/{id}', [TasksController::class, 'destroy'])
                ->middleware(['customcan:delete_tasks', 'demo_restriction', 'checkAccess:App\Models\Task,tasks,id,tasks', 'log.activity'])->name('tasks.destroy');
            Route::delete('/tasks/destroy_multiple', [TasksController::class, 'destroy_multiple'])->middleware(['customcan:delete_tasks', 'demo_restriction', 'log.activity'])->name('tasks.destroy_multiple');
            Route::get('/tasks/list/{id?}', [TasksController::class, 'list'])->name('tasks.list');
            Route::get('/tasks/draggable', [TasksController::class, 'dragula'])->name('tasks.draggable');
            Route::put('/tasks/{id}/update-status/{status}', [TasksController::class, 'updateStatus'])->middleware(['customcan:edit_tasks', 'log.activity'])->name('tasks.update_status');
            Route::put('/save-tasks-view-preference', [TasksController::class, 'saveViewPreference'])->name('tasks.saveViewPreference');
            Route::post('update-task-status', [TasksController::class, 'update_status'])
            ->middleware(['customcan:edit_tasks', 'log.activity'])->name('update-task-status');
            Route::post('update-task-priority', [TasksController::class, 'update_priority'])
            ->middleware(['customcan:edit_tasks', 'log.activity'])->name('update-task-priority');
            // tasks calender view
            Route::get('/tasks/calendar-view', [TasksController::class, 'calendar_view'])->name('tasks.calendar_view');
            Route::get('tasks/get-calendar-data', [TasksController::class, 'get_calendar_data'])->name('tasks.get_calendar_data');
            //Tasks Time Entries
            Route::get('/tasks/time-entries/list/{id}', [TaskTimeEntry::class, 'list'])->name('tasks.time_entries.list');
            Route::post('tasks/time-entries/store', [TaskTimeEntry::class, 'store'])->name('tasks.time_entries.store');
            Route::any('/tasks/time-entries/destroy/{id}', [TaskTimeEntry::class, 'destroy'])->name('tasks.time_entries.destroy');
            Route::any('/tasks/time-entries/destroy_multiple', [TaskTimeEntry::class, 'destroy_multiple'])->name('tasks.time_entries.destroy_multiple');

        });
        //Meetings-------------------------------------------------------------
        Route::middleware(['has_workspace', 'customcan:manage_meetings'])->group(function () {
            Route::get('/meetings', [MeetingsController::class, 'index'])->name('meetings.index');
            Route::get('/meetings/create', [MeetingsController::class, 'create'])->middleware(['customcan:create_meetings'])->name('meetings.create');
            Route::post('/meetings/store', [MeetingsController::class, 'store'])->middleware(['customcan:create_meetings', 'log.activity'])->name('meetings.store');
            Route::get('/meetings/list', [MeetingsController::class, 'list'])->name('meetings.list');
            Route::get('/meetings/edit/{id}', [MeetingsController::class, 'edit'])
                ->middleware(['customcan:edit_meetings', 'checkAccess:App\Models\Meeting,meetings,id,meetings'])->name('meetings.edit');
            Route::put('/meetings/update', [MeetingsController::class, 'update'])
                ->middleware(['customcan:edit_meetings', 'checkAccess:App\Models\Meeting,meetings,id,meetings', 'log.activity'])->name('meetings.update');
            Route::delete('/meetings/destroy/{id}', [MeetingsController::class, 'destroy'])
                ->middleware(['customcan:delete_meetings', 'demo_restriction', 'checkAccess:App\Models\Meeting,meetings,id,meetings', 'log.activity'])->name('meetings.destroy');
            Route::delete('/meetings/destroy_multiple', [MeetingsController::class, 'destroy_multiple'])
            ->middleware(['customcan:delete_meetings', 'demo_restriction', 'log.activity'])->name('meetings.destroy_multiple');
            Route::get('/meetings/join/{id}', [MeetingsController::class, 'join'])
                ->middleware(['checkAccess:App\Models\Meeting,meetings,id,meetings'])->name('meetings.join');
            Route::get('/meetings/duplicate/{id}', [MeetingsController::class, 'duplicate'])
                ->middleware(['customcan:create_meetings', 'checkAccess:App\Models\Meeting,meetings,id,meetings', 'log.activity'])->name('meetings.duplicate');
            Route::get('/meetings/get/{id}', [MeetingsController::class, 'get'])->middleware(['checkAccess:App\Models\Meeting,meetings,id,meetings'])->name('meeting.get');
        });
        //Workspaces-------------------------------------------------------------
        Route::middleware(['customcan:manage_workspaces'])->group(function () {
            Route::get('/workspaces', [WorkspacesController::class, 'index'])->name('workspaces.index');
            Route::get('/workspaces/create', [WorkspacesController::class, 'create'])->middleware(['customcan:create_workspaces', 'check.maxCreate'])->name('workspaces.create');
            Route::post('/workspaces/store', [WorkspacesController::class, 'store'])->middleware(['check.maxCreate', 'customcan:create_workspaces', 'log.activity'])->name('workspaces.store');
            Route::get('/workspaces/duplicate/{id}', [WorkspacesController::class, 'duplicate'])
                ->middleware(['customcan:create_workspaces', 'checkAccess:App\Models\Workspace,workspaces,id,workspaces', 'log.activity', 'check.maxCreate'])->name('workspaces.duplicate');
            Route::get('/workspaces/list', [WorkspacesController::class, 'list'])->name('workspaces.list');
            Route::get('/workspaces/edit/{id}', [WorkspacesController::class, 'edit'])
                ->middleware(['customcan:edit_workspaces', 'checkAccess:App\Models\Workspace,workspaces,id,workspaces'])->name('workspaces.edit');
            Route::put('/workspaces/update', [WorkspacesController::class, 'update'])
                ->middleware(['customcan:edit_workspaces', 'demo_restriction', 'checkAccess:App\Models\Workspace,workspaces,id,workspaces', 'log.activity'])->name('workspaces.update');
            Route::delete('/workspaces/destroy/{id}', [WorkspacesController::class, 'destroy'])
                ->middleware(['customcan:delete_workspaces', 'demo_restriction', 'checkAccess:App\Models\Workspace,workspaces,id,workspaces', 'log.activity'])->name('workspaces.destroy');
            Route::delete('/workspaces/destroy_multiple', [WorkspacesController::class, 'destroy_multiple'])
            ->middleware(['customcan:delete_workspaces', 'demo_restriction', 'log.activity'])->name('workspaces.destroy_multiple');
            Route::get('/workspaces/switch/{id}', [WorkspacesController::class, 'switch'])
                ->middleware(['checkAccess:App\Models\Workspace,workspaces,id,workspaces'])->name('workspaces.switch');
            Route::get('/workspaces/get/{id}', [WorkspacesController::class, 'get'])->middleware(['checkAccess:App\Models\Workspace,workspaces,id,workspaces'])->name('workspace.get');
        });
        Route::get('/workspaces/remove_participant', [WorkspacesController::class, 'remove_participant'])->middleware(['demo_restriction'])->name('workspaces.remove_participant');
        //Todo-------------------------------------------------------------
        Route::middleware(['has_workspace'])->group(function () {
            Route::get('/todos', [TodosController::class, 'index'])->name('todos.index');
            Route::get('/todos/create', [TodosController::class, 'create'])->name('todos.create');
            Route::post('/todos/store', [TodosController::class, 'store'])->middleware(['log.activity'])->name('todos.store');
            Route::get('/todos/edit/{id}', [TodosController::class, 'edit'])->name('todos.edit');
            Route::post('/todos/update/', [TodosController::class, 'update'])->middleware(['log.activity'])->name('todos.update');
            Route::put('/todos/update_status', [TodosController::class, 'update_status'])->middleware(['log.activity'])->name('todos.update_status');
            Route::delete('/todos/destroy/{id}', [TodosController::class, 'destroy'])->middleware(['demo_restriction', 'log.activity'])->name('todos.destroy');
            Route::any('/todos/destroy_multiple', [TodosController::class, 'destroy_multiple'])->middleware(['demo_restriction', 'log.activity'])->name('todos.destroy_multiple');
            Route::get('/todos/get/{id}', [TodosController::class, 'get'])->name('todos.get');
            Route::get('/notes', [NotesController::class, 'index'])->name('notes.index');
            Route::post('/notes/store', [NotesController::class, 'store'])->middleware('log.activity')->name('notes.store');
            Route::post('/notes/update', [NotesController::class, 'update'])->middleware('log.activity')->name('notes.update');
            Route::get('/notes/get/{id}', [NotesController::class, 'get'])->name('notes.get');
            Route::delete('/notes/destroy/{id}', [NotesController::class, 'destroy'])->middleware(['demo_restriction', 'log.activity'])->name('notes.destroy');
            Route::any('/notes/destroy_multiple', [NotesController::class, 'destroy_multiple'])->middleware(['demo_restriction', 'log.activity'])->name('notes.destroy_multiple');
        });
        //Users-------------------------------------------------------------
        Route::get('account/{user}', [ProfileController::class, 'show'])->name('profile.show');
        Route::delete('/account/destroy/{user}', [ProfileController::class, 'destroy'])->middleware(['demo_restriction'])->name('profile.destroy');
        Route::middleware(['has_workspace', 'customcan:manage_users'])->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/create', [UserController::class, 'create'])->middleware(['customcan:create_users', 'check.maxCreate'])->name('users.create');
            Route::post('/users/store', [UserController::class, 'store'])->middleware(['customcan:create_users', 'log.activity'])->name('users.store');
            Route::get('/users/profile/{id}', [UserController::class, 'show'])->name('users.show');
            Route::get('/users/edit/{id}', [UserController::class, 'edit_user'])->middleware(['customcan:edit_users'])->name('users.edit');
            Route::put('/users/update_user/{user}', [UserController::class, 'update_user'])->middleware(['customcan:edit_users', 'demo_restriction', 'log.activity'])->name('users.update_user');
            Route::delete('/users/delete_user/{user}', [UserController::class, 'delete_user'])->middleware(['customcan:delete_users', 'demo_restriction', 'log.activity'])->name('users.delete_user');
            Route::delete('/users/delete_multiple_user', [UserController::class, 'delete_multiple_user'])->middleware(['customcan:delete_users', 'demo_restriction', 'log.activity'])->name('users.delete_multiple_user');
            Route::get('/users/list', [UserController::class, 'list'])->name('users.list');
            Route::get('/users/{user}/permissions', [UserController::class, 'permissions'])->name('users.permissions');
            Route::put('/users/{user}/permissions', [UserController::class, 'update_permissions'])->name('users.update_permissions');
        });
        //Clients-------------------------------------------------------------


        Route::middleware(['has_workspace', 'customcan:manage_clients'])->group(function () {
            Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
            Route::get('/clients/profile/{id}', [ClientController::class, 'show'])->name('clients.profile');
            Route::get('/clients/create', [ClientController::class, 'create'])->middleware(['customcan:create_clients', 'check.maxCreate'])->name('clients.create');
            Route::post('/clients/store', [ClientController::class, 'store'])->middleware(['customcan:create_clients', 'log.activity'])->name('clients.store');
            Route::get('/clients/edit/{id}', [ClientController::class, 'edit'])->middleware(['customcan:edit_clients'])->name('clients.edit');
            Route::put('/clients/update/{id}', [ClientController::class, 'update'])->middleware(['customcan:edit_clients', 'demo_restriction', 'log.activity'])->name('clients.update');
            Route::delete('/clients/destroy/{id}', [ClientController::class, 'destroy'])->middleware(['customcan:delete_clients', 'demo_restriction', 'log.activity'])->name('clients.destroy');
            Route::delete('/clients/destroy_multiple', [ClientController::class, 'destroy_multiple'])->middleware(['customcan:delete_clients', 'demo_restriction', 'log.activity'])->name('clients.destroy_multiple');
            Route::get('/clients/list', [ClientController::class, 'list'])->name('clients.list');
            Route::get('/clients/get/{id}', [ClientController::class, 'get'])->name('clients.get');
            Route::get('/clients/{client}/permissions', [ClientController::class, 'permissions'])->name('clients.permissions');
            Route::put('/clients/{client}/permissions', [ClientController::class, 'update_permissions'])->name('clients.update_permissions');
        });
        //Settings-------------------------------------------------------------
        Route::middleware(['customRole:admin'])->group(function () {
            Route::get('/subscription-plan', [SubscriptionPlan::class, 'index'])->name('subscription-plan.index');
            Route::get('/subscription-plan/transactions-list', [SubscriptionPlan::class, 'transactionsList'])->name('subscription-plan.transactionsList');
            Route::get('/subscription-plan/buy-plan', [SubscriptionPlan::class, 'create'])->name('subscription-plan.buy-plan');
            Route::post('/subscription-plan/store', [SubscriptionPlan::class, 'store'])->name('subscription-plan.store');
            Route::get('/subscription-plan/checkout/{id}/{tenure}', [SubscriptionPlan::class, 'show'])->name('subscription-plan.checkout');
            Route::get('/subscription-plan/subscriptionHistory/', [SubscriptionPlan::class, 'subscriptionHistory'])->name('subscription-plan.subscriptionHistory');
            Route::any('/subscription-plan/upload-bank-transfer-document', [SubscriptionPlan::class, 'upload_bank_transfer_document'])->name('subscription-plan.upload-bank-transfer-document');
        });
        Route::middleware(['has_workspace'])->group(function () {
            Route::middleware(['admin_or_user'])->group(function () {
                Route::get('/leave-requests', [LeaveRequestController::class, 'index'])->name('leave_requests.index');
                Route::post('/leave-requests/store', [LeaveRequestController::class, 'store'])->middleware('log.activity')->name('leave_requests.store');
                Route::get('/leave-requests/list', [LeaveRequestController::class, 'list'])->name('leave_requests.list');
                Route::get('/leave-requests/get/{id}', [LeaveRequestController::class, 'get'])->name('leave_requests.get');
                Route::post('/leave-requests/update', [LeaveRequestController::class, 'update'])->middleware(['admin_or_leave_editor', 'log.activity'])->name('leave_requests.update');
                Route::post('/leave-requests/update-editors', [LeaveRequestController::class, 'update_editors'])->middleware(['customRole:admin'])->name('leave_requests.update_editors');
                Route::delete('/leave-requests/destroy/{id}', [LeaveRequestController::class, 'destroy'])->middleware(['admin_or_leave_editor', 'demo_restriction', 'log.activity'])->name('leave_requests.destroy');
                Route::delete('/leave-requests/destroy_multiple', [LeaveRequestController::class, 'destroy_multiple'])->middleware(['admin_or_leave_editor', 'demo_restriction', 'log.activity'])->name('leave_requests.destroy_multiple');
            });
            Route::middleware(['customcan:manage_contracts'])->group(function () {
                Route::get('/contracts', [ContractsController::class, 'index'])->name('contracts.index');
                Route::post('/contracts/store', [ContractsController::class, 'store'])->middleware(['customcan:create_contracts', 'log.activity'])->name('contracts.store');
                Route::get('/contracts/list', [ContractsController::class, 'list'])->name('contracts.list');
                Route::get('/contracts/get/{id}', [ContractsController::class, 'get'])->middleware(['checkAccess:App\Models\Contract,contracts,id'])->name('contracts.get');
                Route::post('/contracts/update', [ContractsController::class, 'update'])->middleware(['customcan:edit_contracts', 'log.activity'])->name('contracts.update');
                Route::get('/contracts/sign/{id}', [ContractsController::class, 'sign'])->middleware(['checkAccess:App\Models\Contract,contracts,id,contracts', 'log.activity'])->name('contracts.create.sign');
                Route::post('/contracts/create-sign', [ContractsController::class, 'create_sign'])->middleware('log.activity')->name('contracts.sign');
                Route::get('/contracts/duplicate/{id}', [ContractsController::class, 'duplicate'])->middleware(['customcan:create_contracts', 'checkAccess:App\Models\Contract,contracts,id,contracts', 'log.activity'])->name('contracts.duplicate');
                Route::delete('/contracts/destroy/{id}', [ContractsController::class, 'destroy'])->middleware(['customcan:delete_contracts', 'demo_restriction', 'checkAccess:App\Models\Contract,contracts,id,contracts', 'log.activity'])->name('contracts.destroy');
                Route::delete('/contracts/destroy_multiple', [ContractsController::class, 'destroy_multiple'])->middleware(['customcan:delete_contracts', 'demo_restriction', 'log.activity'])->name('contracts.destroy_multiple');
                Route::delete('/contracts/delete-sign/{id}', [ContractsController::class, 'delete_sign'])->middleware('log.activity')->name('contracts.delete_sign');
            });
            Route::middleware(['customcan:manage_contract_types'])->group(function () {
                Route::get('/contracts/contract-types', [ContractsController::class, 'contract_types'])->name('contracts.contract_types');
                Route::post('/contracts/store-contract-type', [ContractsController::class, 'store_contract_type'])->middleware(['customcan:create_contract_types', 'log.activity'])->name('contracts.store_contract_type');
                Route::get('/contracts/contract-types-list', [ContractsController::class, 'contract_types_list'])->name('contracts.contract_types_list');
                Route::get('/contracts/get-contract-type/{id}', [ContractsController::class, 'get_contract_type'])->name('contracts.get_contract_type');
                Route::post('/contracts/update-contract-type', [ContractsController::class, 'update_contract_type'])->middleware(['customcan:edit_contract_types', 'log.activity'])->name('contracts.update_contract_type');
                Route::delete('/contracts/delete-contract-type/{id}', [ContractsController::class, 'delete_contract_type'])->middleware(['customcan:delete_contract_types', 'demo_restriction', 'log.activity'])->name('contracts.delete_contract_type');
                Route::delete('/contracts/delete-multiple-contract-type', [ContractsController::class, 'delete_multiple_contract_type'])->middleware(['customcan:delete_contract_types', 'demo_restriction', 'log.activity'])->name('contracts.delete_multiple_contract_type');
            });

            Route::middleware(['customcan:manage_payslips'])->group(function () {
                Route::get('/payslips', [PayslipsController::class, 'index'])->name('payslips.index');
                Route::get('/payslips/create', [PayslipsController::class, 'create'])->middleware(['customcan:create_payslips'])->name('payslips.create');
                Route::post('/payslips/store', [PayslipsController::class, 'store'])->middleware(['customcan:create_payslips', 'log.activity'])->name('payslips.store');
                Route::get('/payslips/list', [PayslipsController::class, 'list'])->name('payslips.list');
                Route::delete('/payslips/destroy/{id}', [PayslipsController::class, 'destroy'])->middleware(['demo_restriction', 'customcan:delete_payslips', 'checkAccess:App\Models\Payslip,payslips,id,payslips', 'log.activity'])->name('payslips.destroy');
                Route::delete('/payslips/destroy_multiple', [PayslipsController::class, 'destroy_multiple'])->middleware(['demo_restriction', 'customcan:delete_payslips', 'log.activity'])->name('payslips.destroy_multiple');
                Route::get('/payslips/duplicate/{id}', [PayslipsController::class, 'duplicate'])->middleware(['customcan:create_payslips', 'checkAccess:App\Models\Payslip,payslips,id,payslips', 'log.activity'])->name('payslips.duplicate');
                Route::get('/payslips/edit/{id}', [PayslipsController::class, 'edit'])->middleware(['customcan:edit_payslips', 'checkAccess:App\Models\Payslip,payslips,id,payslips'])->name('payslips.edit');
                Route::post('/payslips/update', [PayslipsController::class, 'update'])->middleware(['customcan:edit_payslips', 'checkAccess:App\Models\Payslip,payslips,id,payslips', 'log.activity'])->name('payslips.update');
                Route::get('/payslips/view/{id}', [PayslipsController::class, 'view'])->middleware(['checkAccess:App\Models\Payslip,payslips,id,payslips'])->name('payslips.view');
            });
            Route::middleware(['customcan:manage_allowances'])->group(function () {
                Route::get('/allowances', [AllowancesController::class, 'index'])->name('allowances.index');
                Route::post('/allowances/store', [AllowancesController::class, 'store'])->middleware(['customcan:create_allowances', 'log.activity'])->name('allowances.store');
                Route::get('/allowances/list', [AllowancesController::class, 'list'])->name('allowances.list');
                Route::get('/allowances/get/{id}', [AllowancesController::class, 'get'])->name('allowances.get');
                Route::post('/allowances/update', [AllowancesController::class, 'update'])->middleware(['customcan:edit_allowances', 'log.activity'])->name('allowances.update');
                Route::delete('/allowances/destroy/{id}', [AllowancesController::class, 'destroy'])->middleware(['customcan:delete_allowances', 'demo_restriction', 'log.activity'])->name('allowances.destroy');
                Route::delete('/allowances/destroy_multiple', [AllowancesController::class, 'destroy_multiple'])->middleware(['customcan:delete_allowances', 'demo_restriction', 'log.activity'])->name('allowances.destroy_multiple');
            });
            Route::middleware(['customcan:manage_deductions'])->group(function () {
                Route::get('/deductions', [DeductionsController::class, 'index'])->name('deductions.index');
                Route::post('/deductions/store', [DeductionsController::class, 'store'])->middleware(['customcan:create_deductions', 'log.activity'])->name('deductions.store');
                Route::get('/deductions/get/{id}', [DeductionsController::class, 'get'])->name('deductions.get');
                Route::get('/deductions/list', [DeductionsController::class, 'list'])->name('deductions.list');
                Route::post('/deductions/update', [DeductionsController::class, 'update'])->middleware(['customcan:edit_deductions', 'log.activity'])->name('deductions.update');
                Route::delete('/deductions/destroy/{id}', [DeductionsController::class, 'destroy'])->middleware(['customcan:delete_deductions', 'demo_restriction', 'log.activity'])->name('deductions.destroy');
                Route::delete('/deductions/destroy_multiple', [DeductionsController::class, 'destroy_multiple'])->middleware(['customcan:delete_deductions', 'demo_restriction', 'log.activity'])->name('deductions.destroy_multiple');
            });
            Route::get('/time-tracker', [TimeTrackerController::class, 'index'])->middleware(['customcan:manage_timesheet'])->name('time_tracker.index');
            Route::post('/time-tracker/store', [TimeTrackerController::class, 'store'])->middleware(['customcan:create_timesheet', 'log.activity'])->name('time_tracker.store');
            Route::post('/time-tracker/update', [TimeTrackerController::class, 'update'])->middleware('log.activity')->name('time_tracker.update');
            Route::get('/time-tracker/list', [TimeTrackerController::class, 'list'])->middleware(['customcan:manage_timesheet'])->name('time_tracker.list');
            Route::delete('/time-tracker/destroy/{id}', [TimeTrackerController::class, 'destroy'])->middleware(['customcan:delete_timesheet', 'log.activity'])->name('time_tracker.destroy');
            Route::delete('/time-tracker/destroy_multiple', [TimeTrackerController::class, 'destroy_multiple'])->middleware(['customcan:delete_timesheet', 'log.activity'])->name('time_tracker.destroy_multiple');
            Route::middleware(['customcan:manage_activity_log'])->group(function () {
                Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity_log.index');
                Route::get('/activity-log/list', [ActivityLogController::class, 'list'])->name('activity_log.list');
                Route::delete('/activity-log/destroy/{id}', [ActivityLogController::class, 'destroy'])->middleware(['demo_restriction', 'customcan:delete_activity_log'])->name('activity_log.destroy');
                Route::delete('/activity-log/destroy_multiple', [ActivityLogController::class, 'destroy_multiple'])->middleware(['demo_restriction', 'customcan:delete_activity_log'])->name('activity_log.destroy_multiple');
            });
            Route::middleware(['customcan:manage_estimates_invoices'])->group(function () {
                Route::get('/estimates-invoices', [EstimatesInvoicesController::class, 'index'])->name('estimates-invoices.index');
                Route::get('/estimates-invoices/create', [EstimatesInvoicesController::class, 'create'])->middleware(['customcan:create_estimates_invoices'])->name('estimates-invoices.create');
                Route::post('/estimates-invoices/store', [EstimatesInvoicesController::class, 'store'])->middleware(['customcan:create_estimates_invoices', 'log.activity'])->name('estimates-invoices.store');
                Route::get('/estimates-invoices/list', [EstimatesInvoicesController::class, 'list'])->name('estimates-invoices.list');
                Route::get('/estimates-invoices/edit/{id}', [EstimatesInvoicesController::class, 'edit'])->middleware(['customcan:edit_estimates_invoices', 'checkAccess:App\Models\EstimatesInvoice,estimates_invoices,id,estimates_invoices'])->name('estimates-invoices.edit');
                Route::get('/estimates-invoices/view/{id}', [EstimatesInvoicesController::class, 'view'])->middleware(['checkAccess:App\Models\EstimatesInvoice,estimates_invoices,id,estimates_invoices'])->name('estimates-invoices.view');
                Route::get('/estimates-invoices/pdf/{id}', [EstimatesInvoicesController::class, 'pdf'])->middleware(['checkAccess:App\Models\EstimatesInvoice,estimates_invoices,id,estimates_invoices'])->name('estimates-invoice.pdf');
                Route::post('/estimates-invoices/update', [EstimatesInvoicesController::class, 'update'])->middleware(['customcan:edit_estimates_invoices', 'checkAccess:App\Models\EstimatesInvoice,estimates_invoices,id,estimates_invoices', 'log.activity'])->name('estimates-invoices.update');
                Route::get('/estimates-invoices/duplicate/{id}', [EstimatesInvoicesController::class, 'duplicate'])->middleware(['customcan:create_estimates_invoices', 'checkAccess:App\Models\EstimatesInvoice,EstimatesInvoice,id,estimates_invoices', 'log.activity'])->name('estimates-invoices.duplicate');
                Route::delete('/estimates-invoices/destroy/{id}', [EstimatesInvoicesController::class, 'destroy'])->middleware(['demo_restriction', 'customcan:delete_estimates_invoices', 'checkAccess:App\Models\EstimatesInvoice,estimates_invoices,id,estimates_invoices', 'log.activity'])->name('estimates-invoices.destroy');
                Route::delete('/estimates-invoices/destroy_multiple', [EstimatesInvoicesController::class, 'destroy_multiple'])->middleware(['demo_restriction', 'customcan:delete_estimates_invoices', 'log.activity'])->name('estimates-invoices.destroy_multiple');
            });
            //<--------------PaymentMethods------------>
            Route::middleware(['customcan:manage_payment_methods'])->group(function () {
                Route::get('/payment-methods', [PaymentMethodsController::class, 'index'])->name('paymentMethods.index');
                Route::post('/payment-methods/store', [PaymentMethodsController::class, 'store'])->middleware(['customcan:create_payment_methods', 'log.activity'])->name('paymentMethods.store');
                Route::get('/payment-methods/list', [PaymentMethodsController::class, 'list'])->name('paymentMethods.list');
                Route::get('/payment-methods/get/{id}', [PaymentMethodsController::class, 'get'])->name('paymentMethods.get');
                Route::post('/payment-methods/update', [PaymentMethodsController::class, 'update'])->middleware(['customcan:edit_payment_methods', 'log.activity'])->name('paymentMethods.update');
                Route::delete('/payment-methods/destroy/{id}', [PaymentMethodsController::class, 'destroy'])->middleware(['customcan:delete_payment_methods', 'demo_restriction', 'log.activity'])->name('paymentMethods.destroy');
                Route::delete('/payment-methods/destroy_multiple', [PaymentMethodsController::class, 'destroy_multiple'])->middleware(['customcan:delete_payment_methods', 'demo_restriction', 'log.activity'])->name('paymentMethods.destroy_multiple');
            });
            //<--------------------Payments------------------------>
            Route::middleware(['customcan:manage_payments'])->group(function () {

                Route::get('/payments', [PaymentsController::class, 'index'])->name('payments.index');
                Route::post('/payments/store', [PaymentsController::class, 'store'])->middleware(['customcan:create_payments', 'log.activity'])->name('payments.store');
                Route::get('/payments/list', [PaymentsController::class, 'list'])->name('payments.list');
                Route::get('/payments/get/{id}', [PaymentsController::class, 'get'])->middleware(['checkAccess:App\Models\Payment,payments,id'])->name('payments.get');
                Route::post('/payments/update', [PaymentsController::class, 'update'])->middleware(['customcan:edit_payments', 'log.activity'])->name('payments.update');
                Route::get('/payments/duplicate/{id}', [PaymentsController::class, 'duplicate'])->middleware(['customcan:create_expenses', 'checkAccess:App\Models\Expense,expenses,id,expenses', 'log.activity'])->name('payments.duplicate');
                Route::delete('/payments/destroy/{id}', [PaymentsController::class, 'destroy'])->middleware(['customcan:delete_payments', 'demo_restriction', 'log.activity'])->name('payments.destroy');
                Route::delete('/payments/destroy_multiple', [PaymentsController::class, 'destroy_multiple'])->middleware(['customcan:delete_payments', 'demo_restriction', 'log.activity'])->name('payments.destroy_multiple');
            });
            //<-------- Taxes------------>>>
            Route::middleware(['customcan:manage_taxes'])->group(function () {

                Route::get('/taxes', [TaxesController::class, 'index'])->name('taxes.index');
                Route::post('/taxes/store', [TaxesController::class, 'store'])->middleware(['customcan:create_taxes', 'log.activity'])->name('taxes.store');
                Route::get('/taxes/get/{id}', [TaxesController::class, 'get'])->name('taxes.get');
                Route::get('/taxes/list', [TaxesController::class, 'list'])->name('taxes.list');
                Route::post('/taxes/update', [TaxesController::class, 'update'])->middleware(['customcan:edit_taxes', 'log.activity'])->name('taxes.update');
                Route::delete('/taxes/destroy/{id}', [TaxesController::class, 'destroy'])->middleware(['customcan:delete_taxes', 'demo_restriction', 'log.activity'])->name('taxes.destroy');
                Route::delete('/taxes/destroy_multiple', [TaxesController::class, 'destroy_multiple'])->middleware(['customcan:delete_taxes', 'demo_restriction', 'log.activity'])->name('taxes.destroy_multiple');
            });
            //<<<<----------------Units---------------->>>
            Route::middleware(['customcan:manage_units'])->group(function () {
                Route::get('/units', [UnitsController::class, 'index'])->name('units.index');
                Route::post('/units/store', [UnitsController::class, 'store'])->middleware(['customcan:create_units', 'log.activity'])->name('units.store');
                Route::get('/units/get/{id}', [UnitsController::class, 'get'])->name('units.get');
                Route::get('/units/list', [UnitsController::class, 'list'])->name('units.list');
                Route::post('/units/update', [UnitsController::class, 'update'])->middleware(['customcan:edit_units', 'log.activity'])->name('units.update');
                Route::delete('/units/destroy/{id}', [UnitsController::class, 'destroy'])->middleware(['customcan:delete_units', 'demo_restriction', 'log.activity'])->name('units.destroy');
                Route::delete('/units/destroy_multiple', [UnitsController::class, 'destroy_multiple'])->middleware(['customcan:delete_units', 'demo_restriction', 'log.activity'])->name('units.destroy_multiple');
            });
            //<-------- Items -------------------------------->>
            Route::middleware(['customcan:manage_items'])->group(function () {
                Route::get('/items', [ItemsController::class, 'index'])->name('items.index');
                Route::post('/items/store', [ItemsController::class, 'store'])->middleware(['customcan:create_items', 'log.activity'])->name('items.store');
                Route::get('/items/get/{id}', [ItemsController::class, 'get'])->name('items.get');
                Route::get('/items/list', [ItemsController::class, 'list'])->name('items.list');
                Route::post('/items/update', [ItemsController::class, 'update'])->middleware(['customcan:edit_items', 'log.activity'])->name('items.update');
                Route::delete('/items/destroy/{id}', [ItemsController::class, 'destroy'])->middleware(['customcan:delete_items', 'demo_restriction', 'log.activity'])->name('items.destroy');
                Route::delete('/items/destroy_multiple', [ItemsController::class, 'destroy_multiple'])->middleware(['customcan:delete_items', 'demo_restriction', 'log.activity'])->name('items.destroy_multiple');
            });
            //<<<-------------Expenses------------------------>>
            Route::middleware(['customcan:manage_expenses'])->group(function () {
                Route::get('/expenses', [ExpensesController::class, 'index'])->name('expenses.index');
                Route::post('/expenses/store', [ExpensesController::class, 'store'])->middleware(['customcan:create_expenses', 'log.activity'])->name('expenses.store');
                Route::get('/expenses/list', [ExpensesController::class, 'list'])->name('expenses.list');
                Route::get('/expenses/get/{id}', [ExpensesController::class, 'get'])->name('expenses.get');
                Route::post('/expenses/update', [ExpensesController::class, 'update'])->middleware(['customcan:edit_expenses', 'log.activity'])->name('expenses.update');
                Route::get('/expenses/duplicate/{id}', [ExpensesController::class, 'duplicate'])->middleware(['customcan:create_expenses', 'checkAccess:App\Models\Expense,expenses,id,expenses', 'log.activity'])->name('expenses.duplicate');
                Route::delete('/expenses/destroy/{id}', [ExpensesController::class, 'destroy'])->middleware(['customcan:delete_expenses', 'demo_restriction', 'checkAccess:App\Models\Expense,expenses,id,expenses', 'log.activity'])->name('expenses.destroy');
                Route::delete('/expenses/destroy_multiple', [ExpensesController::class, 'destroy_multiple'])->middleware(['customcan:delete_expenses', 'demo_restriction', 'log.activity'])->name('expenses.destroy_multiple');
                //<<<---------Expenses Type-------------------------------->>>>
            });

            Route::middleware(['customcan:manage_expense_types'])->group(function () {
                Route::get('/expenses/expense-types', [ExpensesController::class, 'expense_types'])->name('expenses-type.index');
                Route::post('/expenses/store-expense-type', [ExpensesController::class, 'store_expense_type'])->middleware(['customcan:create_expense_types', 'log.activity'])->name('expenses-type.store');
                Route::get('/expenses/expense-types-list', [ExpensesController::class, 'expense_types_list'])->name('expenses-type.list');
                Route::get('/expenses/get-expense-type/{id}', [ExpensesController::class, 'get_expense_type'])->name('expenses-type.get');
                Route::post('/expenses/update-expense-type', [ExpensesController::class, 'update_expense_type'])->middleware(['customcan:edit_expense_types', 'log.activity'])->name('expenses-type.update');
                Route::delete('/expenses/delete-expense-type/{id}', [ExpensesController::class, 'delete_expense_type'])->middleware(['customcan:delete_system_notifications', 'demo_restriction'])->name('expenses-type.destroy');
                Route::delete('/expenses/delete-multiple-expense-type', [ExpensesController::class, 'delete_multiple_expense_type'])->middleware(['customcan:delete_system_notifications', 'demo_restriction'])->name('expenses-type.destroy_multiple');
            });

            Route::middleware(['customcan:manage_system_notifications'])->group(function () {
                Route::put('/notifications/mark-all-as-read', [NotificationsController::class, 'mark_all_as_read'])->name('notifications.mark_all_as_read');
                Route::get('/notifications', [NotificationsController::class, 'index'])->name('notifications.index');
                Route::get('/notifications/list', [NotificationsController::class, 'list'])->name('notifications.list');
                Route::delete('/notifications/destroy/{id}', [NotificationsController::class, 'destroy'])->middleware(['customcan:delete_system_notifications', 'demo_restriction'])->name('notifications.destroy');
                Route::delete('/notifications/destroy_multiple', [NotificationsController::class, 'destroy_multiple'])->middleware(['customcan:delete_system_notifications', 'demo_restriction'])->name('notifications.destroy_multiple');
                Route::put('/notifications/update-status', [NotificationsController::class, 'update_status'])->name('notifications.update_status');
                Route::get('/notifications/get-unread-notifications', [NotificationsController::class, 'getUnreadNotifications'])->middleware(['customcan:manage_system_notifications'])->name('notifications.getUnreadNotifications');
            });
            // User Prefrence
            Route::post('/save-column-visibility', [PreferenceController::class, 'saveColumnVisibility']);
            Route::post('/save-menu-order', [PreferenceController::class, 'saveMenuOrder']);
            Route::delete('/reset-default-menu-order', [PreferenceController::class, 'resetDefaultMenuOrder']);

            Route::get('/preferences', [PreferenceController::class, 'index'])->name('preferences.index');
            Route::post('/save-notification-preferences', [PreferenceController::class, 'saveNotificationPreferences'])->name('preferences.saveNotifications');

            Route::prefix('/reports')->group(function () {
                // Projects Report

                Route::get('/projects-report', [ReportsController::class, 'showProjectReport'])->name('reports.projects-report');
                Route::get('/projects-report-data', [ReportsController::class, 'getProjectReportData'])->name('reports.project-report-data');
                Route::get('/export-projects-report', [ReportsController::class, 'exportProjectReport'])->name('reports.export-projects-report');

                // Tasks Report
                Route::get('/tasks-report', [ReportsController::class, 'showTaskReport'])->name('reports.tasks-report');
                Route::get('/tasks-report-data', [ReportsController::class, 'getTaskReportData'])->name('reports.tasks-report-data');
                Route::get('/export-tasks-report', [ReportsController::class, 'exportTaskReport'])->name('reports.export-tasks-report');

                //Invoices Report

                Route::get('/invoices-report', [ReportsController::class, 'showInvoicesReport'])->name('reports.invoices-report');
                Route::get('/invoices-report-data', [ReportsController::class, 'getInvoicesReportData'])->name('reports.invoices-report-data');
                Route::get('/export-invoices-report', [ReportsController::class, 'exportInvoicesReport'])->name('reports.export-invoices-report');

                //Leaves Report

                Route::get('/leaves-report', [ReportsController::class, 'showLeavesReport'])->name('reports.leaves-report');
                Route::get('/leaves-report-data', [ReportsController::class, 'getLeavesReportData'])->name('reports.leaves-report-data');
                Route::get('/export-leaves-report', [ReportsController::class, 'exportLeavesReport'])->name('reports.export-leaves-report');

                //Income Vs Expense Report

                Route::get('/income-vs-expense-report', [ReportsController::class, 'showIncomeVsExpenseReport'])->name('reports.income-vs-expense-report');
                Route::get('/income-vs-expense-report-data', [ReportsController::class, 'getIncomeVsExpenseReportData'])->name('reports.income-vs-expense-report-data');
                Route::get('/export-income-vs-expense-report', [ReportsController::class, 'exportIncomeVsExpenseReport'])->name('reports.export-income-vs-expense-report');

                // Work Hours Report
                Route::get('/work-hours-report', [ReportsController::class, 'showWorkHoursReport'])->name('reports.work-hours-report');
                Route::get('/work-hours-report-data', [ReportsController::class, 'getWorkHoursReportData'])->name('reports.work-hours-report-data');
                Route::get('/export-work-hours-report', [ReportsController::class, 'exportWorkHoursReport'])->name('reports.export-work-hours-report');
            });
        });
        // <------------------------- Master Panel Settings --------------------------------->
        Route::prefix('/settings')->middleware(['customRole:admin', 'demo_restriction'])->group(function () {
            Route::get('/', [SettingsController::class, 'admin_settings'])->name('admin_settings.index');
            Route::put('/update', [SettingsController::class, 'update_admin_settings'])->name('admin_settings.update');
            Route::get('/whatsapp-notifications-settings', [SettingsController::class, 'admin_whatsapp_notifications_settings'])->name('admin_whatsapp_notifications_settings.index');
            Route::put('/update-admin-whatsapp-notifications-settings', [SettingsController::class, 'update_admin_whatsapp_notifications_settings'])->name('admin_whatsapp_notifications_settings.update');
        });


        // search routes
        Route::get('/status/search', [StatusController::class, 'search'])->name('status.search');
        Route::get('/clients/search-clients', [ClientController::class, 'searchClients'])->name('clients.searchClients');
        Route::get('tasks/search-projects', [TasksController::class, 'search_projects'])->name('tasks.search_projects');
        Route::get('/priority/search', [PriorityController::class, 'search'])->name('priority.search');
        Route::get('/users/search-users', [UserController::class, 'searchUsers'])->name('users.searchUsers');
        Route::get('/payments/search-invoices', [PaymentsController::class, 'searchInvoices'])->name('payments.searchInvoices');
        Route::get('/home/income-vs-expense-data', [HomeController::class, 'income_vs_expense_data'])->name('home.income_vs_expense_data');

        // Issue Routes
        Route::prefix('/projects/{project}')->middleware(['has_workspace', 'customcan:manage_projects'])->group(function () {
            Route::get('/issues', [IssueController::class, 'index'])->name('issues.index');
            Route::get('/issues/list', [IssueController::class, 'list'])->name('issues.list');
            Route::post('/issues/store', [IssueController::class, 'store'])->name('issues.store');
            Route::get('/issues/create', [IssueController::class, 'create'])->name('issues.create');
            Route::get('/issues/{issue}/edit', [IssueController::class, 'edit'])->name('issues.edit');
            Route::put('/issues/update', [IssueController::class, 'update'])->name('issues.update');
            Route::delete('/issues/destroy/{id}', [IssueController::class, 'destroy'])->name('issues.destroy');
        });
        Route::delete('/projects/issues/destroy_multiple', [IssueController::class, 'destroy_multiple'])->name('issues.destroy_multiple');

        // Announcements Routes

        Route::prefix('/announcements')->middleware(['has_workspace', 'customcan:manage_announcements'])->group(function () {
            Route::get('/', [AnnouncementController::class, 'index'])->name('announcements.index');
            Route::get('/list', [AnnouncementController::class, 'list'])->name('announcements.list');
            Route::post('/store', [AnnouncementController::class, 'store'])->name('announcements.store')->middleware('customcan:create_announcements');
            Route::get('/create', [AnnouncementController::class, 'create'])->name('announcements.create');
            Route::get('/{announcement}/edit', [AnnouncementController::class, 'edit'])->name('announcements.edit')->middleware('customcan:edit_announcements');
            Route::put('/update', [AnnouncementController::class, 'update'])->name('announcements.update')->middleware('customcan:edit_announcements');
            Route::delete('/destroy/{id}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy')->middleware('customcan:delete_announcements');
            Route::get('/events', [AnnouncementController::class, 'getEvents'])
            ->name('announcements.events');
            Route::get('/get-unread-announcements', [AnnouncementController::class, 'getUnreadAnnouncements'])->name('announcements.getUnreadAnnouncements');
            Route::put('/update-status', [AnnouncementController::class, 'update_status'])->name('announcements.update_status');
            Route::put('/mark-all-as-read', [AnnouncementController::class, 'mark_all_as_read'])->name('announcements.mark_all_as_read');
        });

        Route::prefix('/task-lists')->group(function () {
            Route::get('/', [TaskListController::class, 'index'])->name('task_lists.index');
            Route::get('/list', [TaskListController::class, 'list'])->name('task_lists.list');
            Route::post('/store', [TaskListController::class, 'store'])->name('task_lists.store');
            Route::get('/get/{id}', [TaskListController::class, 'get'])->name('task_lists.get');
            Route::put('/update', [TaskListController::class, 'update'])->name('task_lists.update');
            Route::delete('/destroy/{id}', [TaskListController::class, 'destroy'])->name('task_lists.destroy');
            Route::delete('/destroy_multiple', [TaskListController::class, 'destroy_multiple'])->name('task_lists.destroy_multiple');
        });
    });
});
// <-------------------------- Super Admin Routes -------------------->
Route::prefix('superadmin')->middleware('checkSuperadmin')->group(function () {

    // Define your superadmin routes here
    Route::get('/home', [SuperAdminHomeController::class, 'index'])->name('superadmin.panel');
    Route::get('/account/{user}', [ProfileController::class, 'show'])->name('profile_superadmin.show');
    Route::put('/profile/update_photo/{userOrClient}', [ProfileController::class, 'update_photo'])->name('superadmin.profile.update_photo');
    Route::put('profile/update/{userOrClient}', [ProfileController::class, 'update'])->name('superadmin.profile.update')->middleware(['demo_restriction']);
    // Add more routes as needed
    Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');
    Route::get('/plans/create', [PlanController::class, 'create'])->name('plans.create');
    Route::post('/plans/store', [PlanController::class, 'store'])->name('plans.store');
    Route::get('/plans/list', [PlanController::class, 'list'])->name('plans.list');
    Route::get('/plans/edit/{id}', [PlanController::class, 'edit'])->name('plans.edit')->middleware('demo_restriction');
    Route::post('/plans/update/{id}', [PlanController::class, 'update'])->name('plans.update')->middleware('demo_restriction');
    Route::delete('/plans/destroy/{id}', [PlanController::class, 'destroy'])->name('plans.destroy')->middleware('demo_restriction');
    Route::delete('/plans/destroy_multiple', [PlanController::class, 'destroy_multiple'])->name('plans.destroy_multiple')->middleware('demo_restriction');
    //Subscriptions
    Route::get('/subscriptions/list', [SubscriptionController::class, 'list'])->name('subscriptions.list');
    Route::get('/subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('/subscriptions/create', [SubscriptionController::class, 'create'])->name('subscriptions.create');
    Route::post('/subscriptions/store', [SubscriptionController::class, 'store'])->name('subscriptions.store');
    Route::delete('/subscriptions/destroy/{id}', [SubscriptionController::class, 'destroy'])->name('subscriptions.destroy')->middleware('demo_restriction');
    Route::get('/subscriptions/edit/{id}', [SubscriptionController::class, 'edit'])->name('subscriptions.edit')->middleware('demo_restriction');
    Route::delete('/subscriptions/destroy_multiple', [SubscriptionController::class, 'destroy_multiple'])->name('subscriptions.destroy_multiple')->middleware('demo_restriction');
    Route::post('/subscriptions/update/{id}', [SubscriptionController::class, 'update'])->name('subscriptions.update')->middleware('demo_restriction');
    Route::get('/subscriptions/get/{id}', [SubscriptionController::class, 'get'])->name('subscriptions.get');
    Route::get('/subscriptions/{subscription}/documents', [SubscriptionController::class, 'fetchDocuments']);
    Route::post('/subscriptions/{subscription}/verify-payment', [SubscriptionController::class, 'verifyPayment'])->name('subscriptions.verify-payment');
    //customers
    Route::get('/customers', [
        CustomerController::class,
        'index'
    ])->name('customers.index');
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers/store', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/edit/{id}', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::post('/customers/update/{id}', [CustomerController::class, 'update'])->name('customers.update');
    Route::get('/customers/list', [CustomerController::class, 'list'])->name('customers.list');
    Route::delete('/customers/destroy_multiple/', [CustomerController::class, 'destroy_multiple'])->name('customers.destroy_multiple')->middleware('demo_restriction');
    Route::delete('/customers/destroy/{id}', [
        CustomerController::class,
        'destroy'
    ])->middleware(['demo_restriction', 'log.activity'])->name('customers.destroy');
    //transactions
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/list', [TransactionController::class, 'list'])->name('transactions.list');
    Route::get('/plans', [
        PlanController::class,
        'index'
    ])->name('plans.index');
    //settings
    Route::middleware(['customRole:superadmin'])->group(function () {
        Route::get('/settings/permission/create', [RolesController::class, 'create_permission'])->name('roles.create_permission');
        Route::get('/settings/permission', [RolesController::class, 'index'])->name('roles.index');
        Route::delete('/roles/destroy/{id}', [RolesController::class, 'destroy'])->middleware(['demo_restriction'])->name('roles.destroy');
        Route::get('/roles/create', [RolesController::class, 'create'])->name('roles.create');
        Route::post('/roles/store', [RolesController::class, 'store'])->name('roles.store');
        Route::get('/roles/edit/{id}', [RolesController::class, 'edit'])->name('roles.edit');
        Route::put('/roles/update/{id}', [RolesController::class, 'update'])->name('roles.update');
        Route::get('/settings/general', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings/store_general', [SettingsController::class, 'store_general_settings'])->middleware(['demo_restriction'])->name('settings.store_general');
        Route::get('/settings/languages', [LanguageController::class, 'index'])->name('languages.index');
        Route::post('/settings/languages/store', [LanguageController::class, 'store'])->name('languages.store');
        Route::get("/settings/languages/manage", [LanguageController::class, 'manage'])->name('languages.manage');;
        Route::get("/settings/languages/list", [LanguageController::class, 'list'])->name('languages.list');;
        Route::get('/settings/languages/get/{id}', [LanguageController::class, 'get'])->name('languages.get');
        Route::post('/settings/languages/update', [LanguageController::class, 'update'])->middleware(['demo_restriction'])->name('languages.update');
        Route::delete("/settings/languages/destroy/{id}", [LanguageController::class, 'destroy'])->middleware(['demo_restriction']);
        Route::delete("/settings/languages/destroy_multiple", [LanguageController::class, 'destroy_multiple'])->middleware(['demo_restriction']);
        Route::get("settings/languages/change/{code}", [LanguageController::class, 'change'])->name('languages.change');
        Route::put("/settings/languages/save_labels", [LanguageController::class, 'save_labels'])->name('languages.save_labels');
        Route::get('/settings/email', [SettingsController::class, 'email'])->name('settings.email');
        Route::put('/settings/store_email', [SettingsController::class, 'store_email_settings'])->middleware(['demo_restriction'])->name('settings.store_email');
        Route::post('/settings/email/test-email-settings', [SettingsController::class, 'test_email_settings'])->name('settings.test_email_settings');
        Route::get('/settings/pusher', [SettingsController::class, 'pusher'])->name('settings.pusher');
        Route::put('/settings/store_pusher', [
            SettingsController::class,
            'store_pusher_settings'
        ])->middleware(['demo_restriction'])->name('settings.store_pusher');
        Route::get('/settings/media-storage', [SettingsController::class, 'media_storage'])->name('settings.media_storage');
        Route::put('/settings/store_media_storage', [SettingsController::class, 'store_media_storage_settings'])->middleware(['demo_restriction'])->name('settings.store_media_storage');
        Route::get('/settings/system-updater', [UpdaterController::class, 'index'])->name('update.index');
        Route::post('/settings/update-system', [UpdaterController::class, 'update'])->middleware(['demo_restriction'])->name('update.update');
        // Security settings
        Route::get('/settings/security', [SettingsController::class, 'security'])->name('security.index');
        Route::put('/settings/security/store', [SettingsController::class, 'store_security_settings'])->middleware(['demo_restriction'])->name('settings.security.store');
        //  <-------------------- Payment Methods Settings -------------------------------->
        Route::get('/settings/payment-methods', [PaymentMethodController::class, 'index'])->name('payment_method.index');
        Route::put('/settings/payment-methods/store_paypal_settings', [PaymentMethodController::class, 'store_paypal_settings'])->name('payment_method.store_paypal_settings')->middleware('demo_restriction');
        Route::put('/settings/payment-methods/store_phonepe_settings', [PaymentMethodController::class, 'store_phonepe_settings'])->name('payment_method.store_phonepe_settings')->middleware('demo_restriction');
        Route::put('/settings/payment-methods/store_stripe_settings', [PaymentMethodController::class, 'store_stripe_settings'])->name('payment_method.store_stripe_settings')->middleware('demo_restriction');
        Route::put('/settings/payment-methods/store_paystack_settings', [PaymentMethodController::class, 'store_paystack_settings'])->name('payment_method.store_paystack_settings')->middleware('demo_restriction');
        Route::put('/settings/payment-methods/store_bank_transfer_settings', [PaymentMethodController::class, 'store_bank_transfer_settings'])->name('payment_method.store_bank_transfer_settings')->middleware('demo_restriction');

        // <---------------------Privacy Policy---------------------------------------------->
        Route::get('/settings/privacy-policy', [SettingsController::class, 'privacy_policy'])->name('privacy_policy.index');
        Route::put('/settings/privacy-policy/store', [SettingsController::class, 'store_privacy_policy'])->name('privacy_policy.store')->middleware('demo_restriction');
        // <---------------------Terms and Conditions---------------------------------------------->
        Route::get('/settings/terms-and-conditions', [SettingsController::class, 'terms_and_conditions'])->name('terms_and_conditions.index');
        Route::put('/settings/terms-and-conditions/store', [SettingsController::class, 'store_terms_and_conditions'])->name('terms_and_conditions.store')->middleware('demo_restriction');
        // <---------------------Refund Policy---------------------------------------------->
        Route::get('/settings/refund-policy', [SettingsController::class, 'refund_policy'])->name('refund_policy.index');
        Route::put('/settings/refund-policy/store', [SettingsController::class, 'store_refund_policy'])->name('refund_policy.store')->middleware('demo_restriction');
        // <---------------------SMS Gateway---------------------------------------------->
        Route::get('/settings/sms-gateway', [SettingsController::class, 'sms_gateway'])->name('sms_gateway.index');
        Route::put('/settings/store_sms_gateway', [SettingsController::class, 'store_sms_gateway_settings'])->middleware(['demo_restriction'])->name('sms_gateway.store');
        Route::put('/settings/store_whatsapp', [SettingsController::class, 'store_whatsapp_settings'])->middleware(['demo_restriction'])->name('whatsapp_settings.store');
        Route::put('/settings/store_slack', [SettingsController::class, 'store_slack_settings'])->middleware(['demo_restriction'])->name('slack_settings.store');
        // <---------------------Templates---------------------------------------------->
        Route::get('/settings/templates', [SettingsController::class, 'templates'])->name('templates.index');
        Route::put('/settings/store_template', [SettingsController::class, 'store_template'])->name('templates.store');
        Route::post('/settings/get-default-template', [SettingsController::class, 'get_default_template'])->name('templates.get_default_template');
        // Dashboard Charts


        // Additional Role For Super Admin  (Manager)

    });
    Route::get('home/getCustomerMonthlyCount', [SuperAdminHomeController::class, 'getCustomersMonthlyCount'])->name('chart.customer_monthly_count');
    Route::get('home/getRevenueData', [SuperAdminHomeController::class, 'getRevenueData'])->name('chart.revenue_data');

    Route::get('home/getSubscriptionRate', [SuperAdminHomeController::class, 'getSubscriptionRateChart'])->name('chart.subscription_rate');
    Route::get('home/getActiveSubscriptionPerPlan', [SuperAdminHomeController::class, 'getActiveSubscriptionsPerPlan'])->name('chart.activeSubscriptionPerPlan');
    Route::get('home/getBestCustomers', [SuperAdminHomeController::class, 'getBestCustomers'])->name('chart.bestCustomers');
    Route::get('home/getRecentTransactions', [SuperAdminHomeController::class, 'getRecentTransactions'])->name('chart.recentTransactions');
    Route::prefix('managers')->group(function () {
        Route::get('/', [ManagerController::class, 'index'])->name('managers.index');
        Route::get('/create', [ManagerController::class, 'create'])->name('managers.create');
        Route::post('/store', [ManagerController::class, 'store'])->name('managers.store');
        Route::get('/edit/{id}', [ManagerController::class, 'edit'])->name('managers.edit');
        Route::put('/update/{id}', [ManagerController::class, 'update'])->name('managers.update');
        Route::delete('/destroy/{id}', [ManagerController::class, 'destroy'])->name('managers.destroy');
        Route::get('/list', [ManagerController::class, 'list'])->name('managers.list');
    });
});
Route::middleware(['auth', 'customRole:admin|superadmin|manager'])->group(function () {
    // Resourceful routes for SupportController
    Route::resource('support', SupportController::class);
    Route::get('tickets/list', [SupportController::class, 'list'])->name('support.list');
    // Route for storing a reply
    Route::post('support/reply/store/{id}', [SupportController::class, 'storeReply'])
        ->name('reply.store');

    // Route for updating the status of a ticket
    Route::put('support/{ticket}/update-status', [SupportController::class, 'updateStatus'])
        ->name('support.update-status');
});

// <<<<--------------------------------- Webhook Urls -------------------------------->>>>>>>>
// <<<<-------------------------------- Paystack --------->>>>>>>>>
Route::any('/master-panel/subscription-plan/checkout/paystack-webhook', [SubscriptionPlan::class, 'paystack_webhook'])->name('paystack.webhook');
Route::any('/master-panel/subscription-plan/checkout/paystack-payment-success/', [SubscriptionPlan::class, 'paystack_payment_success'])->name('paystack.success');
Route::any('/master-panel/subscription-plan/checkout/paystack-payment-cancel', [SubscriptionPlan::class, 'paystack_payment_cancel'])->name('paystack.cancel');
// <<<-------------------------------- PhonePe ------>>>>>>>
Route::any('/master-panel/subscription-plan/checkout/phone_pe-webhook', [SubscriptionPlan::class, 'phone_pe_webhook'])->name('phone_pe_webhook');
Route::any('/master-panel/subscription-plan/checkout/phone_pe-redirect', [SubscriptionPlan::class, 'phone_pe_redirect'])->name('phone_pe_redirect');
// <<<-------------------------------- Stripe -------->>>>>>>
Route::any('/master-panel/subscription-plan/checkout/stripe-webhook', [SubscriptionPlan::class, 'stripe_webhook'])->name('stripe_webhook');
Route::any('/master-panel/subscription-plan/checkout/stripe-success', [SubscriptionPlan::class, 'stripe_success'])->name('stripe.success');
// <<<-------------------------------- Paypal -------->>>>>>>
Route::any('/master-panel/subscription-plan/checkout/paypal-success', [SubscriptionPlan::class, 'paypal_success'])->name('paypal.success');
Route::any('/master-panel/subscription-plan/checkout/paypal-webhook', [SubscriptionPlan::class, 'paypal_webhook'])->name('paypal.webhook');
Route::any('/master-panel/subcription-plan/checkout/payment_successful/{data}', [SubscriptionPlan::class, 'payment_success_view'])->name('payment_successful');
Route::get('/test-403', function () {
    throw new UnauthorizedException(403, 'Unauthorized Access');
});
Route::get('/test-404', function () {
    throw new NotFoundHttpException('Page not found.');
});
Route::get('/test-500', function () {
    throw new Exception('Internal Server Error.');
});
Route::get('/test-http-exception', function () {
    throw new HttpException(418, "I'm a teapot.");
});
Route::post('/clear-cache', function () {
    Artisan::call('optimize:clear');
    return back()->with('message', 'Cache cleared successfully!');
})->name('clear.cache');
Route::get('/clear-permission-cache', function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    return 'Permissions cache cleared';
});

