<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\application\ApplicationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\notifications\NotificationController;
use App\Http\Controllers\reports\ReportsController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\company\ApplicationsCompanyController;
use App\Http\Controllers\company\DashboardCompanyController;
use App\Http\Controllers\company\JobController;
use App\Http\Controllers\company\CompanyTrelloController;
use App\Http\Controllers\company\ProgramsCompanyController;
use App\Http\Controllers\student\StudentController;
use App\Http\Controllers\student\SkillTestController;
use App\Http\Controllers\student\JisrController;
use App\Http\Controllers\supervisior\SupervisorApplicationsController;
use App\Http\Controllers\supervisior\SupervisorController;
use App\Http\Controllers\tasks\TrelloController;
use App\Http\Controllers\tasks\TrainingTaskController;
use App\Models\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::view('/login', 'spa')->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.process');
Route::view('/register', 'auth.register')->name('register');
Route::post('/register', [RegisterController::class, 'register'])->name('register.process');

Route::prefix('trello')->group(function () {
    Route::get('/', [TrelloController::class, 'index']);
    Route::post('/card', [TrelloController::class, 'createCard']);
});

Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::view('/board', 'board')->name('board');

    Route::get('/training-complete/{application}', function (Application $application) {
        $user = Auth::user();
        abort_if(! $user, 403);

        abort_unless(
            $user->id === $application->student_id ||
            ($user->role === 'company' && optional($application->opportunity)->company_user_id === $user->id) ||
            ($user->role === 'supervisor' && optional($application->student)->supervisor_code === $user->supervisor_code),
            403
        );
        abort_unless($application->training_completed_at, 404);

        return view('training-complete', ['application' => $application]);
    })->name('training.complete');

    Route::get('/admin/dashboard', [AdminController::class, 'adminDashboard'])->name('admin.dashboard');
    Route::get('/admin/users', [AdminController::class, 'adminUsersPage'])->name('admin.users');
    Route::get('/admin/companies', [AdminController::class, 'adminCompaniesPage'])->name('admin.companies');
    Route::view('/admin/reports', 'spa')->name('admin.reports');
    Route::get('/admin/reports/data', [ReportsController::class, 'admin'])->name('admin.reports.data');
    Route::view('/admin/add-supervisor', 'spa')->name('admin.add-supervisor');
    Route::post('/admin/supervisors', [AdminController::class, 'createSupervisor'])->name('admin.supervisors.store');
    Route::post('/admin/supervisor/{id}', [AdminController::class, 'updateSupervisorStatus'])->name('admin.supervisor.update');
    Route::post('/admin/company/{id}', [AdminController::class, 'updateCompanyStatus'])->name('admin.company.update');
    Route::post('/admin/supervisors/{id}/status', [AdminController::class, 'updateSupervisorStatus']);
    Route::post('/admin/companies/{id}/status', [AdminController::class, 'updateCompanyStatus']);

    Route::get('/company/dashboard', [DashboardCompanyController::class, 'dashboard'])->name('company.dashboard');
    Route::get('/company/programs', [ProgramsCompanyController::class, 'programsPage'])->name('company.programs.index');
    Route::get('/company/programs/create', [ProgramsCompanyController::class, 'createProgramPage'])->name('company.programs.create');
    Route::get('/company/programs/{id}', [ProgramsCompanyController::class, 'show'])->name('company.programs.show');
    Route::get('/company/programs/{id}/edit', [ProgramsCompanyController::class, 'editProgramPage'])->name('company.programs.edit');
    Route::post('/company/createJob', [JobController::class, 'createJob'])->name('company.jobs.create');
    Route::post('/company/jobs/{id}', [JobController::class, 'updateJob'])->name('company.jobs.update');
    Route::delete('/company/jobs/{id}', [JobController::class, 'deleteJob'])->name('company.jobs.delete');
    Route::post('/company/programs', [JobController::class, 'createJob'])->name('company.programs.store');
    Route::post('/company/programs/{id}', [JobController::class, 'updateJob'])->name('company.programs.update');
    Route::post('/company/programs/{id}/delete', [JobController::class, 'deleteJob'])->name('company.programs.delete');
    Route::get('/company/applicants', [ApplicationsCompanyController::class, 'applicantsPage'])->name('company.applicants.index');
    Route::get('/company/applicants/{id}', [ApplicationsCompanyController::class, 'applicantDetails'])->name('company.applicants.show');
    Route::view('/company/reports', 'spa')->name('company.reports');
    Route::get('/company/reports/data', [ReportsController::class, 'company'])->name('company.reports.data');
    Route::view('/company/trello-settings', 'spa')->name('company.trello-settings');
    Route::get('/company/trello/settings', [CompanyTrelloController::class, 'settings'])->name('company.trello.settings');
    Route::post('/company/trello/settings', [CompanyTrelloController::class, 'saveSettings'])->name('company.trello.settings.save');
    Route::post('/company/trello/test', [CompanyTrelloController::class, 'testConnection'])->name('company.trello.test');
    Route::get('/company/trello/boards', [CompanyTrelloController::class, 'boards'])->name('company.trello.boards');
    Route::get('/company/trello/boards/{boardId}/lists', [CompanyTrelloController::class, 'lists'])->name('company.trello.lists');
    Route::get('/company/trello/integrations', [CompanyTrelloController::class, 'integrations'])->name('company.trello.integrations');
    Route::post('/company/trello/internships/{internshipId}/connect', [CompanyTrelloController::class, 'connectBoard'])->name('company.trello.connect');
    Route::post('/company/trello/internships/{internshipId}/sync', [CompanyTrelloController::class, 'syncInternship'])->name('company.trello.sync');
    Route::delete('/company/trello/internships/{internshipId}/unlink', [CompanyTrelloController::class, 'unlinkInternship'])->name('company.trello.unlink');
    Route::delete('/company/trello/disconnect', [CompanyTrelloController::class, 'disconnect'])->name('company.trello.disconnect');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
    Route::post('/notifications/{id}/mark-read', [NotificationController::class, 'markRead'])->name('notifications.mark-read');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.delete');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/change-password', [ProfileController::class, 'changePassword'])->name('profile.change-password');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/company/applications/{id}/approve', [ApplicationController::class, 'companyApplicationApprove'])->name('company.applications.approve');
    Route::post('/company/applications/{id}/reject', [ApplicationController::class, 'companyApplicationReject'])->name('company.applications.reject');
    Route::post('/company/applications/{id}/complete-training', [ApplicationController::class, 'companyCompleteTraining'])->name('company.applications.complete-training');
    Route::get('/applications/{application}/cv', [ApplicationController::class, 'downloadCv'])->name('applications.cv');

    Route::get('/student/dashboard', [StudentController::class, 'dashboard'])->name('student.dashboard');
    Route::get('/student/programs', [StudentController::class, 'programs'])->name('student.programs.index');
    Route::get('/student/browse-programs', [StudentController::class, 'programs'])->name('student.programs.browse');
    Route::get('/student/programs/{id}', [StudentController::class, 'programShow'])->name('student.programs.show');
    Route::get('/student/program/{id}', [StudentController::class, 'programShow'])->name('student.program.view');
    Route::post('/student/applications/apply', [ApplicationController::class, 'applyApplication'])->name('student.applications.apply');
    Route::get('/student/applications', [StudentController::class, 'applications'])->name('student.applications.index');
    Route::get('/student/application-status', [StudentController::class, 'applications'])->name('student.applications.status');
    Route::get('/student/workspace', [StudentController::class, 'workspace'])->name('student.workspace.index');
    Route::view('/student/submit-task/{taskId}', 'spa')->name('student.submit-task');
    Route::view('/student/accreditation', 'spa')->name('student.accreditation');
    Route::view('/student/jisr', 'spa')->name('student.jisr');
    Route::view('/student/skill-test', 'spa')->name('student.skill-test');
    Route::get('/student/skill-test/data', [SkillTestController::class, 'show'])->name('student.skill-test.data');
    Route::post('/student/skill-test/submit', [SkillTestController::class, 'submit'])->name('student.skill-test.submit');
    Route::get('/student/jisr/tasks', [JisrController::class, 'tasks'])->name('student.jisr.tasks');
    Route::post('/student/jisr/tasks/{taskId}/submit', [JisrController::class, 'submit'])->name('student.jisr.tasks.submit');
    Route::post('/student/jisr/complete', [JisrController::class, 'complete'])->name('student.jisr.complete');

    Route::get('/supervisor/dashboard', [SupervisorController::class, 'dashboard'])->name('supervisor.dashboard');
    Route::get('/supervisor/dashboard/data', [SupervisorController::class, 'dashboardData'])->name('supervisor.dashboard.data');
    Route::get('/supervisor/students', [SupervisorController::class, 'studentsPage'])->name('supervisor.students.index');
    Route::get('/supervisor/student/{id}', [SupervisorController::class, 'studentDetails'])->name('supervisor.students.show');
    Route::get('/supervisor/students/pending', [SupervisorController::class, 'pendingStudentsPage'])->name('supervisor.students.pending');
    Route::post('/supervisor/students/{id}/approve', [SupervisorController::class, 'supervisorActiveStudentAcouunt'])->name('supervisor.students.approve');
    Route::post('/supervisor/students/{id}/reject', [SupervisorController::class, 'rejectActiveStudentAcouunt'])->name('supervisor.students.reject');
    Route::post('/supervisor/students/{id}/delete', [SupervisorController::class, 'deleteStudent'])->name('supervisor.students.delete');
    Route::get('/supervisor/jisr-reviews', [SupervisorController::class, 'jisrReviewsPage'])->name('supervisor.jisr-reviews');
    Route::get('/supervisor/jisr-reviews/data', [SupervisorController::class, 'jisrReviewsData'])->name('supervisor.jisr-reviews.data');
    Route::post('/supervisor/jisr-reviews/{id}/review', [SupervisorController::class, 'reviewJisrSubmission'])->name('supervisor.jisr-reviews.review');
    Route::get('/supervisor/jisr-reviews/{submission}/attachments/{index}', [SupervisorController::class, 'showJisrAttachment'])->name('supervisor.jisr-attachments.show');
    Route::get('/supervisor/jisr-reviews/{submission}/attachments/{index}/download', [SupervisorController::class, 'downloadJisrAttachment'])->name('supervisor.jisr-attachments.download');
    Route::get('/supervisor/applications', [SupervisorApplicationsController::class, 'applicationsPage'])->name('supervisor.applications.index');
    Route::get('/supervisor/applications/{id}', [SupervisorApplicationsController::class, 'show'])->name('supervisor.applications.show');
    Route::post('/supervisor/applications/{id}/approve', [ApplicationController::class, 'supervisorApplicationApprove'])->name('supervisor.applications.approve');
    Route::post('/supervisor/applications/{id}/reject', [ApplicationController::class, 'supervisorReject'])->name('supervisor.applications.reject');
    Route::post('/supervisor/applications/{id}/complete-training', [ApplicationController::class, 'supervisorCompleteTraining'])->name('supervisor.applications.complete-training');
    Route::get('/supervisor/weekly-tasks', [SupervisorController::class, 'weeklyTasksPage'])->name('supervisor.weekly-tasks');
    Route::view('/supervisor/reports', 'spa')->name('supervisor.reports');
    Route::get('/supervisor/reports/data', [ReportsController::class, 'supervisor'])->name('supervisor.reports.data');
    Route::view('/supervisor/evaluations', 'spa')->name('supervisor.evaluations');

    // Training Tasks Workflow (Role-based + Trello sync)
    Route::get('/workspace/tasks', [TrainingTaskController::class, 'workspace'])->name('tasks.workspace');
    Route::get('/admin/tasks/workspace', [TrainingTaskController::class, 'adminWorkspace'])->name('tasks.admin.workspace');
    Route::post('/admin/tasks/broadcast', [TrainingTaskController::class, 'adminBroadcastTask'])->name('tasks.admin.broadcast');
    Route::post('/supervisor/tasks/broadcast', [TrainingTaskController::class, 'supervisorBroadcastTask'])->name('tasks.supervisor.broadcast');
    Route::get('/applications/{application}/tasks', [TrainingTaskController::class, 'board'])->name('tasks.board');
    Route::post('/applications/{application}/tasks', [TrainingTaskController::class, 'createTask'])->name('tasks.create');
    Route::post('/applications/{application}/tasks/{task}/submit', [TrainingTaskController::class, 'submitSolution'])->name('tasks.submit');
    Route::post('/applications/{application}/tasks/{task}/comment', [TrainingTaskController::class, 'addComment'])->name('tasks.comment');
    Route::post('/applications/{application}/tasks/{task}/grade', [TrainingTaskController::class, 'gradeTask'])->name('tasks.grade');
});
