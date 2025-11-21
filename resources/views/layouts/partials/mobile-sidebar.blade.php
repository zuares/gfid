{{-- resources/views/layouts/partials/mobile-sidebar.blade.php --}}
<style>
    /* ============================
       MOBILE SIDEBAR (DRAWER)
    ============================ */

    .mobile-sidebar-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
        z-index: 1040;
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
        transition: opacity .18s ease, visibility .18s ease;
    }

    .mobile-sidebar-overlay.is-open {
        opacity: 1;
        visibility: visible;
        pointer-events: auto;
    }

    .mobile-sidebar-panel {
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        width: 260px;
        max-width: 82%;

        background: var(--card);
        border-right: 1px solid var(--line);
        box-shadow:
            10px 0 30px rgba(15, 23, 42, 0.35),
            0 0 0 1px rgba(15, 23, 42, .15);

        z-index: 1051;

        transform: translateX(-100%);
        transition: transform .22s ease-out;

        display: flex;
        flex-direction: column;
        padding: .75rem .85rem 1.1rem;
    }

    .mobile-sidebar-panel.is-open {
        transform: translateX(0);
    }

    .mobile-sidebar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        margin-bottom: .75rem;
    }

    .mobile-sidebar-title {
        font-weight: 600;
        font-size: 1rem;
    }

    .mobile-sidebar-close-btn {
        border-radius: 999px;
        border: 1px solid var(--line);
        width: 30px;
        height: 30px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: color-mix(in srgb, var(--card) 90%, var(--bg) 10%);
        cursor: pointer;
        font-size: 1.1rem;
    }

    .mobile-sidebar-nav {
        list-style: none;
        padding: 0;
        margin: .5rem 0 0;
    }

    .mobile-sidebar-link {
        display: flex;
        align-items: center;
        gap: .55rem;
        padding: .55rem .4rem;
        border-radius: 12px;
        text-decoration: none;
        color: var(--text);
        font-size: .9rem;
        margin-bottom: .1rem;
    }

    .mobile-sidebar-link span.icon {
        font-size: 1.1rem;
        width: 22px;
        text-align: center;
    }

    .mobile-sidebar-link:hover {
        background: color-mix(in srgb, var(--accent-soft) 70%, var(--card) 30%);
    }

    .mobile-sidebar-link.active {
        background: color-mix(in srgb, var(--accent-soft) 80%, var(--card) 20%);
        color: var(--accent);
        font-weight: 600;
    }

    .mobile-sidebar-section-label {
        font-size: .74rem;
        text-transform: uppercase;
        letter-spacing: .08em;
        color: var(--muted);
        margin-top: .8rem;
        margin-bottom: .15rem;
        padding-inline: .2rem;
    }

    .mobile-sidebar-footer {
        margin-top: auto;
        font-size: .75rem;
        color: var(--muted);
        padding-top: .6rem;
        border-top: 1px solid var(--line);
    }

    @media (min-width: 768px) {

        .mobile-sidebar-overlay,
        .mobile-sidebar-panel {
            display: none;
        }
    }
</style>

{{-- OVERLAY --}}
<div id="mobileSidebarOverlay" class="mobile-sidebar-overlay"></div>

{{-- PANEL --}}
<aside id="mobileSidebarPanel" class="mobile-sidebar-panel">

    <div class="mobile-sidebar-header">
        <div class="mobile-sidebar-title">
            {{ config('app.name', 'GFID') }}
        </div>
        <button type="button" class="mobile-sidebar-close-btn" id="mobileSidebarCloseBtn">
            ✕
        </button>
    </div>

    <ul class="mobile-sidebar-nav">
        @auth
            <li>
                <a href="{{ route('dashboard') }}"
                    class="mobile-sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <span class="icon">🏠</span>
                    <span>Dashboard</span>
                </a>
            </li>

            <div class="mobile-sidebar-section-label">Purchasing</div>
            <li>
                <a href="{{ route('purchasing.purchase_orders.index') }}"
                    class="mobile-sidebar-link {{ request()->routeIs('purchasing.purchase_orders.*') ? 'active' : '' }}">
                    <span class="icon">🧾</span>
                    <span>Purchase Orders</span>
                </a>
            </li>

            <div class="mobile-sidebar-section-label">Production</div>
            <li>
                <a href="#" class="mobile-sidebar-link">
                    <span class="icon">✂️</span>
                    <span>Cutting &amp; Sewing</span>
                </a>
            </li>
            <li>
                <a href="#" class="mobile-sidebar-link">
                    <span class="icon">🧵</span>
                    <span>Finishing</span>
                </a>
            </li>

            <div class="mobile-sidebar-section-label">Warehouse</div>
            <li>
                <a href="#" class="mobile-sidebar-link">
                    <span class="icon">📦</span>
                    <span>Inventory</span>
                </a>
            </li>

            <div class="mobile-sidebar-section-label">Finance</div>
            <li>
                <a href="#" class="mobile-sidebar-link">
                    <span class="icon">💰</span>
                    <span>Payroll</span>
                </a>
            </li>
        @endauth
    </ul>

    <div class="mobile-sidebar-footer">
        <div class="d-flex justify-content-between">
            <span>{{ now()->format('d/m/Y') }}</span>
            <span class="mono">{{ Auth::user()->name ?? '' }}</span>
        </div>
    </div>
</aside>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('mobileSidebarToggle');
            const closeBtn = document.getElementById('mobileSidebarCloseBtn');
            const sidebar = document.getElementById('mobileSidebarPanel');
            const overlay = document.getElementById('mobileSidebarOverlay');

            if (!toggleBtn || !sidebar || !overlay) return;

            function openSidebar() {
                sidebar.classList.add('is-open');
                overlay.classList.add('is-open');
                document.body.style.overflow = 'hidden';
            }

            function closeSidebar() {
                sidebar.classList.remove('is-open');
                overlay.classList.remove('is-open');
                document.body.style.overflow = '';
            }

            toggleBtn.addEventListener('click', function() {
                if (sidebar.classList.contains('is-open')) {
                    closeSidebar();
                } else {
                    openSidebar();
                }
            });

            closeBtn?.addEventListener('click', closeSidebar);
            overlay.addEventListener('click', closeSidebar);

            document.addEventListener('keyup', function(e) {
                if (e.key === 'Escape') {
                    closeSidebar();
                }
            });
        });
    </script>
@endpush
