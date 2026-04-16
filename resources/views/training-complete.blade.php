<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Training Complete</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100">
    @php
    $user = auth()->user();
    $backUrl = url('/');
    if ($user?->role === 'student') {
        $backUrl = route('student.workspace.index');
    } elseif ($user?->role === 'company') {
        $backUrl = route('company.applicants.show', $application->id);
    } elseif ($user?->role === 'supervisor') {
        $backUrl = route('supervisor.applications.show', $application->id);
    }
    @endphp

    <div class="mx-auto flex min-h-screen max-w-3xl items-center justify-center p-6">
        <div class="w-full rounded-2xl bg-white p-8 text-center shadow">
            <h1 class="text-3xl font-bold text-emerald-600">مبارك! تم إنهاء التدريب بنجاح</h1>
            <p class="mt-3 text-slate-600">النتيجة النهائية للطالب:</p>
            <p class="mt-2 text-5xl font-extrabold text-slate-800">{{ $application->final_score ?? 0 }}/100</p>
            <p class="mt-2 text-sm text-slate-500">Application #{{ $application->id }}</p>
            <p class="mt-1 text-sm text-slate-500">تاريخ الإنهاء: {{ optional($application->training_completed_at)->format('Y-m-d H:i') }}</p>

            <div class="mt-5 rounded-lg border border-slate-200 bg-slate-50 p-4 text-right">
                <p class="text-sm text-slate-600"><strong>تقييم الشركة:</strong> {{ $application->company_final_score ?? '-' }}/100</p>
                <p class="text-sm text-slate-600 mt-1"><strong>ملاحظة الشركة:</strong> {{ $application->company_final_note ?? '-' }}</p>
                <p class="text-sm text-slate-600 mt-3"><strong>تقييم المشرف:</strong> {{ $application->supervisor_final_score ?? '-' }}/100</p>
                <p class="text-sm text-slate-600 mt-1"><strong>ملاحظة المشرف:</strong> {{ $application->supervisor_final_note ?? '-' }}</p>
            </div>

            <a href="{{ $backUrl }}" class="mt-6 inline-block rounded bg-sky-600 px-5 py-2 text-white hover:bg-sky-700">الرجوع</a>
        </div>
    </div>
</body>
</html>
