
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%);
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        .login-card {
            width: 100%;
            max-width: 460px;
            background: #fff;
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.2);
            position: relative;
        }

        .logo-box {
            width: 72px;
            height: 72px;
            margin: 0 auto 16px;
            border-radius: 18px;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
        }

        .form-label {
            font-weight: 700;
            color: #1f2937;
        }

        .form-control {
            border-radius: 14px;
            padding: 12px 14px;
        }

        .btn-login {
            width: 100%;
            border: 0;
            border-radius: 14px;
            padding: 13px;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, #2563eb, #7c3aed);
        }

        .switcher button {
            border: 0;
            background: transparent;
            font-weight: 700;
            color: #64748b;
            padding: 0;
        }

        .switcher button.active {
            color: #111827;
        }

        .lang-switcher {
            position: absolute;
            top: 18px;
            right: 18px;
            display: flex;
            gap: 8px;
        }

        .lang-btn {
            border: 1px solid #dbe3f0;
            background: #f8fafc;
            color: #475569;
            border-radius: 999px;
            padding: 6px 12px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
        }

        .lang-btn.active {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
        }
    </style>
</head>

<body>
    <div class="login-card">
        <div class="lang-switcher">
            <button type="button" class="lang-btn" id="langEn">EN</button>
            <button type="button" class="lang-btn" id="langAr">AR</button>
        </div>

        <div class="text-center mb-4">
            <div class="logo-box">
                <i class="bi bi-mortarboard-fill"></i>
            </div>
            <h2 class="fw-bold mb-1" id="pageTitle">SIP Login</h2>
            <p class="text-muted mb-0" id="pageSubtitle">Sign in with university ID or email</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger mb-3">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login.process') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label d-flex align-items-center gap-2 switcher">
                    <button type="button" id="idModeBtn" class="active">University ID</button>
                    <span>/</span>
                    <button type="button" id="emailModeBtn">Email</button>
                </label>
                <input
                    type="text"
                    name="identifier"
                    id="identifier"
                    class="form-control"
                    placeholder="Enter university ID"
                    value="{{ old('identifier') }}"
                    required
                >
            </div>

            <div class="mb-3">
                <label for="password" class="form-label" id="passwordLabel">Password</label>
                <input
                    type="password"
                    name="password"
                    id="password"
                    class="form-control"
                    placeholder="********"
                    required
                >
            </div>

            <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label" for="remember" id="rememberLabel">Remember me</label>
            </div>

            <button type="submit" class="btn-login" id="submitBtn">
                <i class="bi bi-box-arrow-in-right me-1"></i>
                Sign In
            </button>
        </form>
    </div>

    <script>
        const identifier = document.getElementById('identifier');
        const idModeBtn = document.getElementById('idModeBtn');
        const emailModeBtn = document.getElementById('emailModeBtn');
        const langEn = document.getElementById('langEn');
        const langAr = document.getElementById('langAr');
        const pageTitle = document.getElementById('pageTitle');
        const pageSubtitle = document.getElementById('pageSubtitle');
        const passwordLabel = document.getElementById('passwordLabel');
        const rememberLabel = document.getElementById('rememberLabel');
        const submitBtn = document.getElementById('submitBtn');

        const translations = {
            en: {
                dir: 'ltr',
                title: 'SIP Login',
                subtitle: 'Sign in with university ID or email',
                universityId: 'University ID',
                email: 'Email',
                idPlaceholder: 'Enter university ID',
                emailPlaceholder: 'Enter email',
                password: 'Password',
                remember: 'Remember me',
                signIn: 'Sign In'
            },
            ar: {
                dir: 'rtl',
                title: 'تسجيل الدخول',
                subtitle: 'سجل الدخول باستخدام الرقم الجامعي أو البريد الإلكتروني',
                universityId: 'الرقم الجامعي',
                email: 'البريد الإلكتروني',
                idPlaceholder: 'أدخل الرقم الجامعي',
                emailPlaceholder: 'أدخل البريد الإلكتروني',
                password: 'كلمة المرور',
                remember: 'تذكرني',
                signIn: 'تسجيل الدخول'
            }
        };

        let currentLang = localStorage.getItem('lang') || 'ar';
        let currentMode = 'id';

        function setMode(mode) {
            currentMode = mode;
            const isEmail = mode === 'email';
            identifier.type = isEmail ? 'email' : 'text';
            identifier.placeholder = isEmail
                ? translations[currentLang].emailPlaceholder
                : translations[currentLang].idPlaceholder;
            idModeBtn.classList.toggle('active', !isEmail);
            emailModeBtn.classList.toggle('active', isEmail);
        }

        function applyLanguage(lang) {
            currentLang = lang;
            localStorage.setItem('lang', lang);
            document.documentElement.lang = lang;
            document.documentElement.dir = translations[lang].dir;

            pageTitle.textContent = translations[lang].title;
            pageSubtitle.textContent = translations[lang].subtitle;
            idModeBtn.textContent = translations[lang].universityId;
            emailModeBtn.textContent = translations[lang].email;
            passwordLabel.textContent = translations[lang].password;
            rememberLabel.textContent = translations[lang].remember;
            submitBtn.innerHTML = '<i class="bi bi-box-arrow-in-right me-1"></i> ' + translations[lang].signIn;

            langEn.classList.toggle('active', lang === 'en');
            langAr.classList.toggle('active', lang === 'ar');
            setMode(currentMode);
        }

        idModeBtn.addEventListener('click', () => setMode('id'));
        emailModeBtn.addEventListener('click', () => setMode('email'));
        langEn.addEventListener('click', () => applyLanguage('en'));
        langAr.addEventListener('click', () => applyLanguage('ar'));

        if ((identifier.value || '').includes('@')) {
            setMode('email');
        }

        applyLanguage(currentLang);
    </script>
</body>

</html>
