<nav class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme"
    id="layout-navbar">
    <!--  Brand demo (display only for navbar-full and hide on below xl) -->

    <!-- ! Not required for layout-without-menu -->
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-4 me-xl-0  d-xl-none ">
        <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
            <i class="icon-base bx bx-menu icon-md"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">

        <!-- Search -->
        <div class="navbar-nav align-items-center">
            <div class="nav-item navbar-search-wrapper mb-0">
                <a class="nav-item nav-link search-toggler px-0" href="javascript:void(0);">
                    <span class="d-inline-block text-body-secondary fw-normal" id="autocomplete"></span>
                </a>
            </div>
        </div>
        <!-- /Search -->

        <ul class="navbar-nav flex-row align-items-center ms-md-auto">

            @if (session()->has('super_admin_impersonator_id'))
                <li class="nav-item me-2">
                    <div class="d-flex align-items-center gap-2 rounded-pill border border-warning px-3 py-1 bg-label-warning">
                        <span class="small fw-semibold text-nowrap"><i class="bx bx-shield-quarter me-1"></i>Viewing {{ auth()->user()->isRole() }} · {{ auth()->user()->company?->name ?? 'Company' }}</span>
                        <form method="POST" action="{{ route('super-admin.return') }}" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-warning text-nowrap"><i class="bx bx-exit me-1"></i>Return to Super Admin</button>
                        </form>
                    </div>
                </li>
            @endif

            <!-- Style Switcher -->
            <li class="nav-item dropdown me-2 me-xl-0">
                <a class="nav-link dropdown-toggle hide-arrow" id="nav-theme" href="javascript:void(0);"
                    data-bs-toggle="dropdown">
                    <svg id="theme-toggle-icon" class="theme-icon-active" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="4"></circle><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"></path></svg>
                    <span class="d-none ms-2" id="nav-theme-text">Toggle theme</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="nav-theme-text">
                    <li>
                        <button type="button" class="dropdown-item align-items-center active"
                            data-bs-theme-value="light">
                            <span><i class="icon-base bx bx-sun icon-md me-3"></i>Light</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="dark">
                            <span><i class="icon-base bx bx-moon icon-md me-3"></i>Dark</span>
                        </button>
                    </li>
                    <li>
                        <button type="button" class="dropdown-item align-items-center" data-bs-theme-value="system">
                            <span><i class="icon-base bx bx-desktop icon-md me-3"></i>System</span>
                        </button>
                    </li>
                </ul>
            </li>
            <!-- / Style Switcher-->

            @php
                $latestNotifications = auth()->user()->unreadNotifications()->latest()->take(5)->get();
                $latestNotificationsCount = $latestNotifications->count();
            @endphp
            <li class="nav-item dropdown-notifications navbar-dropdown dropdown me-3 me-xl-2">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown"
                    data-bs-auto-close="outside" aria-expanded="false">
                    <span class="position-relative">
                        <i class="icon-base bx bx-bell icon-md"></i>
                        <span id="notificationBadge">
                            {{ $latestNotificationsCount }}
                        </span>
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end p-0">
                    <li class="dropdown-menu-header border-bottom">
                        <div class="dropdown-header d-flex align-items-center py-3">
                            <h6 class="mb-0 me-auto">Notification</h6>
                            <div class="d-flex align-items-center h6 mb-0">
                                <span class="badge bg-label-primary me-2"
                                    id="notificationBadge2">{{ $latestNotificationsCount }}
                                    New</span>
                                <a href="javascript:void(0)" class="dropdown-notifications-all p-2"
                                    data-bs-toggle="tooltip" data-bs-placement="top" title="Mark all as read">
                                    <i class="icon-base bx bx-envelope-open text-heading"></i>
                                </a>
                            </div>
                        </div>
                    </li>
                    <li class="dropdown-notifications-list scrollable-container">
                        <ul id="notificationList" class="list-group list-group-flush">
                            <!-- Notifications will be injected here -->
                            @forelse($latestNotifications as $notification)
                                @if($notification->type === 'App\Notifications\BrandApprovalRequest')
                                    <li class="list-group-item dropdown-notifications-item brand-approval-notification cursor-pointer" data-notification-url="{{ $notification->data['url'] ?? '#' }}" data-notification-id="{{ $notification->id }}">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="avatar avatar-sm">
                                                    <span class="avatar-initial rounded-circle bg-label-warning"><i class="bx bx-check-shield"></i></span>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="small mb-1 fw-semibold">{{ $notification->data['title'] ?? 'Brand Approval' }}</h6>
                                                <small class="d-block text-body mb-1">{{ $notification->data['message'] ?? '' }}</small>
                                                <small class="text-muted"><i class="bx bx-time-five me-1"></i>{{ $notification->created_at->diffForHumans() }}</small>
                                            </div>
                                            <div class="flex-shrink-0 dropdown-notifications-actions">
                                                <span class="badge badge-dot bg-primary"></span>
                                            </div>
                                        </div>
                                    </li>
                                @elseif($notification->type === 'App\Notifications\NewVisitorNotification')
                                    <li class="list-group-item dropdown-notifications-item">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="avatar avatar-sm">
                                                    @if(preg_match('/^[A-Za-z]{2}$/', (string) ($notification->data['country_code'] ?? '')))
                                                        <img src="https://flagcdn.com/w40/{{ strtolower($notification->data['country_code']) }}.png"
                                                            alt="Visitor country flag" class="rounded-circle">
                                                    @else
                                                        <span class="avatar-initial rounded-circle bg-label-info"><i class="bx bx-user-check"></i></span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="small mb-1 fw-semibold">{{ $notification->data['title'] ?? 'New Visitor' }}</h6>
                                                <small class="d-block text-body mb-1">{{ $notification->data['message'] ?? '' }}</small>
                                                <small class="text-muted"><i class="bx bx-time-five me-1"></i>{{ $notification->created_at->diffForHumans() }}</small>
                                            </div>
                                            <div class="flex-shrink-0 dropdown-notifications-actions">
                                                <span class="badge badge-dot bg-primary"></span>
                                            </div>
                                        </div>
                                    </li>
                                @else
                                    <li class="list-group-item dropdown-notifications-item">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0 me-3">
                                                <div class="avatar avatar-sm">
                                                    <span class="avatar-initial rounded-circle bg-label-primary"><i class="bx bx-bell"></i></span>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="small mb-1 fw-semibold">{{ $notification->data['title'] ?? 'Notification' }}</h6>
                                                <small class="d-block text-body mb-1">{{ $notification->data['message'] ?? '' }}</small>
                                                <small class="text-muted"><i class="bx bx-time-five me-1"></i>{{ $notification->created_at->diffForHumans() }}</small>
                                            </div>
                                            <div class="flex-shrink-0 dropdown-notifications-actions">
                                                <span class="badge badge-dot bg-primary"></span>
                                            </div>
                                        </div>
                                    </li>
                                @endif
                            @empty
                                <li class="list-group-item text-center text-muted py-4">
                                    <i class="bx bx-bell-off fs-4 d-block mb-1"></i>
                                    No new notifications
                                </li>
                            @endforelse
                        </ul>
                    </li>
                    <li class="border-top">
                        <div class="d-grid p-4">
                            <a class="btn btn-primary btn-sm d-flex" href="{{ route('admin.notification') }}">
                                <small class="align-middle">View all notifications</small>
                            </a>
                        </div>
                    </li>
                </ul>
            </li>

            @php
                $user = DB::table('users')
                    ->where('id', auth()->user()->id)
                    ->first();
            @endphp
            <!-- User -->
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                        <img src="{{ asset($user->image ?? 'assets/images/default.png') }}" alt
                            class="rounded-circle" />
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.profile.index') }}">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar avatar-online">
                                        <img src="{{ asset($user->image ?? 'assets/images/default.png') }}" alt
                                            class="w-px-40 h-auto rounded-circle" />
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">
                                        {{ auth()->user()->name ?? 'Unknown Name' }}
                                    </h6>
                                    <small class="text-body-secondary">{{ auth()->user()->isRole() }}</small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider my-1"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('admin.profile.index') }}">
                            <i class="icon-base bx bx-user icon-md me-3"></i><span>My Profile</span>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider my-1"></div>
                    </li>
                    @if (!Auth::check())
                        <li>
                            <a class="dropdown-item"
                                href="https://demos.themeselection.com/sneat-bootstrap-html-laravel-admin-template/demo-1/auth/login-basic">
                                <i class="icon-base bx bx-log-in icon-md me-3"></i><span>Login</span>
                            </a>
                        </li>
                    @else
                        <li>
                            <form method="POST" action="{{ session()->has('super_admin_impersonator_id') ? route('super-admin.return') : route('logout') }}" class="m-0">
                                @csrf
                <button type="submit" class="dropdown-item">
                                    <i class="icon-base bx {{ session()->has('super_admin_impersonator_id') ? 'bx-exit' : 'bx-log-out' }} icon-md me-3"></i><span>{{ session()->has('super_admin_impersonator_id') ? 'Return to Super Admin' : 'Logout' }}</span>
                                </button>
                            </form>
                        </li>
                    @endif
                </ul>
            </li>
            <!--/ User -->
        </ul>
    </div>
