{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="id" data-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'GFID'))</title>

    {{-- Bootstrap 5 CDN (boleh diganti @vite) --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    {{-- THEME: soft light & dark, pakai CSS variables --}}
    <style>
        :root {
            color-scheme: light dark;

            /* ========= PALETTE DASAR ========= */
            /* LIGHT: lembut tapi tetap kontras */
            --bg-light: #f4f5fb;
            /* abu kebiruan lembut */
            --card-light: #ffffff;
            --surface-light: #e5e9f2;
            /* untuk bagian tertentu kalau perlu */
            --text-light: #111827;
            /* slate-900-ish */
            --muted-light: #6b7280;
            /* slate-500 */
            --line-light: #d4d7e3;
            /* border halus */

            --accent-light: #2563eb;
            /* biru utama */
            --accent-soft-light: #dbeafe;
            /* background soft untuk tag/badge */

            --danger-light: #dc2626;
            --danger-soft-light: #fee2e2;

            --success-light: #16a34a;
            --success-soft-light: #dcfce7;

            /* DARK: gelap, tapi card & text jelas (bukan hitam pekat semua) */
            --bg-dark: #020617;
            /* slate-950 */
            --card-dark: #020b1b;
            /* sedikit lebih terang dari bg */
            --surface-dark: #020a16;
            /* untuk header/footer/card tertentu */
            --text-dark: #e5e7eb;
            /* slate-200 */
            --muted-dark: #9ca3af;
            /* slate-400 */
            --line-dark: #1f2937;
            /* slate-800 */

            --accent-dark: #60a5fa;
            /* biru agak terang */
            --accent-soft-dark: #1d283a;

            --danger-dark: #f87171;
            --danger-soft-dark: #451a1a;

            --success-dark: #4ade80;
            --success-soft-dark: #064e3b;
        }

        /* ========= THEME RESOLVER ========= */
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

        /* ========= GLOBAL ========= */
        html,
        body {
            height: 100%;
        }

        body {
            margin: 0;
            background: radial-gradient(circle at top, color-mix(in srgb, var(--bg) 90%, #ffffff 10%), var(--bg));
            color: var(--text);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "SF Pro Text", "Segoe UI", sans-serif;
        }

        a,
        .link-primary {
            color: var(--accent);
        }

        a:hover {
            color: var(--accent);
            opacity: .9;
        }

        /* ========= NAVBAR ========= */
        .navbar {
            background: color-mix(in srgb, var(--card) 85%, var(--bg) 15%);
            border-bottom: 1px solid var(--line);
            backdrop-filter: blur(12px);
        }

        .navbar .navbar-brand,
        .navbar .nav-link,
        .navbar .navbar-text {
            color: var(--text) !important;
        }

        .navbar .nav-link {
            font-size: .9rem;
            padding-inline: .75rem;
        }

        .navbar .nav-link.active {
            font-weight: 600;
            border-bottom: 2px solid var(--accent);
        }

        /* Toggler icon biar jelas di light & dark */
        .navbar-toggler {
            border-color: var(--line);
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(148, 163, 184, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        [data-theme="dark"] .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(148, 163, 184, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        /* ========= CARD & TABLE ========= */
        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            box-shadow:
                0 18px 45px rgba(15, 23, 42, 0.12),
                0 1px 0 rgba(15, 23, 42, 0.04);
        }

        .card-header {
            background: color-mix(in srgb, var(--card) 85%, var(--bg) 15%);
            border-bottom: 1px solid var(--line);
        }

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
        }

        /* ========= BUTTONS ========= */
        .btn-primary {
            background: var(--accent);
            border-color: var(--accent);
            box-shadow: 0 8px 25px rgba(37, 99, 235, .35);
        }

        .btn-primary:hover {
            filter: brightness(1.05);
        }

        .btn-outline-primary {
            color: var(--accent);
            border-color: var(--accent);
        }

        .btn-outline-primary:hover {
            background: color-mix(in srgb, var(--accent-soft) 70%, var(--accent) 30%);
            color: var(--accent);
        }

        .btn-soft {
            background: var(--accent-soft);
            color: var(--accent);
            border: 1px solid color-mix(in srgb, var(--accent) 10%, var(--line) 90%);
        }

        /* ========= TEXT UTILITY ========= */
        .text-muted,
        .muted {
            color: var(--muted) !important;
        }

        .page-wrap {
            max-width: 1080px;
            margin-inline: auto;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .help {
            color: var(--muted);
            font-size: .85rem;
        }

        /* ========= TAG / CHIP ========= */
        .tag-soft {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            border-radius: 999px;
            padding: .1rem .55rem;
            font-size: .7rem;
            border: 1px solid var(--line);
            background: var(--accent-soft);
            color: var(--accent);
        }

        /* ========= THEME TOGGLE BUTTON ========= */
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

        .theme-toggle-btn span {
            vertical-align: middle;
        }

        .theme-toggle-btn .icon {
            font-size: 1rem;
        }

        /* ========= FOOTER ========= */
        .app-footer {
            font-size: .8rem;
            color: var(--muted);
            padding: .75rem 1rem 1rem;
        }

        @media (max-width: 767.98px) {
            .navbar-brand {
                font-size: .95rem;
            }

            .page-wrap {
                padding-inline: .75rem;
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

                <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false"
                    aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarMain">
                    {{-- LEFT MENU (contoh, bisa kamu ganti/expand) --}}
                    <ul class="navbar-nav me-auto">
                        @auth
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                                    href="{{ route('dashboard') }}">
                                    Dashboard
                                </a>
                            </li>
                            {{-- Tambah menu lainnya: production, warehouse, payroll, dll --}}
                        @endauth
                    </ul>

                    {{-- RIGHT: user info + theme switcher --}}
                    <ul class="navbar-nav ms-auto align-items-center gap-2">

                        {{-- Theme toggle --}}
                        <li class="nav-item me-2">
                            <button type="button" class="btn btn-sm theme-toggle-btn" id="themeToggleBtn">
                                <span class="icon" id="themeToggleIcon">🌙</span>
                                <span class="muted" id="themeToggleLabel">Mode Gelap</span>
                            </button>
                        </li>

                        {{-- Auth --}}
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

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    {{-- Tambah link profil dsb di sini --}}

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            Logout
                                        </button>
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        {{-- MAIN CONTENT --}}
        <main class="flex-grow-1 py-3">
            <div class="page-wrap">

                {{-- Flash message simple --}}
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

        {{-- FOOTER --}}
        <footer class="app-footer text-center">
            <div class="page-wrap">
                <span>
                    © {{ date('Y') }} {{ config('app.name', 'GFID') }}
                    · <span class="mono">Laravel {{ app()->version() }}</span>
                </span>
            </div>
        </footer>
    </div>

    {{-- Bootstrap JS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>

    {{-- THEME TOGGLER SCRIPT --}}
    <script>
        (function() {
            const root = document.documentElement;
            const btn = document.getElementById('themeToggleBtn');
            const icon = document.getElementById('themeToggleIcon');
            const label = document.getElementById('themeToggleLabel');
            const storageKey = 'gfid_theme';

            function applyTheme(theme) {
                if (!['light', 'dark'].includes(theme)) {
                    theme = 'light';
                }

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
                } catch (e) {
                    // abaikan kalau localStorage tidak tersedia
                }
            }

            function initTheme() {
                let savedTheme = null;
                try {
                    savedTheme = localStorage.getItem(storageKey);
                } catch (e) {}

                if (savedTheme === 'light' || savedTheme === 'dark') {
                    applyTheme(savedTheme);
                    return;
                }

                // Default: ikuti prefers-color-scheme OS
                if (window.matchMedia &&
                    window.matchMedia('(prefers-color-scheme: dark)').matches) {
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
