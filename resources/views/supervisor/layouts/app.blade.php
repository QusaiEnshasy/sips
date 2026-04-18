<!doctype html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>@yield('title', 'Supervisor Dashboard')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

    <style>
        :root {
            --sidebar-width: 260px;
            --primary-purple: #6366f1;
            --bg-light: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #f1f5f9;
            --sidebar-bg: #ffffff;
        }

        [data-theme="dark"] {
            --bg-light: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --sidebar-bg: #1e293b;
        }

        body {
            font-family: "Inter", sans-serif;
            background-color: var(--bg-light);
            color: var(--text-main);
            margin: 0;
            display: flex;
            transition: .3s;
            overflow-x: hidden;
        }

        .mobile-menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1060;
            width: 45px;
            height: 45px;
            background: var(--primary-purple);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 1.2rem;
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
        }

        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            position: fixed;
            padding: 24px;
            display: flex;
            flex-direction: column;
            transition: .3s;
            z-index: 1050;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 40px;
        }

        .logo-box {
            width: 40px;
            height: 40px;
            background: var(--primary-purple);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
        }

        .nav-link-custom {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 10px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 8px;
            transition: .3s;
        }

        .nav-link-custom.active {
            background: var(--primary-purple);
            color: white;
        }

        .nav-link-custom:hover:not(.active) {
            background: var(--border-color);
            color: var(--primary-purple);
        }

        .main-content {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            padding: 40px;
            min-height: 100vh;
        }

        [dir="rtl"] .main-content {
            margin-left: 0;
            margin-right: var(--sidebar-width);
        }

        .theme-toggle-btn {
            width: 45px;
            height: 45px;
            background: #f1efff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            color: #6366f1;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .lang-switcher {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .lang-btn {
            border: 1px solid var(--border-color);
            background: var(--card-bg);
            color: var(--text-main);
            border-radius: 999px;
            padding: 7px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }

        .lang-btn.active {
            background: var(--primary-purple);
            border-color: var(--primary-purple);
            color: white;
        }

        [data-theme="dark"] .theme-toggle-btn {
            background: #334155;
            color: #fbbf24;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 24px;
            border: 1px solid var(--border-color);
            transition: .3s;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, .05);
        }

        .icon-rounded {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 20px;
            color: white;
        }

        .student-card {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 24px;
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, .04);
            transition: .3s;
            height: 100%;
        }

        .student-card:hover {
            transform: translateY(-5px);
        }

        .avatar-lg {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            color: white;
            background: var(--primary-purple);
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-on-track {
            background: #f0fdf4;
            color: #22c55e;
        }

        .status-at-risk {
            background: #fffbeb;
            color: #f59e0b;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 8px;
            color: var(--text-muted);
        }

        .progress {
            height: 8px;
            border-radius: 10px;
            background: var(--bg-light);
            margin-bottom: 20px;
        }

        .card-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .btn-outline-card {
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 8px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-main);
            background: var(--card-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: .3s;
            text-decoration: none;
        }

        .btn-outline-card:hover {
            background: var(--bg-light);
            border-color: var(--primary-purple);
            color: var(--primary-purple);
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }

            .sidebar {
                transform: translateX(-100%);
                width: 240px;
                box-shadow: 2px 0 20px rgba(0, 0, 0, 0.1);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                margin-right: 0;
                width: 100%;
                padding: 80px 16px 40px 16px;
            }
        }

        .logout-modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .45);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            padding: 16px;
        }

        .logout-modal-overlay.show {
            display: flex;
        }

        .logout-modal-card {
            width: 100%;
            max-width: 420px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, .2);
            text-align: center;
            padding: 26px 22px 20px;
        }

        .logout-modal-icon {
            width: 72px;
            height: 72px;
            margin: 0 auto 14px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 34px;
            color: #dc2626;
            background: rgba(220, 38, 38, .12);
        }

        .logout-modal-title {
            font-weight: 700;
            margin-bottom: 8px;
        }

        .logout-modal-text {
            color: var(--text-muted);
            margin-bottom: 18px;
        }

        .logout-modal-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .logout-modal-actions .btn {
            min-width: 120px;
            border-radius: 10px;
            font-weight: 600;
        }
    </style>

    @stack('styles')
</head>

