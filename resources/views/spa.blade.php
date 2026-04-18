<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>SIP - App</title>
    @php
        $manifestPath = public_path('.vite/manifest.json');
        $manifest = file_exists($manifestPath)
            ? json_decode(file_get_contents($manifestPath), true)
            : [];

        $entry = $manifest['index.html'] ?? null;
        $jsAsset = isset($entry['file']) ? 'assets/' . basename($entry['file']) : null;
        $cssAssets = collect($entry['css'] ?? [])
            ->map(fn ($asset) => 'assets/' . basename($asset))
            ->values();
        $jsVersion = $jsAsset ? filemtime(public_path($jsAsset)) : time();
    @endphp
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    @foreach($cssAssets as $cssAsset)
        <link rel="stylesheet" crossorigin href="/{{ $cssAsset }}?v={{ filemtime(public_path($cssAsset)) }}">
    @endforeach
    <style>
        .logout-confirm-overlay {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, .45);
            backdrop-filter: blur(4px);
            z-index: 9999;
            padding: 16px;
        }
        .logout-confirm-overlay.show { display: flex; }
        .logout-confirm-modal {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border: 1px solid rgba(15, 23, 42, .1);
            border-radius: 16px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, .2);
            text-align: center;
            padding: 26px 22px 20px;
        }
        .logout-confirm-icon {
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
        .logout-confirm-title {
            margin: 0 0 8px;
            font-size: 1.2rem;
            font-weight: 700;
            color: #0f172a;
        }
        .logout-confirm-text {
            margin: 0 0 18px;
            color: #64748b;
            line-height: 1.7;
        }
        .logout-confirm-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .logout-confirm-btn {
            min-width: 120px;
            border-radius: 10px;
            font-weight: 600;
            border: 1px solid transparent;
            padding: 10px 14px;
            cursor: pointer;
        }
        .logout-confirm-btn.primary {
            background: #dc2626;
            color: #fff;
        }
        .logout-confirm-btn.cancel {
            background: #fff;
            border-color: #d1d5db;
            color: #334155;
        }
        @media (max-width: 576px) {
            .logout-confirm-actions {
                flex-direction: column;
            }
            .logout-confirm-btn {
                width: 100%;
            }
        }
    </style>
</head>
<body style="margin:0;min-height:100vh;background:#f4f6fb;">
    <div id="app"></div>
    <form id="globalLogoutForm" method="POST" action="{{ route('logout') }}" style="display:none;">
        @csrf
    </form>
    <div class="logout-confirm-overlay" id="logoutConfirmOverlay">
        <div class="logout-confirm-modal">
            <div class="logout-confirm-icon">
                <i class="bi bi-box-arrow-right"></i>
            </div>
            <h5 class="logout-confirm-title">تأكيد تسجيل الخروج</h5>
            <p class="logout-confirm-text">هل أنت متأكد أنك تريد تسجيل الخروج؟</p>
            <div class="logout-confirm-actions">
                <button type="button" class="logout-confirm-btn primary" id="logoutConfirmYes">تسجيل الخروج</button>
                <button type="button" class="logout-confirm-btn cancel" id="logoutConfirmNo">إلغاء</button>
            </div>
        </div>
    </div>

    <script>
        window.__AUTH_USER__ = @json(request()->routeIs('login', 'register') ? null : auth()->user());
        window.__FLASH__ = {!! json_encode([
            'success' => session('success'),
            'error' => session('error'),
            'status' => session('status'),
            'warning' => session('warning'),
        ]) !!};

        (function () {
            const overlay = document.getElementById('logoutConfirmOverlay');
            const confirmBtn = document.getElementById('logoutConfirmYes');
            const cancelBtn = document.getElementById('logoutConfirmNo');
            if (!overlay || !confirmBtn || !cancelBtn) return;

            let pendingAction = null;

            const openModal = (action) => {
                pendingAction = action;
                overlay.classList.add('show');
            };

            const closeModal = () => {
                overlay.classList.remove('show');
                pendingAction = null;
            };

            const submitLogout = () => {
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                localStorage.removeItem('userType');

                const form = document.getElementById('globalLogoutForm');
                if (form) {
                    form.submit();
                    return;
                }

                window.location.href = '/login';
            };

            const isLogoutTrigger = (node) => {
                const el = node.closest('button, a');
                if (!el) return false;

                // Never treat dialog action buttons as logout triggers.
                if (el.id === 'logoutConfirmYes' || el.id === 'logoutConfirmNo') return false;
                if (el.closest('#logoutConfirmOverlay')) return false;

                if (el.dataset.logoutTrigger === '1') return true;
                if (el.querySelector('.bi-box-arrow-left')) return true;

                const text = (el.textContent || '').trim().toLowerCase();
                return text.includes('logout') || text.includes('تسجيل الخروج');
            };

            document.addEventListener('click', (e) => {
                const appLogo = e.target.closest('.sidebar .logo-area, .mobile-sidebar .logo-area');
                if (appLogo) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation?.();
                    return;
                }

                if (!isLogoutTrigger(e.target)) return;

                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation?.();

                openModal(submitLogout);
            }, true);

            confirmBtn.addEventListener('click', async () => {
                const action = pendingAction;
                closeModal();
                if (action) await action();
            });

            cancelBtn.addEventListener('click', closeModal);
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) closeModal();
            });
        })();
    </script>
    @if($jsAsset)
        <script type="module" crossorigin src="/{{ $jsAsset }}?v={{ $jsVersion }}"></script>
    @endif
</body>
</html>


