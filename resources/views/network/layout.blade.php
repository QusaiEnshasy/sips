<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'إدارة الشبكة')</title>
    <style>
        :root {
            --ink: #17211f;
            --muted: #62706d;
            --line: #d9e2df;
            --page: #f5f7f6;
            --surface: #ffffff;
            --teal: #0f766e;
            --teal-dark: #12413d;
            --amber: #b45309;
            --red: #b91c1c;
            --violet: #6d28d9;
            --green-soft: #e8f5f2;
            --amber-soft: #fff4df;
            --red-soft: #fdecec;
            --violet-soft: #f0e9ff;
            --shadow: 0 12px 30px rgba(23, 33, 31, .08);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--page);
            color: var(--ink);
            font-family: Tahoma, Arial, sans-serif;
            line-height: 1.65;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        input,
        select,
        textarea {
            font: inherit;
        }

        .app-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
        }

        .sidebar {
            background: var(--teal-dark);
            color: #ecfffb;
            padding: 22px 18px;
            position: sticky;
            top: 0;
            min-height: 100vh;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
        }

        .brand-mark {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            display: grid;
            place-items: center;
            background: #d7fff5;
            color: var(--teal-dark);
        }

        .brand strong {
            display: block;
            font-size: 1.05rem;
        }

        .brand span {
            color: #bce9e0;
            font-size: .82rem;
        }

        .nav {
            display: grid;
            gap: 8px;
        }

        .nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 44px;
            padding: 10px 12px;
            border-radius: 8px;
            color: #dff9f3;
        }

        .nav a:hover,
        .nav a.active {
            background: rgba(255, 255, 255, .13);
            color: #ffffff;
        }

        .icon {
            width: 18px;
            height: 18px;
            flex: 0 0 18px;
        }

        .main {
            min-width: 0;
            padding: 28px;
        }

        .page-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 18px;
        }

        .page-title {
            margin: 0;
            font-size: clamp(1.45rem, 3vw, 2.15rem);
            line-height: 1.25;
        }

        .page-subtitle {
            color: var(--muted);
            margin: 6px 0 0;
        }

        .toolbar,
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .button,
        .ghost-button,
        .danger-button {
            border: 0;
            min-height: 40px;
            border-radius: 8px;
            padding: 9px 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            white-space: nowrap;
        }

        .button {
            background: var(--teal);
            color: white;
            box-shadow: 0 8px 16px rgba(15, 118, 110, .18);
        }

        .ghost-button {
            background: var(--surface);
            color: var(--ink);
            border: 1px solid var(--line);
        }

        .danger-button {
            background: var(--red-soft);
            color: var(--red);
            border: 1px solid #f3b7b7;
        }

        .button:hover,
        .ghost-button:hover,
        .danger-button:hover {
            filter: brightness(.98);
        }

        .panel {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 18px;
            margin-bottom: 18px;
        }

        .panel-title {
            margin: 0 0 14px;
            font-size: 1.1rem;
        }

        .metric-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 18px;
        }

        .metric-card {
            min-height: 128px;
            border-radius: 8px;
            background: var(--surface);
            border: 1px solid var(--line);
            padding: 16px;
            box-shadow: var(--shadow);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .metric-card strong {
            display: block;
            font-size: 1.45rem;
            margin-top: 8px;
        }

        .metric-card span {
            color: var(--muted);
            font-size: .9rem;
        }

        .metric-card.teal {
            border-top: 4px solid var(--teal);
        }

        .metric-card.amber {
            border-top: 4px solid var(--amber);
        }

        .metric-card.red {
            border-top: 4px solid var(--red);
        }

        .metric-card.violet {
            border-top: 4px solid var(--violet);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .field {
            display: grid;
            gap: 6px;
        }

        .field.wide {
            grid-column: span 2;
        }

        .field.full {
            grid-column: 1 / -1;
        }

        label {
            color: #31403d;
            font-size: .9rem;
            font-weight: 700;
        }

        input,
        select,
        textarea {
            width: 100%;
            min-height: 42px;
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 9px 11px;
            background: #fbfdfc;
            color: var(--ink);
        }

        textarea {
            min-height: 86px;
            resize: vertical;
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: 2px solid rgba(15, 118, 110, .18);
            border-color: var(--teal);
        }

        .table-wrap {
            overflow-x: auto;
            border: 1px solid var(--line);
            border-radius: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 860px;
            background: white;
        }

        th,
        td {
            padding: 12px;
            border-bottom: 1px solid var(--line);
            text-align: right;
            vertical-align: top;
        }

        th {
            background: #eef4f2;
            color: #2c3d39;
            font-size: .88rem;
        }

        tr:last-child td {
            border-bottom: 0;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 26px;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: .82rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .badge.green {
            background: var(--green-soft);
            color: var(--teal);
        }

        .badge.amber {
            background: var(--amber-soft);
            color: var(--amber);
        }

        .badge.red {
            background: var(--red-soft);
            color: var(--red);
        }

        .badge.violet {
            background: var(--violet-soft);
            color: var(--violet);
        }

        .muted {
            color: var(--muted);
            font-size: .9rem;
        }

        .stack {
            display: grid;
            gap: 6px;
        }

        .inline-form {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
        }

        .inline-form input,
        .inline-form select {
            width: auto;
            min-width: 120px;
        }

        .alert {
            padding: 12px 14px;
            border-radius: 8px;
            margin-bottom: 16px;
            border: 1px solid;
        }

        .alert.success {
            background: var(--green-soft);
            border-color: #b8e3da;
            color: #0b5f58;
        }

        .alert.error {
            background: var(--red-soft);
            border-color: #f2b6b6;
            color: var(--red);
        }

        .pagination {
            margin-top: 14px;
        }

        .pagination nav > div:first-child {
            display: none;
        }

        .empty {
            padding: 28px;
            text-align: center;
            color: var(--muted);
        }

        @media (max-width: 1100px) {
            .metric-grid,
            .form-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .app-shell {
                display: block;
            }

            .sidebar {
                position: static;
                min-height: 0;
            }

            .nav {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .main {
                padding: 18px;
            }

            .page-head {
                display: grid;
            }

            .metric-grid,
            .form-grid {
                grid-template-columns: 1fr;
            }

            .field.wide {
                grid-column: auto;
            }

            .inline-form input,
            .inline-form select {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <a class="brand" href="{{ route('network.dashboard') }}">
                <span class="brand-mark" aria-hidden="true">
                    <svg class="icon" viewBox="0 0 24 24" fill="none">
                        <path d="M4 13a10 10 0 0 1 16 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M8 16a5 5 0 0 1 8 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M12 20h.01" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                    </svg>
                </span>
                <span>
                    <strong>إدارة الشبكة</strong>
                    <span>مشتركين ومبيعات</span>
                </span>
            </a>

            <nav class="nav" aria-label="القائمة الرئيسية">
                <a class="{{ request()->routeIs('network.dashboard') ? 'active' : '' }}" href="{{ route('network.dashboard') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M4 13h6V4H4v9Zm10 7h6V4h-6v16ZM4 20h6v-4H4v4Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                    <span>اللوحة</span>
                </a>
                <a class="{{ request()->routeIs('network.subscribers.*') ? 'active' : '' }}" href="{{ route('network.subscribers.index') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M16 11a4 4 0 1 0-8 0M5 21a7 7 0 0 1 14 0" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><path d="M18 8h3M19.5 6.5v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    <span>المشتركين</span>
                </a>
                <a class="{{ request()->routeIs('network.sales.*') ? 'active' : '' }}" href="{{ route('network.sales.index') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M4 8h16v11H4V8Z" stroke="currentColor" stroke-width="2"/><path d="M7 8V5h10v3M8 13h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    <span>بيع البطاقات</span>
                </a>
                <a class="{{ request()->routeIs('network.expenses.*') ? 'active' : '' }}" href="{{ route('network.expenses.index') }}">
                    <svg class="icon" viewBox="0 0 24 24" fill="none"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M9 8h6M9 12h6M9 16h3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    <span>المصاريف</span>
                </a>
            </nav>
        </aside>

        <main class="main">
            @if (session('success'))
                <div class="alert success">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert error">
                    <strong>راجع البيانات:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
