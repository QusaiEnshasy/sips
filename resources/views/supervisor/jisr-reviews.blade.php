@extends('supervisor.layouts.app')

@section('title', 'تقييم برنامج الجسر')

@push('styles')
<style>
    .jisr-stat-card {
        background: #fff;
        border: 1px solid #eef2ff;
        border-radius: 20px;
        padding: 20px;
        box-shadow: 0 10px 30px rgba(99, 102, 241, 0.08);
        height: 100%;
    }

    .jisr-review-card {
        background: #fff;
        border: 1px solid #f1f5f9;
        border-radius: 22px;
        box-shadow: 0 16px 40px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }

    .jisr-review-card + .jisr-review-card {
        margin-top: 20px;
    }

    .jisr-review-header {
        background: linear-gradient(135deg, #f8fafc, #eef2ff);
        border-bottom: 1px solid #e2e8f0;
        padding: 20px 24px;
    }

    .jisr-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 999px;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 700;
    }

    .jisr-badge-pending {
        background: #fef3c7;
        color: #92400e;
    }

    .jisr-badge-accepted {
        background: #dcfce7;
        color: #166534;
    }

    .jisr-badge-rejected {
        background: #fee2e2;
        color: #991b1b;
    }

    .jisr-body {
        padding: 24px;
    }

    .jisr-answer-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 16px;
        white-space: pre-wrap;
    }

    .attachment-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 12px;
        border-radius: 999px;
        background: #eef2ff;
        color: #4338ca;
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
    }

    .jisr-form-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 18px;
    }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h3 class="fw-bold mb-1">تقييم برنامج الجسر</h3>
        <p class="text-muted mb-0">راجع حلول الطلاب في برنامج الجسر واعتمدها أو أعدها للطالب مع ملاحظاتك.</p>
    </div>

    <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="{{ route('supervisor.students.index') }}" class="btn btn-outline-primary rounded-pill px-4">
            <i class="bi bi-people me-1"></i> الطلاب
        </a>
        <a href="{{ route('supervisor.dashboard') }}" class="btn btn-outline-secondary rounded-pill px-4">
            <i class="bi bi-grid me-1"></i> لوحة المشرف
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
    <div class="alert alert-success rounded-4 border-0 shadow-sm">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger rounded-4 border-0 shadow-sm">
        {{ $errors->first() }}
    </div>
@endif

<div class="row g-3 mb-4">
    <div class="col-12 col-md-3">
        <div class="jisr-stat-card">
            <div class="text-muted small">إجمالي التسليمات</div>
            <div class="fs-2 fw-bold">{{ $stats['total_submissions'] ?? 0 }}</div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="jisr-stat-card">
            <div class="text-muted small">بانتظار التقييم</div>
            <div class="fs-2 fw-bold text-warning">{{ $stats['pending_reviews'] ?? 0 }}</div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="jisr-stat-card">
            <div class="text-muted small">تم اعتمادها</div>
            <div class="fs-2 fw-bold text-success">{{ $stats['accepted_submissions'] ?? 0 }}</div>
        </div>
    </div>
    <div class="col-12 col-md-3">
        <div class="jisr-stat-card">
            <div class="text-muted small">مطلوب تعديلها</div>
            <div class="fs-2 fw-bold text-danger">{{ $stats['rejected_submissions'] ?? 0 }}</div>
        </div>
    </div>
</div>

