<!doctype html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Training Tasks Board</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body class='bg-light'>
<div class='container py-4'>
@php
    $backUrl = match ($role) {
        'admin' => route('tasks.admin.workspace'),
        'supervisor' => route('supervisor.weekly-tasks'),
        'company' => route('company.applicants.index'),
        'student' => route('student.workspace.index'),
        default => route('tasks.workspace'),
    };
@endphp

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h3 class="fw-bold mb-1">Training Tasks Board</h3>
        <p class="text-muted mb-0">{{ $application->student->name }} - {{ $application->opportunity->title ?? '-' }}</p>
    </div>
    <a href="{{ $backUrl }}" class="btn btn-outline-secondary rounded-pill">Back</a>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

@php
$needsCompanyFinal = $trainingEnded && in_array($role, ['company', 'admin']) && $application->company_final_score === null;
$needsSupervisorFinal = $trainingEnded && in_array($role, ['supervisor', 'admin']) && $application->supervisor_final_score === null;
@endphp

@if($trainingEnded)
<div class="alert alert-warning mb-4">
    <div><strong>Training period ended.</strong> Tasks are now read-only.</div>
    @if($trainingEndDate)
    <div class="small mt-1">End date: {{ $trainingEndDate->format('Y-m-d') }}</div>
    @endif
    @if($application->training_completed_at)
    <div class="small mt-1">Completed at: {{ $application->training_completed_at->format('Y-m-d H:i') }}</div>
    <a href="{{ route('training.complete', $application->id) }}" class="btn btn-sm btn-primary rounded-pill mt-2">Open Completion Screen</a>
    @endif
</div>
@endif

@if($needsCompanyFinal)
<div class="bg-white rounded-4 border shadow-sm p-4 mb-4">
    <h5 class="fw-bold mb-3">Company Final Evaluation (Required)</h5>
    <form method="POST" action="{{ route('company.applications.complete-training', $application->id) }}" class="row g-3">
        @csrf
        <div class="col-md-3">
            <label class="form-label">Score /100</label>
            <input type="number" name="company_final_score" min="0" max="100" required class="form-control" value="{{ old('company_final_score', $application->company_final_score) }}">
        </div>
        <div class="col-md-9">
            <label class="form-label">Final Note</label>
            <textarea name="company_final_note" rows="3" class="form-control" required>{{ old('company_final_note', $application->company_final_note) }}</textarea>
        </div>
        <div class="col-12">
            <button class="btn btn-primary rounded-pill px-4" type="submit">Save Final Evaluation</button>
        </div>
    </form>
</div>
@endif

@if($needsSupervisorFinal)
<div class="bg-white rounded-4 border shadow-sm p-4 mb-4">
    <h5 class="fw-bold mb-3">Supervisor Final Evaluation (Required)</h5>
    <form method="POST" action="{{ route('supervisor.applications.complete-training', $application->id) }}" class="row g-3">
        @csrf
        <div class="col-md-3">
            <label class="form-label">Score /100</label>
            <input type="number" name="supervisor_final_score" min="0" max="100" required class="form-control" value="{{ old('supervisor_final_score', $application->supervisor_final_score) }}">
        </div>
        <div class="col-md-9">
            <label class="form-label">Final Note</label>
            <textarea name="supervisor_final_note" rows="3" class="form-control" required>{{ old('supervisor_final_note', $application->supervisor_final_note) }}</textarea>
        </div>
        <div class="col-12">
            <button class="btn btn-primary rounded-pill px-4" type="submit">Save Final Evaluation</button>
        </div>
    </form>
</div>
@endif

@if(in_array($role, ['company', 'supervisor']) && ! $trainingEnded)
<div class="bg-white rounded-4 border shadow-sm p-4 mb-4">
    <h5 class="fw-bold mb-3">Create Task</h5>
    <form method="POST" action="{{ route('tasks.create', $application->id) }}" class="row g-3">
        @csrf
        <div class="col-md-4">
            <input type="text" name="title" class="form-control" placeholder="Task title" required>
        </div>
        <div class="col-md-4">
            <input type="date" name="due_date" class="form-control">
        </div>
        <div class="col-md-4">
            <select name="label" class="form-select">
                <option value="">Label</option>
                <option value="red">Urgent</option>
                <option value="green">Low</option>
                <option value="blue">Feature</option>
            </select>
        </div>
        <div class="col-12">
            <textarea name="details" class="form-control" rows="2" placeholder="Task details"></textarea>
        </div>
        <div class="col-12">
            <button class="btn btn-primary rounded-pill">Add Task</button>
        </div>
    </form>
