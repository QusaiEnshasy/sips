@extends('supervisor.layouts.app')

@section('title', 'Supervisor Dashboard')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h3 class="fw-bold mb-1" data-i18n="supervisor_dashboard">Supervisor Dashboard</h3>
        <p class="text-muted mb-0" data-i18n="monitor_students">Monitor your students, approvals, and active training progress.</p>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('supervisor.students.pending') }}" class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-person-check me-1"></i> <span data-i18n="pending_students">Pending Students</span>
        </a>
        <a href="{{ route('supervisor.applications.index') }}" class="btn btn-outline-primary rounded-pill px-4">
            <i class="bi bi-file-earmark-text me-1"></i> <span data-i18n="applications">Applications</span>
        </a>
        <div class="lang-switcher">
            <button type="button" class="lang-btn" id="langSwitchEn" onclick="applyBladeLanguage('en')">EN</button>
            <button type="button" class="lang-btn" id="langSwitchAr" onclick="applyBladeLanguage('ar')">AR</button>
        </div>
        <button class="theme-toggle-btn" onclick="toggleTheme()">
            <i id="themeIcon" class="bi bi-moon-stars-fill"></i>
        </button>
    </div>
</div>

<div class="row g-3 g-lg-4 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card" style="background: rgba(59,130,246,.1); border-left:5px solid #3b82f6;">
            <div class="icon-rounded" style="background:#3b82f6;">
                <i class="bi bi-people-fill"></i>
            </div>
            <small class="text-muted d-block" data-i18n="total_students">Total Students</small>
            <h2 class="fw-bold">{{ $totalStudents }}</h2>
            <span class="text-primary small fw-bold" data-i18n="all_linked_students">All linked students</span>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card" style="background: rgba(245,158,11,.1); border-left:5px solid #f59e0b;">
            <div class="icon-rounded" style="background:#f59e0b;">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <small class="text-muted d-block" data-i18n="pending">Pending</small>
            <h2 class="fw-bold">{{ $pendingStudents }}</h2>
            <span class="text-warning small fw-bold" data-i18n="awaiting_approval">Awaiting approval</span>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card" style="background: rgba(34,197,94,.1); border-left:5px solid #22c55e;">
            <div class="icon-rounded" style="background:#22c55e;">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <small class="text-muted d-block" data-i18n="active">Active</small>
            <h2 class="fw-bold">{{ $activeStudents }}</h2>
            <span class="text-success small fw-bold" data-i18n="active_students">Active students</span>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card" style="background: rgba(239,68,68,.1); border-left:5px solid #ef4444;">
            <div class="icon-rounded" style="background:#ef4444;">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <small class="text-muted d-block" data-i18n="rejected">Rejected</small>
            <h2 class="fw-bold">{{ $rejectedStudents }}</h2>
            <span class="text-danger small fw-bold" data-i18n="rejected_students">Rejected students</span>
        </div>
    </div>
</div>

<div class="bg-white rounded-4 border shadow-sm p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold mb-1" data-i18n="approved_training_students">Approved Training Students</h5>
            <p class="text-muted small mb-0" data-i18n="approved_training_subtitle">Students with approved applications currently under your supervision.</p>
        </div>
        <a href="{{ route('supervisor.students.index') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-i18n="view_all_students">View All Students</a>
    </div>

    <div class="row g-3">
        @forelse($studentCards as $card)
            <div class="col-12 col-md-6 col-xl-4">
                <div class="student-card">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="avatar-lg">
                            {{ strtoupper(substr($card['student']->name ?? 'S', 0, 1)) }}
                        </div>
                        <div>
                            <div class="fw-bold">{{ $card['student']->name ?? '-' }}</div>
                            <small class="text-muted">{{ $card['student']->email ?? '-' }}</small>
                        </div>
                    </div>

                    <div class="mb-2 fw-semibold">{{ $card['opportunity']->title ?? '-' }}</div>
                    <div class="text-muted small mb-3">{{ $card['status_label'] }}</div>

                    <div class="progress-label">
                        <span data-i18n="progress">Progress</span>
                        <span>{{ $card['progress'] }}%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" role="progressbar" style="width: {{ $card['progress'] }}%; background:#6366f1;"></div>
                    </div>

                    <div class="card-actions">
                        <a href="{{ route('tasks.board', ['application' => $card['application']->id]) }}" class="btn-outline-card">
                            <i class="bi bi-kanban"></i>
                            <span data-i18n="open_board">Open Board</span>
                        </a>
                        <a href="{{ route('supervisor.students.show', ['id' => $card['student']->id]) }}" class="btn-outline-card">
                            <i class="bi bi-person-lines-fill"></i>
                            <span data-i18n="details">Details</span>
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="text-center text-muted py-4">
                    <span data-i18n="no_approved_students">No approved student applications found yet.</span>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