@forelse($submissions as $submission)
    @php
        $statusClass = match($submission['status']) {
            'accepted' => 'jisr-badge jisr-badge-accepted',
            'rejected' => 'jisr-badge jisr-badge-rejected',
            default => 'jisr-badge jisr-badge-pending',
        };
        $statusLabel = match($submission['status']) {
            'accepted' => 'تم الاعتماد',
            'rejected' => 'مطلوب تعديل',
            default => 'بانتظار التقييم',
        };
    @endphp
    <div class="jisr-review-card">
        <div class="jisr-review-header d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <div class="fw-bold fs-5">{{ $submission['student']['name'] ?? '-' }}</div>
                <div class="text-muted">{{ $submission['student']['email'] ?? '-' }}</div>
                <div class="mt-2 small text-muted">
                    المهمة: <span class="fw-semibold text-dark">{{ $submission['task']['title'] ?? '-' }}</span>
                    <span class="mx-2">|</span>
                    رقم المهمة: <span class="fw-semibold text-dark">#{{ $submission['task']['order_number'] ?? '-' }}</span>
                </div>
            </div>
            <div class="text-end">
                <span class="{{ $statusClass }}">{{ $statusLabel }}</span>
                <div class="small text-muted mt-2">
                    {{ !empty($submission['submitted_at']) ? \Carbon\Carbon::parse($submission['submitted_at'])->format('Y-m-d H:i') : '-' }}
                </div>
            </div>
        </div>

        <div class="jisr-body">
            <div class="row g-4">
                <div class="col-12 col-lg-7">
                    <div class="mb-3">
                        <div class="fw-semibold mb-2">حل الطالب</div>
                        <div class="jisr-answer-box">{{ $submission['content'] ?: 'لا يوجد نص مرفق.' }}</div>
                    </div>

                    @if(!empty($submission['attachments']))
                        <div>
                            <div class="fw-semibold mb-2">المرفقات</div>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($submission['attachments'] as $attachment)
                                    @if(!empty($attachment['url']))
                                        <a class="attachment-chip" href="{{ $attachment['url'] }}" target="_blank" rel="noopener noreferrer">
                                            <i class="bi bi-paperclip"></i>
                                            {{ $attachment['name'] ?? 'ملف مرفق' }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="col-12 col-lg-5">
                    <div class="jisr-form-card mb-3">
                        <div class="fw-semibold mb-2">التقييم الحالي</div>
                        <div class="small text-muted mb-2">الدرجة الحالية: {{ $submission['score'] ?? '-' }}</div>
                        <div class="jisr-answer-box">{{ $submission['feedback'] ?: 'لا توجد ملاحظات محفوظة بعد.' }}</div>
                    </div>

                    <form method="POST" action="{{ route('supervisor.jisr-reviews.review', ['id' => $submission['id']]) }}" class="jisr-form-card mb-3">
                        @csrf
                        <input type="hidden" name="status" value="accepted">
                        <div class="fw-semibold mb-3">اعتماد الحل</div>
                        <div class="mb-3">
                            <label class="form-label">الدرجة</label>
                            <input type="number" name="score" class="form-control" min="0" max="{{ $submission['task']['max_score'] ?? 100 }}" value="{{ $submission['score'] ?? ($submission['task']['max_score'] ?? 100) }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">ملاحظات المشرف</label>
                            <textarea name="feedback" class="form-control" rows="4" required>{{ $submission['feedback'] ?: 'أحسنت، تم اعتماد الحل.' }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check-circle me-1"></i> اعتماد الحل
                        </button>
                    </form>

                    <form method="POST" action="{{ route('supervisor.jisr-reviews.review', ['id' => $submission['id']]) }}" class="jisr-form-card">
                        @csrf
                        <input type="hidden" name="status" value="rejected">
                        <div class="fw-semibold mb-3">إعادة الحل للطالب</div>
                        <div class="mb-3">
                            <label class="form-label">الدرجة</label>
                            <input type="number" name="score" class="form-control" min="0" max="{{ $submission['task']['max_score'] ?? 100 }}" value="{{ $submission['score'] ?? 0 }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">سبب الإرجاع أو التعديل المطلوب</label>
                            <textarea name="feedback" class="form-control" rows="4" placeholder="اكتب للطالب ما الذي يحتاج تعديله" required>{{ $submission['feedback'] }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> إعادة للطالب
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@empty
    <div class="alert alert-warning rounded-4 border-0 shadow-sm">
        لا توجد حلول مرسلة في برنامج الجسر حاليًا.
    </div>
@endforelse
@endsection
