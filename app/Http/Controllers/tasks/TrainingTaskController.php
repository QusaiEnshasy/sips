<?php

namespace App\Http\Controllers\tasks;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Task;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\TrelloService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TrainingTaskController extends Controller
{
    public function __construct(
        private readonly TrelloService $trello,
        private readonly NotificationService $notifications
    ) {
    }

    public function workspace(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->role === 'supervisor') {
            return redirect()->route('supervisor.weekly-tasks');
        }

        $query = Application::query()
            ->where('company_status', 'approved')
            ->where('supervisor_status', 'approved')
            ->where('final_status', 'approved');

        if ($user->role === 'student') {
            $query->where('student_id', $user->id);
        } elseif ($user->role === 'company') {
            $query->whereHas('opportunity', fn (Builder $q) => $q->where('company_user_id', $user->id));
        } else {
            abort(403);
        }

        $application = $query->latest()->first();

        if (! $application) {
            return back()->with('error', 'لا يوجد تدريب مكتمل الموافقات بعد لعرض لوحة المهام.');
        }

        return redirect()->route('tasks.board', $application->id);
    }

    public function adminWorkspace(Request $request)
    {
        abort_unless($request->user()->role === 'admin', 403);

        $approvedApplications = Application::with(['student:id,name', 'opportunity:id,title'])
            ->where('company_status', 'approved')
            ->where('supervisor_status', 'approved')
            ->where('final_status', 'approved')
            ->latest()
            ->get();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'approved_applications' => $approvedApplications,
            ]);
        }

        return view('spa');
    }

    public function adminBroadcastTask(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'admin', 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:5000'],
            'due_date' => ['nullable', 'date'],
            'label' => ['nullable', 'in:red,green,blue'],
        ]);

        $applications = Application::with('student')
            ->where('company_status', 'approved')
            ->where('supervisor_status', 'approved')
            ->where('final_status', 'approved')
            ->whereNull('training_completed_at')
            ->get();

        foreach ($applications as $application) {
            $application->loadMissing('opportunity');
            if ($this->isTrainingEnded($application)) {
                continue;
            }

            $task = Task::create([
                'application_id' => $application->id,
                'created_by' => $request->user()->id,
                'title' => trim($validated['title']),
                'details' => $validated['details'] ?: null,
                'due_date' => $validated['due_date'] ?: null,
                'label' => $validated['label'] ?: null,
                'assigned_user' => $application->student?->name,
                'status' => 'todo',
                'order' => (Task::where('application_id', $application->id)->where('status', 'todo')->max('order') ?? 0) + 1,
            ]);

            $card = $this->trello->createCard(
                listId: $this->statusToTrelloListId('todo'),
                name: '[' . ($application->student?->name ?? 'Student') . '] ' . $task->title,
                desc: (string) ($task->details ?? '')
            );

            if (! empty($card['id'])) {
                $task->update(['trello_card_id' => $card['id']]);
            }

            if ($application->student_id) {
                $this->notifications->notifyUser(
                    userId: (int) $application->student_id,
                    title: 'New General Task',
                    description: 'تمت إضافة مهمة عامة جديدة من الإدارة.',
                    type: 'info',
                    meta: ['category' => 'task']
                );
            }
        }

        return back()->with('success', 'تم نشر المهمة العامة بنجاح.');
    }

    public function supervisorBroadcastTask(Request $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'supervisor', 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:5000'],
            'due_date' => ['nullable', 'date'],
            'label' => ['nullable', 'in:red,green,blue'],
        ]);

        $applications = Application::with('student')
            ->where('company_status', 'approved')
            ->where('supervisor_status', 'approved')
            ->where('final_status', 'approved')
            ->whereNull('training_completed_at')
            ->whereHas('student', function (Builder $query) use ($request) {
                $query->where('supervisor_code', $request->user()->supervisor_code);
            })
            ->get();

        if ($applications->isEmpty()) {
            return back()->with('error', 'لا يوجد طلاب مقبولون نهائيًا ضمن إشرافك.');
        }

        foreach ($applications as $application) {
            $application->loadMissing('opportunity');
            if ($this->isTrainingEnded($application)) {
                continue;
            }

            $task = Task::create([
                'application_id' => $application->id,
                'created_by' => $request->user()->id,
                'title' => trim($validated['title']),
                'details' => $validated['details'] ?: null,
                'due_date' => $validated['due_date'] ?: null,
                'label' => $validated['label'] ?: null,
                'assigned_user' => $application->student?->name,
                'status' => 'todo',
                'order' => (Task::where('application_id', $application->id)->where('status', 'todo')->max('order') ?? 0) + 1,
            ]);

            $card = $this->trello->createCard(
                listId: $this->statusToTrelloListId('todo'),
                name: '[' . ($application->student?->name ?? 'Student') . '] ' . $task->title,
                desc: (string) ($task->details ?? '')
            );

            if (! empty($card['id'])) {
                $task->update(['trello_card_id' => $card['id']]);
            }

            if ($application->student_id) {
                $this->notifications->notifyUser(
                    userId: (int) $application->student_id,
                    title: 'New General Task',
                    description: 'تمت إضافة مهمة عامة جديدة من المشرف.',
                    type: 'info',
                    meta: ['category' => 'task']
                );
            }
        }

        return back()->with('success', 'تم نشر المهمة العامة بنجاح.');
    }

    private function authorizeApplication(Request $request, Application $application): void
    {
        $user = $request->user();

        $allowed = (int) $application->student_id === (int) $user->id
            || ($user->role === 'company' && (int) optional($application->opportunity)->company_user_id === (int) $user->id)
            || ($user->role === 'supervisor' && optional($application->student)->supervisor_code === $user->supervisor_code)
            || ($user->role === 'admin');

        abort_unless($allowed, 403);

        abort_unless(
            $application->company_status === 'approved'
                && $application->supervisor_status === 'approved'
                && $application->final_status === 'approved',
            403,
            'يمكن إدارة المهام فقط بعد القبول النهائي من الشركة والمشرف.'
        );
    }

    private function statusToTrelloListId(string $status): string
    {
        return match ($status) {
            'todo' => (string) config('services.trello.todo_list_id', ''),
            'progress' => (string) config('services.trello.progress_list_id', ''),
            'done' => (string) config('services.trello.done_list_id', ''),
            default => '',
        };
    }

    private function ensureTrainingOpen(Application $application): void
    {
        abort_if($this->isTrainingEnded($application), 422, 'Training period ended. Tasks are read-only now.');
    }

    private function getTrainingEndDate(Application $application): ?\Illuminate\Support\Carbon
    {
        if (! $application->approved_at || ! $application->opportunity || (int) $application->opportunity->duration <= 0) {
            return null;
        }

        return $application->approved_at->copy()->addMonths((int) $application->opportunity->duration)->startOfDay();
    }

    private function isTrainingEnded(Application $application): bool
    {
        if ($application->training_completed_at) {
            return true;
        }

        $endDate = $this->getTrainingEndDate($application);
        if (! $endDate) {
            return false;
        }

        return now()->startOfDay()->greaterThanOrEqualTo($endDate);
    }

    public function board(Request $request, Application $application)
    {
        $application->load(['student', 'opportunity.companyUser']);
        $this->authorizeApplication($request, $application);

        $trainingEnded = $this->isTrainingEnded($application);
        $trainingEndDate = $this->getTrainingEndDate($application);

        if ($application->training_completed_at && $request->user()->role === 'student') {
            return redirect()->route('training.complete', $application->id);
        }

        $tasks = Task::with([
            'creator:id,name,role',
            'comments.user:id,name,role',
            'attachments.user:id,name,role',
        ])->where('application_id', $application->id)
            ->orderBy('order')
            ->orderBy('id')
            ->get();

        return view('tasks.board', [
            'application' => $application,
            'role' => $request->user()->role,
            'trainingEnded' => $trainingEnded,
            'trainingEndDate' => $trainingEndDate,
            'todoTasks' => $tasks->where('status', 'todo')->values(),
            'progressTasks' => $tasks->where('status', 'progress')->values(),
            'doneTasks' => $tasks->where('status', 'done')->values(),
        ]);
    }

    public function createTask(Request $request, Application $application): RedirectResponse
    {
        $this->authorizeApplication($request, $application);
        $this->ensureTrainingOpen($application);
        abort_unless(in_array($request->user()->role, ['company', 'supervisor', 'admin'], true), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:5000'],
            'due_date' => ['nullable', 'date'],
            'label' => ['nullable', 'in:red,green,blue'],
        ]);

        $task = Task::create([
            'application_id' => $application->id,
            'created_by' => $request->user()->id,
            'title' => trim($validated['title']),
            'details' => $validated['details'] ?: null,
            'due_date' => $validated['due_date'] ?: null,
            'label' => $validated['label'] ?: null,
            'assigned_user' => $application->student?->name,
            'status' => 'todo',
            'order' => (Task::where('application_id', $application->id)->where('status', 'todo')->max('order') ?? 0) + 1,
        ]);

        $card = $this->trello->createCard(
            listId: $this->statusToTrelloListId('todo'),
            name: $task->title,
            desc: (string) ($task->details ?? '')
        );

        if (! empty($card['id'])) {
            $task->update(['trello_card_id' => $card['id']]);
        }

        $this->notifications->notifyUser(
            userId: (int) $application->student_id,
            title: 'New Task Added',
            description: 'تمت إضافة مهمة جديدة على لوحة التدريب.',
            type: 'info',
            meta: ['category' => 'task']
        );

        return back()->with('success', 'تم إنشاء المهمة بنجاح.');
    }

    public function submitSolution(Request $request, Application $application, Task $task): RedirectResponse
    {
        $this->authorizeApplication($request, $application);
        $this->ensureTrainingOpen($application);
        abort_unless($request->user()->role === 'student', 403);
        abort_unless((int) $application->id === (int) $task->application_id, 404);

        $validated = $request->validate([
            'student_solution' => ['required', 'string', 'max:5000'],
            'status' => ['nullable', 'in:todo,progress,done'],
            'attachments.*' => ['nullable', 'file', 'max:10240'],
        ]);

        $task->student_solution = trim($validated['student_solution']);

        if (! empty($validated['status'])) {
            $task->status = $validated['status'];
        }

        $task->save();

        foreach ((array) $request->file('attachments', []) as $file) {
            $path = $file->store('attachments', 'public');
            $task->attachments()->create([
                'user_id' => $request->user()->id,
                'filename' => $file->getClientOriginalName(),
                'filepath' => $path,
            ]);
        }

        if ($task->trello_card_id) {
            $this->trello->updateCard($task->trello_card_id, [
                'desc' => "Solution:\n" . $task->student_solution,
            ]);

            $listId = $this->statusToTrelloListId($task->status);
            if ($listId !== '') {
                $this->trello->moveCard($task->trello_card_id, $listId);
            }
        }

        $recipients = [];
        $companyId = (int) optional($application->opportunity)->company_user_id;
        if ($companyId > 0) {
            $recipients[] = $companyId;
        }

        $supervisor = $application->student?->supervisor_code
            ? User::where('role', 'supervisor')->where('supervisor_code', $application->student->supervisor_code)->first()
            : null;

        if ($supervisor) {
            $recipients[] = (int) $supervisor->id;
        }

        $this->notifications->notifyMany(
            userIds: $recipients,
            title: 'Task Solution Submitted',
            description: 'قام الطالب بتسليم حل مهمة.',
            type: 'success',
            meta: ['category' => 'task']
        );

        return back()->with('success', 'تم تسليم الحل بنجاح.');
    }

    public function addComment(Request $request, Application $application, Task $task): RedirectResponse
    {
        $this->authorizeApplication($request, $application);
        $this->ensureTrainingOpen($application);
        abort_unless((int) $application->id === (int) $task->application_id, 404);

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:1000'],
        ]);

        $task->comments()->create([
            'user_id' => $request->user()->id,
            'content' => trim($validated['content']),
        ]);

        if ((string) $request->user()->role !== 'student' && $application->student_id) {
            $this->notifications->notifyUser(
                userId: (int) $application->student_id,
                title: 'New Comment on Task',
                description: 'تمت إضافة تعليق جديد على إحدى مهامك.',
                type: 'info',
                meta: ['category' => 'task']
            );
        }

        return back()->with('success', 'تمت إضافة التعليق.');
    }

    public function gradeTask(Request $request, Application $application, Task $task): RedirectResponse
    {
        $this->authorizeApplication($request, $application);
        $this->ensureTrainingOpen($application);
        abort_unless(in_array($request->user()->role, ['company', 'supervisor', 'admin'], true), 403);
        abort_unless((int) $application->id === (int) $task->application_id, 404);

        $validated = $request->validate([
            'score' => ['required', 'integer', 'min:0', 'max:50'],
        ]);

        if ($request->user()->role === 'company') {
            $task->company_score = $validated['score'];
        } elseif ($request->user()->role === 'supervisor') {
            $task->supervisor_score = $validated['score'];
        } else {
            $task->company_score = $validated['score'];
            $task->supervisor_score = $validated['score'];
        }

        $task->save();

        if ($application->student_id) {
            $this->notifications->notifyUser(
                userId: (int) $application->student_id,
                title: 'Task Evaluated',
                description: 'تم تقييم مهمتك من قبل الجهة المسؤولة.',
                type: 'success',
                meta: ['category' => 'evaluation']
            );
        }

        return back()->with('success', 'تم حفظ التقييم بنجاح.');
    }
}
