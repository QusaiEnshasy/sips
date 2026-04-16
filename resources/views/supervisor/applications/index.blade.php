@extends('supervisor.layouts.app')

@section('title', 'Student Applications')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h3 class="fw-bold" data-i18n="student_applications">Student Applications</h3>
        <p class="text-muted mb-0" data-i18n="review_applications">Review applications submitted by your students to companies</p>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        <div class="bg-white border rounded-pill px-3 py-2 d-flex align-items-center gap-2 shadow-sm">
            <i class="bi bi-key text-primary"></i>
            <span class="fw-semibold">{{ $supervisor->supervisor_code ?? '-' }}</span>
        </div>

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

<div class="row g-3 g-lg-4 mb-5">
    <div class="col-12 col-sm-6 col-md-6 col-lg-3">
        <div class="stat-card" style="background: rgba(59,130,246,.1); border-left:5px solid #3b82f6;">
            <div class="icon-rounded" style="background:#3b82f6;">
                <i class="bi bi-files"></i>
            </div>
            <small class="text-muted d-block" data-i18n="total_applications">Total Applications</small>
            <h2 class="fw-bold">{{ $totalApplications }}</h2>
            <span class="text-primary small fw-bold" data-i18n="all_student_requests">All student requests</span>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-6 col-lg-3">
        <div class="stat-card" style="background: rgba(245,158,11,.1); border-left:5px solid #f59e0b;">
            <div class="icon-rounded" style="background:#f59e0b;">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <small class="text-muted d-block" data-i18n="pending">Pending</small>
            <h2 class="fw-bold">{{ $pendingApplications }}</h2>
            <span class="text-warning small fw-bold" data-i18n="waiting_review">Waiting your review</span>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-6 col-lg-3">
        <div class="stat-card" style="background: rgba(34,197,94,.1); border-left:5px solid #22c55e;">
            <div class="icon-rounded" style="background:#22c55e;">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <small class="text-muted d-block" data-i18n="approved">Approved</small>
            <h2 class="fw-bold">{{ $approvedApplications }}</h2>
            <span class="text-success small fw-bold" data-i18n="supervisor_approved">Supervisor approved</span>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-md-6 col-lg-3">
        <div class="stat-card" style="background: rgba(239,68,68,.1); border-left:5px solid #ef4444;">
            <div class="icon-rounded" style="background:#ef4444;">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <small class="text-muted d-block" data-i18n="rejected">Rejected</small>
            <h2 class="fw-bold">{{ $rejectedApplications }}</h2>
            <span class="text-danger small fw-bold" data-i18n="supervisor_rejected">Supervisor rejected</span>
        </div>
    </div>
</div>

<div class="bg-white p-3 p-md-4 rounded-4 border mb-4">
    <h5 class="fw-bold mb-1" data-i18n="applications_table">Applications Table</h5>
    <p class="text-muted small mb-0" data-i18n="only_linked_students">Only applications submitted by students linked to your supervisor code are shown here</p>
</div>

<div class="bg-white rounded-4 border shadow-sm overflow-hidden">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead style="background:#f8fafc;">
                <tr>
                    <th class="px-4 py-3" data-i18n="student">Student</th>
                    <th class="px-4 py-3" data-i18n="company_opportunity">Company / Opportunity</th>
                    <th class="px-4 py-3" data-i18n="company_status">Company Status</th>
                    <th class="px-4 py-3" data-i18n="supervisor_status">Supervisor Status</th>
                    <th class="px-4 py-3" data-i18n="final_status">Final Status</th>
                    <th class="px-4 py-3" data-i18n="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($applications as $application)
                @php
                $student = $application->student;
                $opportunity = $application->opportunity;

                $supervisorStatus = $application->supervisor_status ?? 'pending';
                $companyStatus = $application->company_status ?? 'pending';
                $finalStatus = $application->final_status ?? 'pending';

                $supervisorPending = $supervisorStatus === 'pending';
                $supervisorApproved = $supervisorStatus === 'approved';
                $supervisorRejected = $supervisorStatus === 'rejected';
                @endphp

                <tr>
                    <td class="px-4 py-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-lg" style="width:48px;height:48px;font-size:14px;">
                                {{ strtoupper(substr($student->name ?? 'S', 0, 1)) }}
                            </div>
                            <div>
                                <div class="fw-bold">{{ $student->name ?? '-' }}</div>
                                <small class="text-muted">{{ $student->email ?? '-' }}</small>
                            </div>
                        </div>
                    </td>

                    <td class="px-4 py-3">
                        <div class="fw-semibold">{{ $opportunity->title ?? '-' }}</div>
                        <small class="text-muted">
                            {{ $opportunity->type ?? '-' }}
                        </small>
                    </td>

                    <td class="px-4 py-3">
                        @if($companyStatus === 'approved')
                        <span class="status-badge status-on-track" data-i18n="approved">Approved</span>
                        @elseif($companyStatus === 'rejected')
                        <span class="status-badge" style="background:#fee2e2;color:#dc2626;">Rejected</span>
                        @else
                        <span class="status-badge status-at-risk" data-i18n="pending_status">Pending</span>
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        @if($supervisorApproved)
                        <span class="status-badge status-on-track" data-i18n="approved">Approved</span>
                        @elseif($supervisorRejected)
                        <span class="status-badge" style="background:#fee2e2;color:#dc2626;">Rejected</span>
                        @else
                        <span class="status-badge status-at-risk" data-i18n="pending_status">Pending</span>
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        @if($finalStatus === 'approved')
                        <span class="status-badge status-on-track" data-i18n="approved">Approved</span>
                        @elseif($finalStatus === 'rejected')
                        <span class="status-badge" style="background:#fee2e2;color:#dc2626;">Rejected</span>
                        @else
                        <span class="status-badge status-at-risk" data-i18n="pending_status">Pending</span>
                        @endif
                    </td>

                    <td class="px-4 py-3">
                        <div class="d-flex flex-wrap gap-2">
                            @if($supervisorPending)
                            <form method="POST" action="{{ route('supervisor.applications.approve', $application->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-success btn-sm rounded-pill px-3">
                                    <i class="bi bi-check-circle me-1"></i> <span data-i18n="approve">Approve</span>
                                </button>
                            </form>

                            <form method="POST" action="{{ route('supervisor.applications.reject', $application->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3">
                                    <i class="bi bi-x-circle me-1"></i> <span data-i18n="reject">Reject</span>
                                </button>
                            </form>
                            @else
                            <span class="text-muted small" data-i18n="decision_submitted">Decision already submitted</span>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">
                        <span data-i18n="no_applications">No applications found for your students</span>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
