<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>التقييم النهائي</title>
    <style>
        :root {
            --bg: #f5f7fb;
            --card: #ffffff;
            --text: #172033;
            --muted: #64748b;
            --line: #e2e8f0;
            --primary: #2563eb;
            --success: #059669;
            --success-soft: #dcfce7;
            --purple: #7c3aed;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(circle at top right, rgba(124, 58, 237, 0.13), transparent 32%),
                linear-gradient(135deg, #f8fafc, var(--bg));
            color: var(--text);
            font-family: Tahoma, Arial, sans-serif;
        }

        .page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px;
        }

        .card {
            width: min(920px, 100%);
            overflow: hidden;
            border: 1px solid var(--line);
            border-radius: 32px;
            background: var(--card);
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.12);
        }

        .hero {
            padding: 34px;
            text-align: center;
            background:
                radial-gradient(circle at top left, rgba(34, 197, 94, 0.18), transparent 34%),
                linear-gradient(135deg, #ffffff, #f0fdf4);
            border-bottom: 1px solid var(--line);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 9px 16px;
            border-radius: 999px;
            background: var(--success-soft);
            color: var(--success);
            font-weight: 800;
            font-size: 14px;
        }

        h1 {
            margin: 18px 0 8px;
            font-size: clamp(28px, 4vw, 42px);
            line-height: 1.25;
        }

        .subtitle {
            margin: 0;
            color: var(--muted);
        }

        .score {
            margin: 24px auto 0;
            width: 190px;
            height: 190px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: #ffffff;
            border: 10px solid #bbf7d0;
            box-shadow: inset 0 0 0 1px var(--line), 0 14px 30px rgba(5, 150, 105, 0.16);
        }

        .score strong {
            display: block;
            font-size: 48px;
            line-height: 1;
            color: var(--success);
        }

        .score span {
            color: var(--muted);
            font-weight: 700;
        }

        .content {
            padding: 28px 34px 34px;
        }

        .meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 20px;
        }

        .meta-item,
        .evaluation-box {
            border: 1px solid var(--line);
            border-radius: 20px;
            background: #f8fafc;
            padding: 16px;
        }

        .meta-item small,
        .evaluation-box small {
            display: block;
            margin-bottom: 6px;
            color: var(--muted);
            font-weight: 700;
        }

        .meta-item strong {
            font-size: 16px;
        }

        .evaluations {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .evaluation-box h2 {
            margin: 0 0 14px;
            color: var(--purple);
            font-size: 20px;
        }

        .evaluation-box .mark {
            font-size: 28px;
            font-weight: 900;
            color: var(--text);
        }

        .note {
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid var(--line);
            color: #334155;
            line-height: 1.8;
            white-space: pre-wrap;
        }

        .actions {
            margin-top: 24px;
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 160px;
            border-radius: 16px;
            padding: 13px 18px;
            text-decoration: none;
            font-weight: 900;
        }

        .btn-primary {
            background: var(--primary);
            color: #ffffff;
        }

        .btn-soft {
            background: #eef2ff;
            color: #3730a3;
        }

        @media (max-width: 760px) {
            .page {
                padding: 14px;
            }

            .hero,
            .content {
                padding: 22px;
            }

            .meta,
            .evaluations {
                grid-template-columns: 1fr;
            }

            .score {
                width: 150px;
                height: 150px;
            }

            .score strong {
                font-size: 38px;
            }
        }
    </style>
</head>
<body>
@php
    $user = auth()->user();
    $application->loadMissing(['student', 'opportunity.companyUser']);

    $backUrl = url('/');
    $dashboardUrl = url('/');

    if ($user?->role === 'student') {
        $backUrl = route('student.workspace.index');
        $dashboardUrl = route('student.dashboard');
    } elseif ($user?->role === 'company') {
        $backUrl = route('company.applicants.show', $application->id);
        $dashboardUrl = route('company.training-tasks');
    } elseif ($user?->role === 'supervisor') {
        $backUrl = route('supervisor.applications.show', $application->id);
        $dashboardUrl = route('supervisor.dashboard');
    }

    $studentName = $application->student?->name ?? 'الطالب';
    $programTitle = $application->opportunity?->title ?? 'التدريب';
    $companyName = $application->opportunity?->companyUser?->company_name
        ?: ($application->opportunity?->companyUser?->name ?? '-');
@endphp

<main class="page">
    <section class="card" aria-label="التقييم النهائي">
        <div class="hero">
            <span class="badge">تم إنهاء التدريب بنجاح</span>
            <h1>مبارك، النتيجة النهائية جاهزة</h1>
            <p class="subtitle">تم اعتماد تقييم الشركة والمشرف وحساب العلامة النهائية.</p>

            <div class="score">
                <div>
                    <strong>{{ $application->final_score ?? 0 }}</strong>
                    <span>من 100</span>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="meta">
                <div class="meta-item">
                    <small>الطالب</small>
                    <strong>{{ $studentName }}</strong>
                </div>
                <div class="meta-item">
                    <small>البرنامج</small>
                    <strong>{{ $programTitle }}</strong>
                </div>
                <div class="meta-item">
                    <small>الشركة</small>
                    <strong>{{ $companyName }}</strong>
                </div>
                <div class="meta-item">
                    <small>تاريخ الإنهاء</small>
                    <strong>{{ optional($application->training_completed_at)->format('Y-m-d H:i') }}</strong>
                </div>
            </div>

            <div class="evaluations">
                <article class="evaluation-box">
                    <h2>تقييم الشركة</h2>
                    <small>العلامة</small>
                    <div class="mark">{{ $application->company_final_score ?? '-' }}/100</div>
                    <div class="note">{{ $application->company_final_note ?: 'لا توجد ملاحظات.' }}</div>
                </article>

                <article class="evaluation-box">
                    <h2>تقييم المشرف</h2>
                    <small>العلامة</small>
                    <div class="mark">{{ $application->supervisor_final_score ?? '-' }}/100</div>
                    <div class="note">{{ $application->supervisor_final_note ?: 'لا توجد ملاحظات.' }}</div>
                </article>
            </div>

            <div class="actions">
                <a class="btn btn-primary" href="{{ $backUrl }}">الرجوع للتفاصيل</a>
                <a class="btn btn-soft" href="{{ $dashboardUrl }}">العودة للوحة التحكم</a>
            </div>
        </div>
    </section>
</main>
</body>
</html>
