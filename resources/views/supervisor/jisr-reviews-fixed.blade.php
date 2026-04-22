@extends('supervisor.layouts.app')

@section('title', 'تقييم برنامج الجسر')

@push('styles')
<style>
    .jisr-page {
        direction: rtl;
    }

    .jisr-toolbar {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-end;
    }

    .stats-grid {
        display: grid;
        gap: 14px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        margin-bottom: 24px;
    }

    .stat-card {
        background: #fff;
        border: 1px solid #e7ecff;
        border-radius: 20px;
        box-shadow: 0 14px 35px rgba(15, 23, 42, .06);
        padding: 18px;
    }

    .stat-label {
        color: #64748b;
        font-size: 13px;
        margin-bottom: 6px;
    }

    .stat-value {
        color: #0f172a;
        font-size: 30px;
        font-weight: 900;
    }

    .student-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 26px;
        box-shadow: 0 22px 60px rgba(15, 23, 42, .08);
        margin-bottom: 26px;
        overflow: hidden;
    }

    .student-head {
        align-items: flex-start;
        background: linear-gradient(135deg, #f8fafc, #eef2ff);
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        justify-content: space-between;
        padding: 24px;
    }

    .student-name {
        color: #111827;
        font-size: 22px;
        font-weight: 900;
    }

    .student-email {
        color: #64748b;
        font-size: 14px;
        margin-top: 4px;
    }

    .student-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 14px;
    }

    .student-pills span {
        background: #fff;
        border: 1px solid #dbe4ff;
        border-radius: 999px;
        color: #334155;
        font-size: 13px;
        font-weight: 800;
        padding: 7px 12px;
    }

    .status-badge {
        border-radius: 999px;
        display: inline-flex;
        font-size: 12px;
        font-weight: 900;
        padding: 8px 14px;
        white-space: nowrap;
    }

    .status-pending { background: #fef3c7; color: #92400e; }
    .status-accepted { background: #dcfce7; color: #166534; }
    .status-rejected { background: #fee2e2; color: #991b1b; }

    .student-body {
        padding: 8px 24px 24px;
    }

    .task-row {
        border-bottom: 1px solid #e5e7eb;
        display: grid;
        gap: 18px;
        grid-template-columns: minmax(0, 1fr) 320px;
        padding: 24px 0;
    }

    .task-row:last-child {
        border-bottom: 0;
        padding-bottom: 0;
    }

    .task-main {
        min-width: 0;
    }

    .task-title-line {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 8px;
    }

    .task-number {
        background: #eef2ff;
        border-radius: 999px;
        color: #4338ca;
        font-size: 12px;
        font-weight: 900;
        padding: 6px 10px;
    }

    .task-title {
        color: #0f172a;
        font-size: 18px;
        font-weight: 900;
    }

    .task-desc {
        color: #64748b;
        line-height: 1.7;
        margin-bottom: 14px;
    }

    .section-box {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        margin-top: 12px;
        padding: 16px;
    }

    .section-title {
        color: #0f172a;
        font-size: 14px;
        font-weight: 900;
        margin-bottom: 10px;
    }

    .answer-text {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        color: #334155;
        line-height: 1.8;
        min-height: 54px;
        padding: 12px;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .files-list {
        display: grid;
        gap: 10px;
    }

    .file-row {
        align-items: center;
        background: #fff;
        border: 1px solid #dbe4ff;
        border-radius: 15px;
        display: flex;
        gap: 12px;
        justify-content: space-between;
        padding: 12px;
    }

    .file-info {
        align-items: center;
        display: flex;
        gap: 10px;
        min-width: 0;
    }

    .file-icon {
        align-items: center;
        background: #eef2ff;
        border-radius: 12px;
        color: #4338ca;
        display: inline-flex;
        flex-shrink: 0;
        height: 40px;
        justify-content: center;
        width: 40px;
    }

    .file-name {
        color: #0f172a;
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .file-meta {
        color: #64748b;
        font-size: 12px;
        margin-top: 2px;
    }

    .file-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
    }

    .file-action {
        border-radius: 999px;
        font-size: 13px;
        font-weight: 900;
        padding: 8px 12px;
        text-decoration: none;
        white-space: nowrap;
    }

    .file-action.view { background: #eef2ff; color: #4338ca; }
    .file-action.download { background: #dcfce7; color: #166534; }

    .side-panel {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        height: fit-content;
        padding: 16px;
    }

    .current-review {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        color: #475569;
        line-height: 1.7;
        padding: 12px;
    }

    .review-actions {
        display: grid;
        gap: 10px;
        margin-top: 12px;
    }

    .review-toggle {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        overflow: hidden;
    }

    .review-toggle summary {
        cursor: pointer;
        font-weight: 900;
        list-style: none;
        padding: 12px 14px;
        text-align: center;
    }

    .review-toggle summary::-webkit-details-marker {
        display: none;
    }

    .review-toggle.accept summary { background: #dcfce7; color: #166534; }
    .review-toggle.reject summary { background: #fee2e2; color: #991b1b; }

    .review-form {
        border-top: 1px solid #e5e7eb;
        padding: 14px;
    }

    .empty-box {
        background: #fff;
        border: 1px dashed #cbd5e1;
        border-radius: 14px;
        color: #64748b;
        padding: 14px;
    }

    /* High-contrast layer for clearer separation between cards, tasks, files, and actions. */
    .stat-card {
        border: 2px solid #c7d2fe;
    }

    .student-card {
        border: 2px solid #94a3b8;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .14);
    }

    .student-head {
        background: linear-gradient(135deg, #dbeafe, #eef2ff);
        border-bottom: 2px solid #94a3b8;
    }

    .student-email,
    .task-desc,
    .file-meta {
        color: #334155;
    }

    .student-pills span {
        border: 2px solid #a5b4fc;
    }

    .status-pending {
        background: #b45309;
        color: #fff;
    }

    .status-accepted {
        background: #166534;
        color: #fff;
    }

    .status-rejected {
        background: #991b1b;
        color: #fff;
    }

    .task-row {
        border-bottom: 2px solid #cbd5e1;
    }

    .task-number,
    .file-icon {
        background: #312e81;
        color: #fff;
    }

    .section-box {
        background: #eef2ff;
        border: 2px solid #a5b4fc;
    }

    .answer-text,
    .current-review {
        border: 2px solid #cbd5e1;
        color: #0f172a;
    }

    .file-row {
        border: 2px solid #93c5fd;
    }

    .file-action.view {
        background: #1e3a8a;
        color: #fff;
    }

    .file-action.download {
        background: #166534;
        color: #fff;
    }

    .side-panel {
        background: #f1f5f9;
        border: 2px solid #94a3b8;
    }

    .review-toggle {
        border: 2px solid #94a3b8;
    }

    .review-toggle.accept summary {
        background: #166534;
        color: #fff;
    }

    .review-toggle.reject summary {
        background: #991b1b;
        color: #fff;
    }

    @media (max-width: 1200px) {
        .task-row { grid-template-columns: 1fr; }
    }

    @media (max-width: 992px) {
        .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 768px) {
        .stats-grid { grid-template-columns: 1fr; }
        .student-head, .student-body { padding: 16px; }
        .jisr-toolbar { justify-content: stretch; }
        .jisr-toolbar > * { width: 100%; }
        .file-row { align-items: stretch; flex-direction: column; }
        .file-actions { justify-content: stretch; }
        .file-action { flex: 1; text-align: center; }
    }

    /* Balanced contrast: readable separation without harsh saturated blocks. */
    .student-card {
        border-color: #dde3ec;
        box-shadow: 0 16px 42px rgba(15, 23, 42, .07);
    }

    .student-head {
        background: linear-gradient(135deg, #f3f6ff, #fbfcff);
        border-bottom-color: #e2e8f0;
    }

    .status-pending {
        background: #fff7d6;
        color: #8a5a04;
    }

    .status-accepted {
        background: #e8f8ef;
        color: #1f6f42;
    }

    .status-rejected {
        background: #fdecec;
        color: #9f2f2f;
    }

    .task-number,
    .file-icon {
        background: #edf1ff;
        color: #4f46b8;
    }

    .section-box {
        background: #fbfcff;
        border-color: #e1e7f5;
    }

    .file-action.view {
        background: #f0f4ff;
        color: #4f46b8;
    }

    .file-action.download {
        background: #eaf8f0;
        color: #1f6f42;
    }

    .side-panel {
        background: #fbfcff;
        border-color: #e2e8f0;
    }

    .review-toggle.accept summary {
        background: #eaf8f0;
        color: #1f6f42;
    }

    .review-toggle.reject summary {
        background: #fdecec;
        color: #9f2f2f;
    }
</style>
@endpush

@section('content')
@php
    $studentGroups = collect($submissions ?? [])
        ->groupBy(fn ($submission) => data_get($submission, 'student.id') ?: 'student-' . data_get($submission, 'id'))
        ->sortBy(function ($group) {
            return $group->contains(fn ($item) => data_get($item, 'status') === 'pending_review') ? 0 :
                ($group->contains(fn ($item) => data_get($item, 'status') === 'rejected') ? 1 : 2);
        });

    $badge = function ($status) {
        return match($status) {
            'accepted' => ['class' => 'status-badge status-accepted', 'label' => 'تم الاعتماد'],
            'rejected' => ['class' => 'status-badge status-rejected', 'label' => 'مطلوب تعديل'],
            default => ['class' => 'status-badge status-pending', 'label' => 'بانتظار التقييم'],
        };
    };
@endphp

<div class="jisr-page">
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-3">
        <div>
            <h3 class="fw-bold mb-1">تقييم برنامج الجسر</h3>
            <p class="text-muted mb-0">كارد واحد لكل طالب، وداخله كل المهام والملفات بشكل واضح.</p>
        </div>

        <div class="jisr-toolbar">
            <a href="{{ route('supervisor.students.index') }}" class="btn btn-outline-primary rounded-pill px-4">
                <i class="bi bi-people me-1"></i>
                الطلاب
            </a>
            <a href="{{ route('supervisor.dashboard') }}" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="bi bi-grid me-1"></i>
                لوحة المشرف
            </a>
            <button type="button" class="lang-btn" id="langSwitchEn" onclick="applyBladeLanguage('en')">EN</button>
            <button type="button" class="lang-btn" id="langSwitchAr" onclick="applyBladeLanguage('ar')">AR</button>
            <button class="theme-toggle-btn" onclick="toggleTheme()" type="button">
                <i id="themeIcon" class="bi bi-moon-stars-fill"></i>
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success rounded-4 border-0 shadow-sm">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger rounded-4 border-0 shadow-sm">{{ $errors->first() }}</div>
    @endif

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">إجمالي التسليمات</div>
            <div class="stat-value">{{ $stats['total_submissions'] ?? 0 }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">بانتظار التقييم</div>
            <div class="stat-value text-warning">{{ $stats['pending_reviews'] ?? 0 }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">تم اعتمادها</div>
            <div class="stat-value text-success">{{ $stats['accepted_submissions'] ?? 0 }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">مطلوب تعديلها</div>
            <div class="stat-value text-danger">{{ $stats['rejected_submissions'] ?? 0 }}</div>
        </div>
    </div>

    @forelse($studentGroups as $studentId => $studentSubmissions)
        @php
            $first = $studentSubmissions->first();
            $student = data_get($first, 'student', []);
            $cardStatus = $studentSubmissions->contains(fn ($item) => data_get($item, 'status') === 'pending_review')
                ? 'pending_review'
                : ($studentSubmissions->contains(fn ($item) => data_get($item, 'status') === 'rejected') ? 'rejected' : 'accepted');
            $cardBadge = $badge($cardStatus);
            $attachmentsTotal = $studentSubmissions->sum(fn ($item) => collect(data_get($item, 'attachments', []))->count());
            $acceptedTasks = data_get($student, 'accepted_tasks', $studentSubmissions->where('status', 'accepted')->count());
            $totalTasks = data_get($student, 'total_tasks', $studentSubmissions->count());
        @endphp

        <article class="student-card">
            <div class="student-head">
                <div>
                    <div class="student-name">{{ data_get($student, 'name', '-') }}</div>
                    <div class="student-email">{{ data_get($student, 'email', '-') }}</div>
                    <div class="student-pills">
                        <span>الرقم الجامعي: {{ data_get($student, 'university_id', '-') ?: '-' }}</span>
                        <span>المهام: {{ $studentSubmissions->count() }}</span>
                        <span>الملفات: {{ $attachmentsTotal }}</span>
                        <span>المعتمد: {{ $acceptedTasks }}/{{ $totalTasks }}</span>
                    </div>
                </div>
                <span class="{{ $cardBadge['class'] }}">{{ $cardBadge['label'] }}</span>
            </div>

            <div class="student-body">
                @foreach($studentSubmissions->sortBy(fn ($item) => data_get($item, 'task.order_number', 999)) as $submission)
                    @php
                        $submissionBadge = $badge(data_get($submission, 'status'));
                        $attachments = collect(data_get($submission, 'attachments', []));
                    @endphp

                    <section class="task-row">
                        <div class="task-main">
                            <div class="task-title-line">
                                <span class="task-number">#{{ data_get($submission, 'task.order_number', '-') }}</span>
                                <span class="task-title">{{ data_get($submission, 'task.title', '-') }}</span>
                                <span class="{{ $submissionBadge['class'] }}">{{ $submissionBadge['label'] }}</span>
                            </div>

                            <div class="task-desc">{{ data_get($submission, 'task.description', '-') }}</div>
                            <div class="small text-muted mb-2">
                                الدرجة القصوى: {{ data_get($submission, 'task.max_score', 100) }}
                                |
                                أُرسل:
                                {{ data_get($submission, 'submitted_at') ? \Carbon\Carbon::parse(data_get($submission, 'submitted_at'))->format('Y-m-d H:i') : '-' }}
                            </div>

                            <div class="section-box">
                                <div class="section-title">حل الطالب</div>
                                <div class="answer-text">{{ data_get($submission, 'content') ?: 'لا يوجد نص مرفق.' }}</div>
                            </div>

                            <div class="section-box">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                    <div class="section-title mb-0">ملفات الحل</div>
                                    <span class="badge rounded-pill text-bg-light">{{ $attachments->count() }} ملف</span>
                                </div>

                                @if($attachments->isNotEmpty())
                                    <div class="files-list">
                                        @foreach($attachments as $attachment)
                                            <div class="file-row">
                                                <div class="file-info">
                                                    <div class="file-icon">
                                                        <i class="bi bi-file-earmark-arrow-up"></i>
                                                    </div>
                                                    <div>
                                                        <div class="file-name">{{ $attachment['name'] ?? 'ملف مرفق' }}</div>
                                                        <div class="file-meta">تم رفعه مع هذه المهمة</div>
                                                    </div>
                                                </div>
                                                <div class="file-actions">
                                                    @if(!empty($attachment['view_url']))
                                                        <a class="file-action view" href="{{ $attachment['view_url'] }}" target="_blank" rel="noopener noreferrer">
                                                            <i class="bi bi-eye"></i>
                                                            فتح
                                                        </a>
                                                    @endif
                                                    @if(!empty($attachment['download_url']))
                                                        <a class="file-action download" href="{{ $attachment['download_url'] }}">
                                                            <i class="bi bi-download"></i>
                                                            تنزيل
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="empty-box">لم يقم الطالب برفع ملفات مع هذه المهمة.</div>
                                @endif
                            </div>
                        </div>

                        <aside class="side-panel">
                            <div class="section-title">التقييم الحالي</div>
                            <div class="small text-muted mb-2">الدرجة الحالية: {{ data_get($submission, 'score', '-') ?? '-' }}</div>
                            <div class="current-review">{{ data_get($submission, 'feedback') ?: 'لا توجد ملاحظات محفوظة بعد.' }}</div>

                            <div class="review-actions">
                                @if(data_get($submission, 'status') !== 'accepted')
                                <details class="review-toggle accept">
                                    <summary>اعتماد المهمة</summary>
                                    <form method="POST" action="{{ route('supervisor.jisr-reviews.review', ['id' => data_get($submission, 'id')]) }}" class="review-form">
                                        @csrf
                                        <input type="hidden" name="status" value="accepted">
                                        <div class="mb-3">
                                            <label class="form-label">الدرجة</label>
                                            <input type="number" name="score" class="form-control" min="0" max="{{ data_get($submission, 'task.max_score', 100) }}" value="{{ data_get($submission, 'score') ?? data_get($submission, 'task.max_score', 100) }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">ملاحظات المشرف</label>
                                            <textarea name="feedback" class="form-control" rows="3" required>{{ data_get($submission, 'feedback') ?: 'أحسنت، تم اعتماد الحل.' }}</textarea>
                                        </div>
                                        <button type="submit" class="btn btn-success w-100">حفظ الاعتماد</button>
                                    </form>
                                </details>
                                @endif

                                <details class="review-toggle reject">
                                    <summary>إرجاع المهمة</summary>
                                    <form method="POST" action="{{ route('supervisor.jisr-reviews.review', ['id' => data_get($submission, 'id')]) }}" class="review-form">
                                        @csrf
                                        <input type="hidden" name="status" value="rejected">
                                        <div class="mb-3">
                                            <label class="form-label">الدرجة</label>
                                            <input type="number" name="score" class="form-control" min="0" max="{{ data_get($submission, 'task.max_score', 100) }}" value="{{ data_get($submission, 'score') ?? 0 }}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">سبب الإرجاع أو التعديل المطلوب</label>
                                            <textarea name="feedback" class="form-control" rows="3" placeholder="اكتب للطالب ما الذي يحتاج تعديله" required>{{ data_get($submission, 'feedback') }}</textarea>
                                        </div>
                                        <button type="submit" class="btn btn-danger w-100">حفظ الإرجاع</button>
                                    </form>
                                </details>
                            </div>
                        </aside>
                    </section>
                @endforeach
            </div>
        </article>
    @empty
        <div class="alert alert-warning rounded-4 border-0 shadow-sm">
            لا توجد حلول مرسلة في برنامج الجسر حاليًا.
        </div>
    @endforelse
</div>
@endsection
