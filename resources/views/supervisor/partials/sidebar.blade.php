<nav class="sidebar" id="sidebar">
    <div class="brand-logo">
        <div class="logo-box">
            <i class="bi bi-mortarboard-fill"></i>
        </div>
        <div>
            <h6 class="fw-bold mb-0">TrainEd</h6>
            <small class="text-muted">Training Platform</small>
        </div>
    </div>

    <a href="{{ route('supervisor.dashboard') }}" class="nav-link-custom {{ request()->routeIs('supervisor.dashboard') ? 'active' : '' }}">
        <i class="bi bi-grid-fill"></i>
        <span>لوحة التحكم</span>
    </a>

    <a href="{{ route('supervisor.students.index') }}" class="nav-link-custom {{ request()->routeIs('supervisor.students.index') ? 'active' : '' }}">
        <i class="bi bi-people"></i>
        <span>الطلاب</span>
    </a>

    <a href="{{ route('supervisor.jisr-reviews') }}" class="nav-link-custom {{ request()->routeIs('supervisor.jisr-reviews*') ? 'active' : '' }}">
        <i class="bi bi-clipboard-check"></i>
        <span>تقييم برنامج الجسر</span>
    </a>

    <a href="{{ route('supervisor.weekly-tasks') }}" class="nav-link-custom {{ request()->routeIs('supervisor.weekly-tasks') ? 'active' : '' }}">
        <i class="bi bi-journal-check"></i>
        <span>المهام الأسبوعية</span>
    </a>

    <a href="{{ route('notifications') }}" class="nav-link-custom {{ request()->routeIs('notifications') ? 'active' : '' }}">
        <i class="bi bi-bell"></i>
        <span>الإشعارات</span>
    </a>

    <div style="margin-top: auto;">
        <hr class="opacity-25" />
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="nav-link-custom text-danger border-0 bg-transparent w-100 text-start">
                <i class="bi bi-box-arrow-left"></i>
                <span>تسجيل الخروج</span>
            </button>
        </form>
    </div>
</nav>