</div>
@endif

<div class="row g-4">
    @foreach(['todoTasks' => 'To Do', 'progressTasks' => 'In Progress', 'doneTasks' => 'Done'] as $key => $label)
    <div class="col-lg-4">
        <div class="bg-white rounded-4 border shadow-sm p-3 h-100">
            <h5 class="fw-bold mb-3">{{ $label }} ({{ count($$key) }})</h5>
            @forelse($$key as $task)
            <div class="border rounded-3 p-3 mb-3">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="fw-bold mb-0">{{ $task->title }}</h6>
                    <small class="text-muted">{{ $task->creator->name ?? '-' }}</small>
                </div>

                @if($task->details)
                <p class="text-muted small mb-2">{{ $task->details }}</p>
                @endif

                <div class="small mb-2">
                    <strong>Due:</strong> {{ $task->due_date ? $task->due_date->format('Y-m-d') : '-' }}
                    <br>
                    <strong>Score:</strong> {{ $task->company_score ?? 0 }} + {{ $task->supervisor_score ?? 0 }} / 100
                </div>

                @if($task->student_solution)
                <div class="alert alert-light border small mb-2">
                    <strong>Student solution:</strong><br>{{ $task->student_solution }}
                </div>
                @endif

                @if($role === 'student' && ! $trainingEnded)
                <form method="POST" action="{{ route('tasks.submit', [$application->id, $task->id]) }}" enctype="multipart/form-data" class="mb-2">
                    @csrf
                    <textarea name="student_solution" class="form-control mb-2" rows="2" placeholder="Write your solution..." required>{{ $task->student_solution }}</textarea>
                    <select name="status" class="form-select mb-2">
                        <option value="todo" @selected($task->status==='todo')>To Do</option>
                        <option value="progress" @selected($task->status==='progress')>In Progress</option>
                        <option value="done" @selected($task->status==='done')>Done</option>
                    </select>
                    <input type="file" name="attachments[]" class="form-control mb-2" multiple>
                    <button class="btn btn-sm btn-success">Submit / Update</button>
                </form>
                @endif

                @if(in_array($role, ['company', 'supervisor']) && ! $trainingEnded)
                <form method="POST" action="{{ route('tasks.grade', [$application->id, $task->id]) }}" class="mb-2 d-flex gap-2">
                    @csrf
                    <input type="number" name="score" min="0" max="50" class="form-control form-control-sm" placeholder="Score /50" required>
                    <button class="btn btn-sm btn-warning">Save Grade</button>
                </form>
                @endif

                @if(! $trainingEnded)
                <form method="POST" action="{{ route('tasks.comment', [$application->id, $task->id]) }}" class="mb-2 d-flex gap-2">
                    @csrf
                    <input type="text" name="content" class="form-control form-control-sm" placeholder="Add comment" required>
                    <button class="btn btn-sm btn-outline-primary">Send</button>
                </form>
                @endif

                @if($task->comments->count())
                <div class="small border-top pt-2 mt-2">
                    @foreach($task->comments as $comment)
                    <div class="mb-1"><strong>{{ $comment->user->name ?? '-' }}</strong> ({{ $comment->user->role ?? '-' }}): {{ $comment->content }}</div>
                    @endforeach
                </div>
                @endif

                @if($task->attachments->count())
                <div class="small border-top pt-2 mt-2">
                    @foreach($task->attachments as $attachment)
                    <a href="{{ asset('storage/' . $attachment->filepath) }}" target="_blank">{{ $attachment->filename }}</a><br>
                    @endforeach
                </div>
                @endif
            </div>
            @empty
            <p class="text-muted small mb-0">No tasks in this column.</p>
            @endforelse
        </div>
    </div>
    @endforeach
</div>
</div>
</body>
</html>


