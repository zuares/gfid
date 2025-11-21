{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="id" data-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'GFID'))</title>

    {{-- Bootstrap 5 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <style>
        /* ===============================
   TYPOGRAPHY & HEADINGS
================================= */

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            color: var(--text);
            margin-bottom: .4rem;
        }

        p,
        label,
        span,
        small {
            color: var(--text);
        }

        .text-muted,
        .muted {
            color: var(--muted) !important;
        }

        .help {
            color: var(--muted);
            font-size: .85rem;
        }

        /* ===============================
   CARD & PANEL
================================= */

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            box-shadow:
                0 16px 40px rgba(15, 23, 42, .12),
                0 1px 0 rgba(15, 23, 42, .04);
        }

        .card-header {
            background: color-mix(in srgb, var(--card) 85%, var(--bg) 15%);
            border-bottom: 1px solid var(--line);
            color: var(--text);
            font-weight: 600;
        }

        /* Chip / tag soft */
        .tag-soft {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .1rem .6rem;
            border-radius: 999px;
            border: 1px solid var(--line);
            background: var(--accent-soft);
            color: var(--accent);
            font-size: .7rem;
            white-space: nowrap;
        }

        /* ===============================
   TABLE
================================= */

        .table {
            color: var(--text);
            font-size: .86rem;
        }

        .table> :not(caption)>*>* {
            background-color: transparent;
            border-bottom-color: var(--line);
        }

        .table thead th {
            background: color-mix(in srgb, var(--card) 85%, var(--bg) 15%);
            border-bottom: 1px solid var(--line);
            color: var(--muted);
            font-weight: 600;
        }

        .table tbody tr:hover {
            background: color-mix(in srgb, var(--card) 92%, var(--bg) 8%);
        }

        /* ===============================
   FORM ELEMENTS
================================= */

        .form-control,
        .form-select {
            background-color: var(--card);
            border: 1px solid var(--line);
            color: var(--text);
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 .12rem color-mix(in srgb, var(--accent-soft) 70%, var(--accent) 30%);
            background-color: var(--card);
            color: var(--text);
        }

        .form-control::placeholder {
            color: var(--muted);
        }

        /* ===============================
   BUTTONS
================================= */

        .btn {
            font-size: .85rem;
            border-radius: .6rem;
        }

        .btn-primary {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
            box-shadow: 0 10px 25px rgba(37, 99, 235, .4);
        }

        .btn-primary:hover {
            filter: brightness(1.05);
        }

        .btn-outline-primary {
            color: var(--accent);
            border-color: var(--accent);
            background: transparent;
        }

        .btn-outline-primary:hover {
            background: var(--accent-soft);
            color: var(--accent);
        }

        .btn-soft {
            background: var(--accent-soft);
            color: var(--accent);
            border: 1px solid color-mix(in srgb, var(--accent) 12%, var(--line) 88%);
        }

        /* success / danger mengikuti var */
        .btn-success {
            background: var(--success);
            border-color: var(--success);
            color: #fff;
        }

        .btn-outline-success {
            color: var(--success);
            border-color: var(--success);
        }

        .btn-danger {
            background: var(--danger);
            border-color: var(--danger);
            color: #fff;
        }

        .btn-outline-danger {
            color: var(--danger);
            border-color: var(--danger);
        }

        /* ===============================
   ALERTS
================================= */

        .alert {
            border-radius: .9rem;
            border-width: 1px;
            font-size: .85rem;
        }

        .alert-success {
            background: var(--success-soft);
            border-color: color-mix(in srgb, var(--success) 40%, var(--success-soft) 60%);
            color: var(--success);
        }

        .alert-danger {
            background: var(--danger-soft);
            border-color: color-mix(in srgb, var(--danger) 40%, var(--danger-soft) 60%);
            color: var(--danger);
        }

        .alert-warning {
            background: color-mix(in srgb, #facc15 15%, var(--bg) 85%);
            border-color: #facc15;
            color: #854d0e;
        }

        /* ===============================
   BADGE / STATUS
================================= */

        .badge-soft-success {
            background: var(--success-soft);
            color: var(--success);
            border-radius: 999px;
            padding: .1rem .5rem;
            font-size: .7rem;
        }

        .badge-soft-danger {
            background: var(--danger-soft);
            color: var(--danger);
            border-radius: 999px;
            padding: .1rem .5rem;
            font-size: .7rem;
        }

        .badge-soft-info {
            background: var(--accent-soft);
            color: var(--accent);
            border-radius: 999px;
            padding: .1rem .5rem;
            font-size: .7rem;
        }

        /* ===============================
   PAGINATION (Bootstrap)
================================= */

        .page-link {
            color: var(--muted);
            background-color: var(--card);
            border-color: var(--line);
        }

        .page-link:hover {
            color: var(--accent);
            background-color: color-mix(in srgb, var(--card) 90%, var(--accent-soft) 10%);
            border-color: var(--accent);
        }

        .page-item.active .page-link {
            color: var(--accent);
            background-color: var(--accent-soft);
            border-color: var(--accent);
            font-weight: 600;
        }

        .page-item.disabled .page-link {
            color: var(--muted);
            background-color: var(--card);
            border-color: var(--line);
        }

        /* ===============================
   BOTTOM NAV (ICONS) – warna ikut theme
================================= */

        .bottom-nav {
            background: color-mix(in srgb, var(--card) 92%, var(--bg) 8%);
            border-top: 1px solid var(--line);
        }

        .bottom-nav-item {
            color: var(--muted);
        }

        .bottom-nav-item.active {
            color: var(--accent);
            background: var(--accent-soft);
        }

        :root {
            color-scheme: light dark;

            /* LIGHT */
            --bg-light: #f4f5fb;
            --card-light: #ffffff;
            --surface-light: #e5e9f2;
            --text-light: #111827;
            --muted-light: #6b7280;
            --line-light: #d4d7e3;

            --accent-light: #2563eb;
            --accent-soft-light: #dbeafe;

            --danger-light: #dc2626;
            --danger-soft-light: #fee2e2;

            --success-light: #16a34a;
            --success-soft-light: #dcfce7;

            /* DARK */
            --bg-dark: #020617;
            --card-dark: #020b1b;
            --surface-dark: #020a16;
            --text-dark: #e5e7eb;
            --muted-dark: #9ca3af;
            --line-dark: #1f2937;

            --accent-dark: #60a5fa;
            --accent-soft-dark: #1d283a;

            --danger-dark: #f87171;
            --danger-soft-dark: #451a1a;

            --success-dark: #4ade80;
            --success-soft-dark: #064e3b;
        }

        [data-theme="light"] {
            --bg: var(--bg-light);
            --card: var(--card-light);
            --surface: var(--surface-light);
            --text: var(--text-light);
            --muted: var(--muted-light);
            --line: var(--line-light);
            --accent: var(--accent-light);
            --accent-soft: var(--accent-soft-light);
            --danger: var(--danger-light);
            --danger-soft: var(--danger-soft-light);
            --success: var(--success-light);
            --success-soft: var(--success-soft-light);
        }

        [data-theme="dark"] {
            --bg: var(--bg-dark);
            --card: var(--card-dark);
            --surface: var(--surface-dark);
            --text: var(--text-dark);
            --muted: var(--muted-dark);
            --line: var(--line-dark);
            --accent: var(--accent-dark);
            --accent-soft: var(--accent-soft-dark);
            --danger: var(--danger-dark);
            --danger-soft: var(--danger-soft-dark);
            --success: var(--success-dark);
            --success-soft: var(--success-soft-dark);
        }

        html,
        body {
            height: 100%;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "SF Pro Text", "Segoe UI", sans-serif;
        }

        a {
            color: var(--accent);
        }

        a:hover {
            color: var(--accent);
            opacity: .9;
        }

        /* NAVBAR ATAS */
        .navbar {
            background: color-mix(in srgb, var(--card) 85%, var(--bg) 15%);
            border-bottom: 1px solid var(--line);
            backdrop-filter: blur(10px);
        }

        .navbar .navbar-brand,
        .navbar .nav-link,
        .navbar .navbar-text {
            color: var(--text) !important;
        }

        .navbar-toggler {
            border-color: var(--line);
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(148, 163, 184, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        /* APP SHELL */
        .app-shell {
            display: flex;
            min-height: calc(100vh - 56px - 40px);
            /* navbar + footer approx */
        }

        .app-sidebar {
            width: 230px;
            background: color-mix(in srgb, var(--card) 90%, var(--bg) 10%);
            border-right: 1px solid var(--line);
            padding: .75rem;
        }

        .app-sidebar-brand {
            font-weight: 600;
            font-size: .85rem;
            margin-bottom: .5rem;
            color: var(--muted);
        }

        .app-sidebar-nav {
            list-style: none;
            padding-left: 0;
            margin: 0;
            display: flex;
            flex-direction: column;
            gap: .25rem;
        }

        .app-sidebar-link {
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: .45rem .65rem;
            font-size: .85rem;
            border-radius: .75rem;
            color: var(--muted);
            text-decoration: none;
        }

        .app-sidebar-link-icon {
            width: 18px;
            text-align: center;
            font-size: .95rem;
        }

        .app-sidebar-link.active {
            background: var(--accent-soft);
            color: var(--accent);
            font-weight: 500;
        }

        .app-main {
            flex: 1;
            padding: 1rem 1rem 3.75rem;
            /* extra bottom space buat bottom nav mobile */
        }

        .page-wrap {
            max-width: 1080px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .card-header {
            background: color-mix(in srgb, var(--card) 85%, var(--bg) 15%);
            border-bottom: 1px solid var(--line);
            font-weight: 600;
        }

        .table {
            color: var(--text);
            font-size: .86rem;
        }

        .table> :not(caption)>*>* {
            background-color: transparent;
            border-bottom-color: var(--line);
        }

        .btn-primary {
            background: var(--accent);
            border-color: var(--accent);
        }

        .btn-outline-primary {
            color: var(--accent);
            border-color: var(--accent);
        }

        .btn-soft {
            background: var(--accent-soft);
            color: var(--accent);
            border: 1px solid color-mix(in srgb, var(--accent) 12%, var(--line) 88%);
        }

        .text-muted,
        .muted {
            color: var(--muted) !important;
        }

        .help {
            color: var(--muted);
            font-size: .85rem;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        /* THEME TOGGLE */
        .theme-toggle-btn {
            border-radius: 999px;
            border: 1px solid var(--line);
            padding-inline: .75rem;
            padding-block: .3rem;
            font-size: .8rem;
            background: color-mix(in srgb, var(--card) 90%, var(--accent) 10%);
            display: inline-flex;
            align-items: center;
            gap: .35rem;
        }

        .theme-toggle-btn .icon {
            font-size: 1rem;
        }

        /* FOOTER */
        .app-footer {
            font-size: .8rem;
            color: var(--muted);
            padding: .75rem 1rem 1rem;
        }

        /* BOTTOM NAV (MOBILE) */
        .bottom-nav {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1030;
            background: color-mix(in srgb, var(--card) 92%, var(--bg) 8%);
            border-top: 1px solid var(--line);
            padding: .25rem .75rem .35rem;
            display: flex;
            justify-content: space-between;
            gap: .25rem;
        }

        .bottom-nav-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: .1rem;
            text-decoration: none;
            color: var(--muted);
            font-size: .7rem;
            border-radius: 999px;
            padding: .15rem 0;
        }

        .bottom-nav-item-icon {
            font-size: 1.1rem;
        }

        .bottom-nav-item.active {
            color: var(--accent);
            background: var(--accent-soft);
            font-weight: 500;
        }

        @media (min-width: 768px) {
            .app-sidebar {
                display: flex;
            }

            .bottom-nav {
                display: none;
            }
        }

        @media (max-width: 767.98px) {
            .app-sidebar {
                display: none !important;
            }
        }
    </style>

    @stack('head')
</head>

<body>
    <div id="app" class="d-flex flex-column min-vh-100">

        {{-- NAVBAR --}}
        <nav class="navbar navbar-expand-md shadow-sm">
            <div class="container-fluid" style="max-width: 1080px;">
                <a class="navbar-brand fw-semibold" href="{{ url('/') }}">
                    {{ config('app.name', 'GFID') }}
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain"
                    aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarMain">
                    {{-- kiri (kosong / quick links kalau mau) --}}
                    <ul class="navbar-nav me-auto"></ul>

                    {{-- kanan: theme + user --}}
                    <ul class="navbar-nav ms-auto align-items-center gap-2">
                        <li class="nav-item me-2">
                            <button type="button" class="btn btn-sm theme-toggle-btn" id="themeToggleBtn">
                                <span class="icon" id="themeToggleIcon">🌙</span>
                                <span class="muted" id="themeToggleLabel">Mode Gelap</span>
                            </button>
                        </li>

                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="btn btn-sm btn-outline-primary" href="{{ route('login') }}">Login</a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    {{ Auth::user()->name ?? Auth::user()->email }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end">
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="dropdown-item">Logout</button>
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        {{-- APP SHELL: sidebar + main --}}
        <div class="app-shell">
            {{-- SIDEBAR DESKTOP --}}
            <aside class="app-sidebar d-none d-md-flex flex-column">
                <div class="app-sidebar-brand">Menu</div>
                <ul class="app-sidebar-nav">
                    <li>
                        <a href=""
                            class="app-sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <span class="app-sidebar-link-icon">🏠</span>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="" class="app-sidebar-link {{ request()->is('production*') ? 'active' : '' }}">
                            <span class="app-sidebar-link-icon">✂️</span>
                            <span>Production</span>
                        </a>
                    </li>
                    <li>
                        <a href="" class="app-sidebar-link {{ request()->is('warehouse*') ? 'active' : '' }}">
                            <span class="app-sidebar-link-icon">📦</span>
                            <span>Warehouse</span>
                        </a>
                    </li>
                    <li>
                        <a href="" class="app-sidebar-link {{ request()->is('payroll*') ? 'active' : '' }}">
                            <span class="app-sidebar-link-icon">💰</span>
                            <span>Payroll</span>
                        </a>
                    </li>
                    <li>
                        <a href="" class="app-sidebar-link {{ request()->is('reports*') ? 'active' : '' }}">
                            <span class="app-sidebar-link-icon">📊</span>
                            <span>Reports</span>
                        </a>
                    </li>
                </ul>
            </aside>

            {{-- MAIN CONTENT --}}
            <main class="app-main">
                <div class="page-wrap">
                    @if (session('status'))
                        <div class="alert alert-success mb-3">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger mb-3">
                            <strong>Terjadi error:</strong>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>

        {{-- FOOTER --}}
        <footer class="app-footer text-center">
            <div class="page-wrap">
                © {{ date('Y') }} {{ config('app.name', 'GFID') }}
                · <span class="mono">Laravel {{ app()->version() }}</span>
            </div>
        </footer>

        {{-- BOTTOM NAV MOBILE --}}
        @auth
            <x-mobile-bottom-nav />
        @endauth
    </div>

    {{-- JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>

    <script>
        (function() {
            const root = document.documentElement;
            const btn = document.getElementById('themeToggleBtn');
            const icon = document.getElementById('themeToggleIcon');
            const label = document.getElementById('themeToggleLabel');
            const storageKey = 'gfid_theme';

            function applyTheme(theme) {
                if (!['light', 'dark'].includes(theme)) theme = 'light';
                root.setAttribute('data-theme', theme);

                if (theme === 'dark') {
                    icon.textContent = '☀️';
                    label.textContent = 'Mode Terang';
                } else {
                    icon.textContent = '🌙';
                    label.textContent = 'Mode Gelap';
                }

                try {
                    localStorage.setItem(storageKey, theme);
                } catch (e) {}
            }

            function initTheme() {
                let saved = null;
                try {
                    saved = localStorage.getItem(storageKey);
                } catch (e) {}

                if (saved === 'light' || saved === 'dark') {
                    applyTheme(saved);
                    return;
                }

                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    applyTheme('dark');
                } else {
                    applyTheme('light');
                }
            }

            initTheme();

            if (btn) {
                btn.addEventListener('click', function() {
                    const current = root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
                    const next = current === 'dark' ? 'light' : 'dark';
                    applyTheme(next);
                });
            }
        })();
    </script>

    @stack('scripts')
</body>

</html>
