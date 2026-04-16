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
        <span>Dashboard</span>
    </a>

    <a href="{{ route('supervisor.applications.index') }}" class="nav-link-custom {{ request()->routeIs('supervisor.applications.*') ? 'active' : '' }}">
        <i class="bi bi-file-earmark-text"></i>
        <span>Applications</span>
    </a>

    <a href="{{ route('supervisor.students.index') }}" class="nav-link-custom {{ request()->routeIs('supervisor.students.index') ? 'active' : '' }}">
        <i class="bi bi-people"></i>
        <span>Students</span>
    </a>

    <a href="{{ route('supervisor.students.pending') }}" class="nav-link-custom {{ request()->routeIs('supervisor.students.pending') ? 'active' : '' }}">
        <i class="bi bi-person-check"></i>
        <span>Pending Students</span>
    </a>

    <a href="{{ route('supervisor.weekly-tasks') }}" class="nav-link-custom {{ request()->routeIs('supervisor.weekly-tasks') ? 'active' : '' }}">
        <i class="bi bi-journal-check"></i>
        <span>Weekly Tasks</span>
    </a>

    <div style="margin-top: auto;">
        <hr class="opacity-25" />
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="nav-link-custom text-danger border-0 bg-transparent w-100 text-start">
                <i class="bi bi-box-arrow-left"></i>
                <span>Logout</span>
            </button>
        </form>
    </div>
</nav>
