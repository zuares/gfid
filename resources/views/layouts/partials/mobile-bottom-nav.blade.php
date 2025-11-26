{{-- resources/views/components/mobile-bottom-nav.blade.php --}}
@php
    // Flag aktif per tab, sesuaikan dengan grouping route di app-mu
    $isDashboard = request()->routeIs('dashboard');

    $isProduction = request()->routeIs('production.*'); // semua route produksi (cutting, sewing, qc, report, dll)

    $isInventory = request()->routeIs('inventory.*'); // stok card, transfer, external transfer, dll

    $isProfile = request()->routeIs('profile.*') || request()->routeIs('settings.*');
@endphp

<style>
    .mobile-bottom-nav {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;

        height: 62px;
        padding: 0 .75rem;

        display: flex;
        justify-content: space-between;
        align-items: center;

        background: var(--card);
        border-top: 1px solid var(--line);

        z-index: 1000;

        border-top-left-radius: 22px;
        border-top-right-radius: 22px;

        box-shadow:
            0 -4px 22px rgba(0, 0, 0, .10),
            0 -2px 10px rgba(0, 0, 0, .06);
    }

    .mobile-bottom-nav .nav-item {
        flex: 1;
        text-align: center;
        padding-top: .3rem;

        color: var(--muted);
        font-size: .72rem;
        font-weight: 500;
        text-decoration: none;

        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .2rem;

        transition: .2s ease;
    }

    .mobile-bottom-nav .nav-item.active {
        color: var(--accent);
    }

    .mobile-bottom-nav .icon svg {
        width: 22px;
        height: 22px;
        stroke-width: 2.2;
    }

    .mobile-bottom-nav .center-btn {
        position: relative;
        top: -18px;
        flex: none;
    }

    .mobile-bottom-nav .center-icon {
        width: 62px;
        height: 62px;

        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;

        background: linear-gradient(135deg,
                var(--accent) 0%,
                #7dd3fc 50%,
                #38bdf8 100%);

        color: white;
        box-shadow:
            0 6px 18px rgba(0, 0, 0, .25),
            0 2px 8px rgba(0, 0, 0, .15);

        transition: .18s ease;
    }

    .center-icon svg {
        width: 28px;
        height: 28px;
        stroke-width: 2.4;
    }

    .center-icon:active {
        transform: scale(.9);
    }

    @media (min-width: 768px) {
        .mobile-bottom-nav {
            display: none;
        }
    }
</style>

<div class="mobile-bottom-nav">
    {{-- HOME --}}
    <a href="{{ route('dashboard') }}" class="nav-item {{ $isDashboard ? 'active' : '' }}">
        <span class="icon">
            {{-- icon home --}}
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 10.5 11.24 4.6a1.1 1.1 0 0 1 1.52 0L20 10.5" />
                <path d="M6.5 9.5V18a1.5 1.5 0 0 0 1.5 1.5h8a1.5 1.5 0 0 0 1.5-1.5V9.5" />
                <path d="M10 19.5V13.5a2 2 0 0 1 2-2 2 2 0 0 1 2 2v6" />
            </svg>
        </span>
        <span class="label">Home</span>
    </a>

    {{-- PROD --}}
    <a href="{{ Route::has('production.cutting_jobs.index') ? route('production.cutting_jobs.index') : '#' }}"
        class="nav-item {{ $isProduction ? 'active' : '' }}">
        <span class="icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
                stroke-linejoin="round">
                <circle cx="7" cy="8" r="2.5" />
                <circle cx="7" cy="16" r="2.5" />
                <path d="M9 14 17.5 5.5" />
                <path d="M9 10 17.5 18.5" />
                <path d="M18 5.5 20 3.5" />
                <path d="M18 18.5 20 20.5" />
            </svg>
        </span>
        <span class="label">Prod</span>
    </a>

    {{-- CENTER FAB: quick action (contoh: Sewing Pickup baru) --}}
    <a href="{{ Route::has('production.sewing_pickups.create') ? route('production.sewing_pickups.create') : '#' }}"
        class="nav-item center-btn">
        <div class="center-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
                stroke-linejoin="round">
                <path d="M12 6v12" />
                <path d="M6 12h12" />
            </svg>
        </div>
    </a>

    {{-- STOCK --}}
    <a href="{{ Route::has('inventory.stock_card.index') ? route('inventory.stock_card.index') : '#' }}"
        class="nav-item {{ $isInventory ? 'active' : '' }}">
        <span class="icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
                stroke-linejoin="round">
                <path d="M4.5 9 12 4.5 19.5 9" />
                <path d="M5 9.5v6L12 20l7-4.5v-6" />
                <path d="M9 11.5 15 15" />
                <path d="M15 11.5 9 15" />
            </svg>
        </span>
        <span class="label">Stok</span>
    </a>

    {{-- PROFILE --}}
    <a href="{{ Route::has('profile.edit') ? route('profile.edit') : '#' }}"
        class="nav-item {{ $isProfile ? 'active' : '' }}">
        <span class="icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round"
                stroke-linejoin="round">
                <path d="M12 12.5a3.5 3.5 0 1 0-0.01-7 3.5 3.5 0 0 0 0.01 7Z" />
                <path d="M5.5 19.5a6.5 6.5 0 0 1 13 0" />
            </svg>
        </span>
        <span class="label">Profil</span>
    </a>
</div>
