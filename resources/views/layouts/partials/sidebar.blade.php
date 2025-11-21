{{-- resources/views/layouts/partials/sidebar.blade.php --}}
<style>
    /* =======================================
       SIDEBAR FIXED MODERN (desktop only)
    ======================================= */

    @media (min-width: 992px) {
        .sidebar-modern {
            position: fixed;
            top: 0;
            left: 0;
            width: 240px;
            height: 100vh;
            padding: 1rem 1rem 2rem;

            display: flex;
            flex-direction: column;
            gap: 1rem;

            background: color-mix(in srgb, var(--card) 90%, var(--bg) 10%);
            backdrop-filter: blur(14px);
            border-right: 1px solid rgba(148, 163, 184, .35);

            box-shadow:
                8px 0 24px rgba(0, 0, 0, .04),
                2px 0 8px rgba(0, 0, 0, .03);

            border-radius: 0 22px 22px 0;
            z-index: 1030;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, .4) transparent;
        }

        /* Biar konten geser ke kanan 240px */
        .app-main {
            margin-left: 240px;
        }
    }

    .sidebar-modern {
        display: none;
    }

    @media (min-width: 992px) {
        .sidebar-modern {
            display: flex;
        }
    }

    .sidebar-modern::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar-modern::-webkit-scrollbar-thumb {
        background: rgba(148, 163, 184, .35);
        border-radius: 20px;
    }

    .sidebar-brand {
        font-size: 1.35rem;
        font-weight: 700;
        padding: .8rem .3rem 1.2rem;
        color: var(--text);
    }

    .menu-label {
        color: var(--muted);
        padding-left: .5rem;
        margin-bottom: .25rem;
        letter-spacing: .05em;
        font-size: .72rem;
    }

    .sidebar-nav {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .sidebar-link {
        display: flex;
        align-items: center;
        gap: .75rem;
        padding: .65rem .9rem;
        border-radius: 14px;
        color: var(--text);
        text-decoration: none;
        font-size: .95rem;
        transition: .22s ease;
    }

    .sidebar-link .icon {
        width: 22px;
        font-size: 1.1rem;
    }

    .sidebar-link:hover {
        background: color-mix(in srgb, var(--accent-soft) 50%, var(--card) 50%);
        box-shadow: inset 0 0 0 1px var(--line);
        transform: translateX(3px);
    }

    .sidebar-link.active {
        background: color-mix(in srgb, var(--accent-soft) 70%, var(--card) 30%);
        font-weight: 600;
        border-left: 4px solid var(--accent);
        padding-left: .7rem;
    }
</style>

<aside class="sidebar-modern flex-column">
    <div class="sidebar-brand">GFID</div>

    <ul class="sidebar-nav">

        {{-- DASHBOARD --}}
        <li>
            <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="icon">🏠</span>
                <span>Dashboard</span>
            </a>
        </li>

        {{-- PURCHASING --}}
        <li class="mt-2 text-uppercase small menu-label">Purchasing</li>
        <li>
            <a href="{{ route('purchasing.purchase_orders.index') }}"
                class="sidebar-link {{ request()->routeIs('purchasing.purchase_orders.*') ? 'active' : '' }}">
                <span class="icon">🧾</span>
                <span>Purchase Orders</span>
            </a>
        </li>

        {{-- PRODUCTION --}}
        <li class="mt-2 text-uppercase small menu-label">Production</li>
        <li>
            <a href="#" class="sidebar-link">
                <span class="icon">✂️</span>
                <span>Cutting &amp; Sewing</span>
            </a>
        </li>

        <li>
            <a href="#" class="sidebar-link">
                <span class="icon">🧵</span>
                <span>Finishing</span>
            </a>
        </li>

        {{-- WAREHOUSE --}}
        <li class="mt-2 text-uppercase small menu-label">Warehouse</li>
        <li>
            <a href="#" class="sidebar-link">
                <span class="icon">📦</span>
                <span>Inventory</span>
            </a>
        </li>

        {{-- FINANCE --}}
        <li class="mt-2 text-uppercase small menu-label">Finance</li>
        <li>
            <a href="#" class="sidebar-link">
                <span class="icon">💰</span>
                <span>Payroll</span>
            </a>
        </li>

        <li>
            <a href="#" class="sidebar-link">
                <span class="icon">📊</span>
                <span>Reports</span>
            </a>
        </li>
    </ul>
</aside>