</nav>
<script>
    (function() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        const theme = savedTheme === 'system'
            ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
            : savedTheme;
        const themeIcon = document.getElementById('theme-toggle-icon');
        const updateThemeIcon = (activeTheme) => {
            if (!themeIcon) return;
            themeIcon.innerHTML = activeTheme === 'dark'
                ? '<path d="M20.9 13A9 9 0 0 1 11 3.1 9 9 0 1 0 20.9 13Z"></path>'
                : '<circle cx="12" cy="12" r="4"></circle><path d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42"></path>';
        };

        document.documentElement.setAttribute('data-bs-theme', theme);
        updateThemeIcon(theme);

        // Update active state in dropdown
        const themeButtons = document.querySelectorAll('[data-bs-theme-value]');

        themeButtons.forEach(button => {
            // Mark active button based on current theme
            if (button.getAttribute('data-bs-theme-value') === savedTheme) {
                button.classList.add('active');
            } else {
                button.classList.remove('active');
            }

            // Add click event
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const selectedTheme = this.getAttribute('data-bs-theme-value');
                const resolvedTheme = selectedTheme === 'system'
                    ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                    : selectedTheme;

                // Apply theme
                document.documentElement.setAttribute('data-bs-theme', resolvedTheme);
                updateThemeIcon(resolvedTheme);

                // Save to localStorage
                localStorage.setItem('theme', selectedTheme);

                // Update active states
                themeButtons.forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
    })();
</script>
