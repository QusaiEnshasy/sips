@extends('supervisor.layouts.app')

@section('title', 'Students')

@section('content')
@php
    $fixMojibake = function ($value) {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        $original = $value;
        $current = $value;

        for ($i = 0; $i < 2; $i++) {
            if (!preg_match('/Ã|Â|â€|Ø|Ù/u', $current)) {
                break;
            }

            $candidate = @iconv('Windows-1252', 'UTF-8//IGNORE', $current);
            if (!is_string($candidate) || $candidate === '') {
                break;
            }

            $current = $candidate;
        }

        return preg_match('/\p{Arabic}/u', $current) ? $current : $original;
    };
@endphp
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h3 class="fw-bold mb-1" data-i18n="students_title">Students</h3>
        <p class="text-muted mb-0" data-i18n="students_subtitle">Manage students linked to your supervisor code and review their current status.</p>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('supervisor.students.pending') }}" class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-person-check me-1"></i> <span data-i18n="pending_students">Pending Students</span>
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

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="row g-3 g-lg-4 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card" style="background: rgba(59,130,246,.1); border-left:5px solid #3b82f6;">
            <div class="icon-rounded" style="background:#3b82f6;">
                <i class="bi bi-people-fill"></i>
            </div>
            <small class="text-muted d-block" data-i18n="total_students">Total Students</small>
            <h2 class="fw-bold">{{ $totalStudents }}</h2>
            <span class="text-primary small fw-bold">All linked students</span>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card" style="background: rgba(245,158,11,.1); border-left:5px solid #f59e0b;">
            <div class="icon-rounded" style="background:#f59e0b;">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <small class="text-muted d-block" data-i18n="pending">Pending</small>
            <h2 class="fw-bold">{{ $totalPending }}</h2>
            <span class="text-warning small fw-bold">Waiting approval</span>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card" style="background: rgba(34,197,94,.1); border-left:5px solid #22c55e;">
            <div class="icon-rounded" style="background:#22c55e;">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <small class="text-muted d-block" data-i18n="approved">Approved</small>
            <h2 class="fw-bold">{{ $totalApproved }}</h2>
            <span class="text-success small fw-bold">Active students</span>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stat-card" style="background: rgba(239,68,68,.1); border-left:5px solid #ef4444;">
            <div class="icon-rounded" style="background:#ef4444;">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <small class="text-muted d-block" data-i18n="rejected">Rejected</small>
            <h2 class="fw-bold">{{ $totalRejected }}</h2>
            <span class="text-danger small fw-bold">Rejected requests</span>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="bg-white rounded-4 border shadow-sm overflow-hidden">
            <div class="p-4 border-bottom">
                <h5 class="fw-bold mb-1" data-i18n="pending_students_title">Pending Students</h5>
                <p class="text-muted small mb-0" data-i18n="pending_students_subtitle">Approve or reject newly registered students.</p>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead style="background:#f8fafc;">
                        <tr>
                            <th class="px-4 py-3" data-i18n="student">Student</th>
                            <th class="px-4 py-3" data-i18n="university_id">University ID</th>
                            <th class="px-4 py-3" data-i18n="phone">Phone</th>
                            <th class="px-4 py-3" data-i18n="supervisor_code">Supervisor Code</th>
                            <th class="px-4 py-3" data-i18n="actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendingStudents as $student)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="fw-semibold">{{ $student->name }}</div>
                                <small class="text-muted">{{ $student->email ?? '-' }}</small>
                            </td>
                            <td class="px-4 py-3">{{ $student->university_id ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $student->phone_number ?? $student->phone ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $student->supervisor_code ?? '-' }}</td>
                            <td class="px-4 py-3">
                                <div class="d-flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('supervisor.students.approve', $student->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm rounded-pill px-3">
                                            <i class="bi bi-check-circle me-1"></i> <span data-i18n="approve">Approve</span>
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('supervisor.students.reject', $student->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3">
                                            <i class="bi bi-x-circle me-1"></i> <span data-i18n="reject">Reject</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted" data-i18n="no_pending_students">No pending students found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="bg-white rounded-4 border shadow-sm overflow-hidden">
            <div class="p-4 border-bottom">
                <h5 class="fw-bold mb-1" data-i18n="approved_students">Approved Students</h5>
                <p class="text-muted small mb-0" data-i18n="approved_students_subtitle">Students whose accounts are active under your supervision.</p>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead style="background:#f8fafc;">
                        <tr>
                            <th class="px-4 py-3" data-i18n="student">Student</th>
                            <th class="px-4 py-3" data-i18n="program">Program</th>
                            <th class="px-4 py-3" data-i18n="progress">Progress</th>
                            <th class="px-4 py-3" data-i18n="status">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($approvedStudents as $row)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="fw-semibold">{{ $row['student']->name ?? '-' }}</div>
                                <small class="text-muted">{{ $row['student']->email ?? '-' }}</small>
                            </td>
                            @php $programTitle = $fixMojibake($row['opportunity']->title ?? null); @endphp
                            <td class="px-4 py-3">{{ $programTitle ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $row['progress'] ?? 0 }}%</td>
                            <td class="px-4 py-3">
                                @php $label = $row['status_label'] ?? 'On Track'; @endphp
                                <span class="status-badge {{ $label === 'At Risk' ? 'status-at-risk' : 'status-on-track' }}">{{ $label }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted" data-i18n="no_approved_students">No approved students found yet.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="bg-white rounded-4 border shadow-sm overflow-hidden">
            <div class="p-4 border-bottom">
                <h5 class="fw-bold mb-1" data-i18n="rejected_students_title">Rejected Students</h5>
                <p class="text-muted small mb-0" data-i18n="rejected_students_subtitle">Students whose account requests were rejected.</p>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead style="background:#f8fafc;">
                        <tr>
                            <th class="px-4 py-3" data-i18n="student">Student</th>
                            <th class="px-4 py-3" data-i18n="university_id">University ID</th>
                            <th class="px-4 py-3" data-i18n="email">Email</th>
                            <th class="px-4 py-3" data-i18n="status">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rejectedStudents as $student)
                        <tr>
                            <td class="px-4 py-3">{{ $student->name }}</td>
                            <td class="px-4 py-3">{{ $student->university_id ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $student->email ?? '-' }}</td>
                            <td class="px-4 py-3"><span class="status-badge" style="background:#fee2e2;color:#dc2626;">Rejected</span></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">No rejected students found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