<body>
    <button class="mobile-menu-toggle" id="mobileMenuToggle" onclick="toggleSidebar()">
        <i class="bi bi-list"></i>
    </button>

    @include('supervisor.partials.sidebar')

    <main class="main-content">
        @yield('content')
    </main>

    <div class="logout-modal-overlay" id="logoutConfirmModal">
        <div class="logout-modal-card">
            <div class="logout-modal-icon">
                <i class="bi bi-box-arrow-right"></i>
            </div>
            <h5 class="logout-modal-title">تأكيد تسجيل الخروج</h5>
            <p class="logout-modal-text">هل أنت متأكد أنك تريد تسجيل الخروج؟</p>
            <div class="logout-modal-actions">
                <button type="button" class="btn btn-danger" id="confirmLogoutAction">تسجيل الخروج</button>
                <button type="button" class="btn btn-light border" id="cancelLogoutAction">إلغاء</button>
            </div>
        </div>
    </div>

    <script>
        const htmlElement = document.documentElement;
        const savedTheme = localStorage.getItem('theme') || 'light';
        const savedLang = localStorage.getItem('lang') || 'ar';
        htmlElement.setAttribute('data-theme', savedTheme);

        const bladeTranslations = {
            en: {
                brand: 'TrainEd',
                platform: 'Training Platform',
                dashboard: 'Dashboard',
                applications: 'Applications',
                students: 'Students',
                pending_students: 'Pending Students',
                weekly_tasks: 'Weekly Tasks',
                logout: 'Logout',
                supervisor_dashboard: 'Supervisor Dashboard',
                monitor_students: 'Monitor your students, approvals, and active training progress.',
                total_students: 'Total Students',
                pending: 'Pending',
                active: 'Active',
                rejected: 'Rejected',
                all_linked_students: 'All linked students',
                awaiting_approval: 'Awaiting approval',
                active_students: 'Active students',
                rejected_students: 'Rejected students',
                approved_training_students: 'Approved Training Students',
                approved_training_subtitle: 'Students with approved applications currently under your supervision.',
                view_all_students: 'View All Students',
                progress: 'Progress',
                open_board: 'Open Board',
                details: 'Details',
                no_approved_students: 'No approved student applications found yet.',
                student_applications: 'Student Applications',
                review_applications: 'Review applications submitted by your students to companies',
                total_applications: 'Total Applications',
                all_student_requests: 'All student requests',
                waiting_review: 'Waiting your review',
                supervisor_approved: 'Supervisor approved',
                supervisor_rejected: 'Supervisor rejected',
                applications_table: 'Applications Table',
                only_linked_students: 'Only applications submitted by students linked to your supervisor code are shown here',
                student: 'Student',
                company_opportunity: 'Company / Opportunity',
                company_status: 'Company Status',
                supervisor_status: 'Supervisor Status',
                final_status: 'Final Status',
                actions: 'Actions',
                approved: 'Approved',
                pending_status: 'Pending',
                reject: 'Reject',
                approve: 'Approve',
                decision_submitted: 'Decision already submitted',
                no_applications: 'No applications found for your students',
                pending_students_title: 'Pending Students',
                pending_students_subtitle: 'Approve or reject newly registered students linked to your supervisor code',
                name: 'Name',
                university_id: 'University ID',
                email: 'Email',
                phone: 'Phone',
                status: 'Status',
                supervisor_code: 'Supervisor Code',
                no_pending_students: 'No pending students found.',
                students_title: 'Students',
                students_subtitle: 'Manage students linked to your supervisor code and review their current status.',
                approved_students: 'Approved Students',
                approved_students_subtitle: 'Students whose accounts are active under your supervision.',
                rejected_students_title: 'Rejected Students',
                rejected_students_subtitle: 'Students whose account requests were rejected.',
                program: 'Program'
            },
            ar: {
                brand: 'ترين إيد',
                platform: 'منصة التدريب',
                dashboard: 'لوحة التحكم',
                applications: 'الطلبات',
                students: 'الطلاب',
                pending_students: 'الطلاب المعلقون',
                weekly_tasks: 'المهام الأسبوعية',
                logout: 'تسجيل الخروج',
                supervisor_dashboard: 'لوحة المشرف',
                monitor_students: 'تابع طلابك وطلبات القبول وسير التدريب النشط.',
                total_students: 'إجمالي الطلاب',
                pending: 'قيد الانتظار',
                active: 'نشط',
                rejected: 'مرفوض',
                all_linked_students: 'كل الطلاب المرتبطين',
                awaiting_approval: 'بانتظار الاعتماد',
                active_students: 'الطلاب النشطون',
                rejected_students: 'الطلاب المرفوضون',
                approved_training_students: 'طلاب التدريب الموافق عليهم',
                approved_training_subtitle: 'الطلاب الذين لديهم طلبات تدريب معتمدة تحت إشرافك.',
                view_all_students: 'عرض كل الطلاب',
                progress: 'التقدم',
                open_board: 'فتح اللوحة',
                details: 'التفاصيل',
                no_approved_students: 'لا يوجد طلاب معتمدون حاليًا.',
                student_applications: 'طلبات الطلاب',
                review_applications: 'راجع الطلبات التي قدمها طلابك إلى الشركات',
                total_applications: 'إجمالي الطلبات',
                all_student_requests: 'كل طلبات الطلاب',
                waiting_review: 'بانتظار مراجعتك',
                supervisor_approved: 'وافق المشرف',
                supervisor_rejected: 'رفض المشرف',
                applications_table: 'جدول الطلبات',
                only_linked_students: 'تظهر هنا فقط الطلبات المقدمة من الطلاب المرتبطين بكود المشرف الخاص بك',
                student: 'الطالب',
                company_opportunity: 'الشركة / الفرصة',
                company_status: 'حالة الشركة',
                supervisor_status: 'حالة المشرف',
                final_status: 'الحالة النهائية',
                actions: 'الإجراءات',
                approved: 'مقبول',
                pending_status: 'قيد الانتظار',
                reject: 'رفض',
                approve: 'قبول',
                decision_submitted: 'تم إرسال القرار مسبقًا',
                no_applications: 'لا توجد طلبات لطلابك',
                pending_students_title: 'الطلاب المعلقون',
                pending_students_subtitle: 'وافق أو ارفض الطلاب الجدد المرتبطين بكود المشرف الخاص بك',
                name: 'الاسم',
                university_id: 'الرقم الجامعي',
                email: 'البريد الإلكتروني',
                phone: 'الهاتف',
                status: 'الحالة',
                supervisor_code: 'كود المشرف',
                no_pending_students: 'لا يوجد طلاب معلقون.',
                students_title: 'الطلاب',
                students_subtitle: 'إدارة الطلاب المرتبطين بكود المشرف ومراجعة حالتهم الحالية.',
                approved_students: 'الطلاب المقبولون',
                approved_students_subtitle: 'الطلاب الذين أصبحت حساباتهم نشطة تحت إشرافك.',
                rejected_students_title: 'الطلاب المرفوضون',
                rejected_students_subtitle: 'الطلاب الذين تم رفض طلبات حساباتهم.',
                program: 'البرنامج'
            }
        };

        function applyBladeLanguage(lang) {
            localStorage.setItem('lang', lang);
            htmlElement.lang = lang;
            htmlElement.dir = lang === 'ar' ? 'rtl' : 'ltr';
            document.querySelectorAll('[data-i18n]').forEach((element) => {
                const key = element.dataset.i18n;
                if (bladeTranslations[lang][key]) {
                    element.textContent = bladeTranslations[lang][key];
                }
            });
            document.querySelectorAll('[data-i18n-placeholder]').forEach((element) => {
                const key = element.dataset.i18nPlaceholder;
                if (bladeTranslations[lang][key]) {
                    element.setAttribute('placeholder', bladeTranslations[lang][key]);
                }
            });
            const enBtn = document.getElementById('langSwitchEn');
            const arBtn = document.getElementById('langSwitchAr');
            if (enBtn && arBtn) {
                enBtn.classList.toggle('active', lang === 'en');
                arBtn.classList.toggle('active', lang === 'ar');
            }
        }

        function toggleTheme() {
            const currentTheme = htmlElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            htmlElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);

            const icon = document.getElementById('themeIcon');
            if (icon) {
                icon.className = newTheme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const icon = document.getElementById('themeIcon');
            if (icon) {
                icon.className = savedTheme === 'dark' ? 'bi bi-sun-fill' : 'bi bi-moon-stars-fill';
            }
            applyBladeLanguage(savedLang);
        });

        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('mobileMenuToggle');
            sidebar.classList.toggle('show');

            if (sidebar.classList.contains('show')) {
                toggleBtn.innerHTML = '<i class="bi bi-x-lg"></i>';
            } else {
                toggleBtn.innerHTML = '<i class="bi bi-list"></i>';
            }
        }

        document.querySelectorAll('.nav-link-custom').forEach((link) => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    const sidebar = document.getElementById('sidebar');
                    const toggleBtn = document.getElementById('mobileMenuToggle');
                    sidebar.classList.remove('show');
                    toggleBtn.innerHTML = '<i class="bi bi-list"></i>';
                }
            });
        });

    </script>

    <script>
        (function() {
            let pendingLogoutForm = null;

            function byId(id) {
                return document.getElementById(id);
            }

            function openLogoutModal(form) {
                const modal = byId('logoutConfirmModal');
                if (!modal) return;
                pendingLogoutForm = form || byId('supervisorLogoutForm');
                modal.classList.add('show');
            }

            function closeLogoutModal() {
                const modal = byId('logoutConfirmModal');
                if (!modal) return;
                modal.classList.remove('show');
                pendingLogoutForm = null;
            }

            document.addEventListener('DOMContentLoaded', function() {
                const logoutBtn = byId('supervisorLogoutBtn');
                const logoutForm = byId('supervisorLogoutForm');
                const modal = byId('logoutConfirmModal');
                const confirmBtn = byId('confirmLogoutAction');
                const cancelBtn = byId('cancelLogoutAction');

                if (!modal || !confirmBtn || !cancelBtn) return;

                if (logoutBtn && logoutForm) {
                    logoutBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        openLogoutModal(logoutForm);
                    });
                }

                document.addEventListener('submit', function(e) {
                    const form = e.target;
                    if (!(form instanceof HTMLFormElement)) return;
                    if (form.method.toUpperCase() !== 'POST') return;
                    const action = (form.getAttribute('action') || '').toLowerCase();
                    if (!action.endsWith('/logout')) return;
                    e.preventDefault();
                    openLogoutModal(form);
                }, true);

                cancelBtn.addEventListener('click', closeLogoutModal);
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) closeLogoutModal();
                });

                confirmBtn.addEventListener('click', function() {
                    if (pendingLogoutForm) {
                        pendingLogoutForm.submit();
                    }
                });
            });
        })();
    </script>

    @stack('scripts')
</body>

</html>
