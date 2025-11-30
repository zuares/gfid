{{-- resources/views/production/finishing_jobs/bundles_ready.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Bundles WIP-FIN')

@push('head')
    <style>
        :root {
            --card-radius-lg: 14px;
        }

        .page-wrap {
            max-width: 980px;
            margin-inline: auto;
            padding: .6rem .75rem 6rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.10) 0,
                    rgba(224, 231, 255, 0.45) 18%,
                    #f9fafb 52%,
                    #f9fafb 100%);
        }

        .card {
            background: var(--card);
            border-radius: var(--card-radius-lg);
            border: 1px solid rgba(148, 163, 184, 0.16);
            box-shadow:
                0 6px 18px rgba(15, 23, 42, 0.06),
                0 0 0 1px rgba(15, 23, 42, 0.02);
        }

        .card-section {
            padding: .8rem .9rem;
        }

        @media (min-width: 768px) {
            .card-section {
                padding: .9rem 1.1rem;
            }

            .page-wrap {
                padding-top: 1rem;
                padding-bottom: 3.5rem;
            }
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .help {
            color: var(--muted);
            font-size: .8rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .table-bundles-ready {
            width: 100%;
        }

        /* ===== HEADER PAGE (mirip Sewing Return) ===== */
        .header-row {
            display: flex;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .header-title h1 {
            font-size: 1rem;
            font-weight: 700;
        }

        .header-subtitle {
            font-size: .78rem;
            color: var(--muted);
        }

        .header-icon-circle {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: .6rem;
            background: radial-gradient(circle,
                    rgba(79, 70, 229, 0.15) 0,
                    rgba(79, 70, 229, 0.05) 60%,
                    transparent 100%);
            color: #4f46e5;
        }

        .btn-header-pill {
            border-radius: 999px;
            padding: .35rem .8rem;
            font-size: .78rem;
            font-weight: 500;
            border-width: 1px;
        }

        .btn-header-muted {
            background: rgba(248, 250, 252, 0.96);
            border-color: rgba(148, 163, 184, 0.5);
            color: #0f172a;
        }

        .btn-header-accent {
            background: rgba(224, 231, 255, 0.9);
            border-color: rgba(79, 70, 229, 0.7);
            color: #312e81;
        }

        /* ===== SUMMARY ===== */
        .summary-row {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            align-items: center;
        }

        .summary-chip {
            border-radius: 999px;
            padding: .12rem .6rem;
            font-size: .72rem;
            display: inline-flex;
            align-items: center;
            gap: .25rem;
        }

        .summary-chip-qty {
            background: rgba(129, 140, 248, 0.12);
            color: #4f46e5;
            font-weight: 600;
        }

        .summary-chip-count {
            background: rgba(45, 212, 191, 0.10);
            color: #0f766e;
            font-weight: 600;
        }

        .summary-chip-selected {
            background: rgba(59, 130, 246, 0.12);
            color: #2563eb;
            font-weight: 600;
        }

        /* ===== FILTER BAR ===== */
        .filter-header {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: .7rem;
            align-items: flex-start;
        }

        .filter-header-left-title {
            display: flex;
            align-items: center;
            gap: .3rem;
        }

        .filter-header-left-title h2 {
            margin: 0;
            font-size: .9rem;
            font-weight: 700;
        }

        .filter-header-right {
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .filter-controls {
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        .filter-controls input {
            min-width: 220px;
        }

        .input-group-sm .form-control {
            font-size: .8rem;
        }

        .input-group-sm .input-group-text {
            font-size: .8rem;
        }

        /* ===== TABLE / ROW ===== */
        .bundle-row {
            transition:
                background-color .16s ease,
                box-shadow .18s ease,
                border-color .18s ease,
                transform .08s ease;
            position: relative;
            overflow: hidden;
        }

        .bundle-row td {
            border-top-color: rgba(148, 163, 184, 0.22) !important;
        }

        .bundle-row-base {
            box-shadow: inset 3px 0 0 rgba(148, 163, 184, .3);
            background: rgba(255, 255, 255, 0.98);
        }

        /* SELECT MODE: neon-ish border */
        .bundle-row-selected {
            background: radial-gradient(circle at top left,
                    rgba(129, 140, 248, 0.16) 0,
                    rgba(224, 231, 255, 0.98) 55%);
            box-shadow:
                inset 3px 0 0 rgba(79, 70, 229, 0.95),
                0 0 0 1px rgba(129, 140, 248, 0.9),
                0 0 0 5px rgba(129, 140, 248, 0.22),
                0 10px 26px rgba(15, 23, 42, 0.24);
            transform: translateY(-1px);
        }

        .qty-pill {
            border-radius: 999px;
            padding: .12rem .7rem;
            font-size: .9rem;
            font-weight: 700;
            background: linear-gradient(135deg,
                    rgba(45, 212, 191, 0.20),
                    rgba(129, 140, 248, 0.20));
            color: #0f172a;
            box-shadow:
                0 0 0 1px rgba(148, 163, 184, 0.25),
                0 4px 10px rgba(15, 23, 42, 0.12);
        }

        .qty-label {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
        }

        .select-badge {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 22px;
            height: 22px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #4f46e5;
            color: #f9fafb;
            font-size: .8rem;
            box-shadow:
                0 0 0 1px rgba(15, 23, 42, .35),
                0 8px 18px rgba(15, 23, 42, .35);
            opacity: 0;
            transform: scale(.85);
            pointer-events: none;
            transition:
                opacity .12s ease,
                transform .12s ease;
        }

        .bundle-row-selected .select-badge {
            opacity: 1;
            transform: scale(1);
        }

        /* ====== MINI BADGES (Bundle / Lot) ====== */
        .badge-mini {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: .08rem .5rem;
            font-size: .68rem;
            border: 1px solid rgba(148, 163, 184, 0.6);
            background: rgba(248, 250, 252, 0.96);
            color: #4b5563;
            gap: .25rem;
            white-space: nowrap;
        }

        .badge-mini span {
            opacity: .7;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: .58rem;
        }

        .badge-bundle {
            border-color: rgba(59, 130, 246, 0.65);
            background: rgba(219, 234, 254, 0.9);
            color: #1d4ed8;
        }

        .badge-lot {
            border-color: rgba(129, 140, 248, 0.7);
            background: rgba(224, 231, 255, 0.95);
            color: #4338ca;
        }

        .mobile-muted-soft {
            color: var(--muted);
            font-size: .74rem;
        }

        /* ====== RIPPLE EFFECT ====== */
        .bundle-row::after {
            content: "";
            position: absolute;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: rgba(129, 140, 248, 0.22);
            transform: scale(0);
            opacity: 0;
            pointer-events: none;
            left: var(--ripple-x, 50%);
            top: var(--ripple-y, 50%);
            transition:
                transform .45s ease-out,
                opacity .55s ease-out;
        }

        .bundle-row.ripple-active::after {
            transform: scale(12);
            opacity: 0;
        }

        .bundle-row:active {
            transform: scale(0.985);
            transition: transform .12s ease-out;
        }

        /* ===== MOBILE ===== */
        @media (max-width: 767.98px) {
            .card {
                border-radius: 14px;
            }

            .page-wrap {
                padding-bottom: 6rem;
            }

            .header-row {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-header-pill {
                width: 100%;
                justify-content: center;
            }

            .filter-header {
                flex-direction: column;
            }

            .filter-header-right,
            .filter-controls {
                width: 100%;
            }

            .filter-controls input {
                flex: 1;
                min-width: 0;
            }

            .table-wrap {
                overflow-x: visible;
            }

            .table-bundles-ready {
                border-collapse: separate;
                border-spacing: 0 8px;
                width: 100%;
                table-layout: fixed;
            }

            .table-bundles-ready thead {
                display: none;
            }

            .table-bundles-ready tbody tr {
                display: block;
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
                border-radius: 12px;
                border: 1px solid rgba(148, 163, 184, 0.32);
                padding: .6rem .75rem .65rem;
                margin-bottom: .45rem;
                cursor: pointer;
                background: rgba(255, 255, 255, 0.98);
                box-shadow:
                    0 8px 20px rgba(15, 23, 42, 0.08),
                    0 0 0 1px rgba(15, 23, 42, 0.02);
                touch-action: pan-y;
            }

            .td-desktop-only {
                display: none !important;
            }

            .td-mobile-only {
                display: block;
                width: 100%;
                box-sizing: border-box;
            }

            .mobile-row-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: .75rem;
                margin-bottom: .2rem;
            }

            .mobile-row-header-left {
                font-size: .82rem;
                display: flex;
                flex-direction: column;
                gap: .06rem;
                min-width: 0;
            }

            .mobile-row-header-topline {
                display: flex;
                align-items: center;
                gap: .35rem;
            }

            .mobile-row-header-left .row-index {
                font-size: .7rem;
                color: var(--muted);
            }

            .mobile-row-header-left .item-code {
                font-size: .96rem;
                font-weight: 700;
                color: #4f46e5;
                letter-spacing: .08px;
            }

            .mobile-row-header-left .item-name {
                font-size: .78rem;
                color: var(--muted);
            }

            .mobile-row-header-right {
                text-align: right;
                font-size: .76rem;
                min-width: 96px;
            }

            .mobile-row-header-right .qty-label {
                font-size: .64rem;
            }

            .mobile-row-header-right .qty-pill {
                font-size: .96rem;
                padding: .18rem .8rem;
            }

            .mobile-row-meta {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: .35rem;
                margin-top: .12rem;
            }

            .mobile-badge-row {
                display: flex;
                flex-wrap: wrap;
                gap: .25rem;
            }

            .mobile-extra {
                text-align: right;
                font-size: .72rem;
            }

            .mobile-extra .mono {
                font-size: .72rem;
            }

            .selectable-mode .table-bundles-ready tbody tr {
                cursor: pointer;
            }
        }

        @media (min-width: 768px) {
            .td-mobile-only {
                display: none !important;
            }
        }
    </style>
@endpush

@section('content')
    @php
        /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $bundles */
        $selectMode = request()->boolean('select', false);
    @endphp

    <div class="page-wrap {{ $selectMode ? 'selectable-mode' : '' }}">

        {{-- HEADER --}}
        <div class="card mb-2">
            <div class="card-section">
                <div class="header-row">
                    <div class="d-flex align-items-center">
                        <div class="header-icon-circle">
                            <i class="bi bi-layers"></i>
                        </div>
                        <div class="header-title d-flex flex-column gap-1">
                            <h1>Bundles WIP-FIN</h1>
                            <div class="header-subtitle">
                                Hasil jahit yang sedang disimpan di gudang <span class="mono">WIP-FIN</span>.
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-md-row gap-2">
                        <a href="{{ route('production.finishing_jobs.index') }}"
                            class="btn btn-sm btn-header-pill btn-header-muted d-flex align-items-center gap-2">
                            <i class="bi bi-list-ul"></i>
                            <span>Daftar Finishing</span>
                        </a>

                        @if ($selectMode)
                            <a href="{{ request()->fullUrlWithQuery(['select' => 0]) }}"
                                class="btn btn-sm btn-header-pill btn-header-accent d-flex align-items-center gap-2">
                                <i class="bi bi-eye"></i>
                                <span>Mode monitor</span>
                            </a>
                        @else
                            <a href="{{ request()->fullUrlWithQuery(['select' => 1]) }}"
                                class="btn btn-sm btn-header-pill btn-header-accent d-flex align-items-center gap-2">
                                <i class="bi bi-check2-square"></i>
                                <span>Mode pilih Finishing</span>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- SUMMARY + FILTER --}}
        <div class="card mb-2">
            <div class="card-section">

                <div class="summary-row mb-3">
                    <span class="summary-chip summary-chip-qty">
                        Total WIP-FIN:
                        <span class="mono">{{ number_format($totalWipQty ?? 0, 2, ',', '.') }} pcs</span>
                    </span>

                    <span class="summary-chip summary-chip-count">
                        Bundles: <span class="mono">{{ $totalBundles ?? 0 }}</span>
                    </span>

                    @if ($selectMode)
                        <span class="summary-chip summary-chip-selected">
                            Dipilih: <span id="summary-selected-count" class="mono">0</span> bundle
                        </span>
                    @endif
                </div>

                <form method="get" class="filter-header">
                    <div>
                        <div class="filter-header-left-title mb-1">
                            <i class="bi bi-funnel text-muted"></i>
                            <h2>Bundles siap proses finishing</h2>
                        </div>
                        <div class="help">
                            @if ($selectMode)
                                Tap kartu untuk memilih / batal pilih ke Finishing Job.
                            @else
                                Gunakan pencarian untuk monitoring WIP-FIN per item / lot.
                            @endif
                        </div>
                    </div>

                    <div class="filter-header-right">
                        <div class="filter-controls">

                            <div class="input-group input-group-sm">
                                <span class="input-group-text border-end-0">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" name="q" value="{{ request('q') }}"
                                    class="form-control form-control-sm border-start-0"
                                    placeholder="Cari kode bundle, item, lot...">
                            </div>

                            <input type="hidden" name="select" value="{{ $selectMode ? 1 : 0 }}">

                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                Terapkan
                            </button>

                        </div>
                    </div>

                </form>

            </div>
        </div>

        {{-- LIST BUNDLES --}}
        @if ($selectMode)
            <form id="finishing-select-form" method="get" action="{{ route('production.finishing_jobs.create') }}">
                <div id="selected-bundle-inputs"></div>
        @endif

        <div class="card mb-2">
            <div class="card-section">
                <div class="table-wrap">
                    <table class="table table-sm align-middle mono table-bundles-ready mb-0">
                        <tbody>
                            @forelse ($bundles as $idx => $b)
                                @php
                                    $wipQty = (float) ($b->wip_qty ?? 0);
                                    $lot = $b->lot;
                                    $item = $b->finishedItem;

                                    // ====== LABEL TANGGAL SETOR TERAKHIR ======
                                    $setorDateLabel = '-';
                                    if (!empty($b->last_return_date)) {
                                        try {
                                            // kalau last_return_date string Y-m-d
                                            $setorDateLabel = id_date($b->last_return_date);
                                        } catch (\Throwable $e) {
                                            try {
                                                $setorDateLabel = \Carbon\Carbon::parse($b->last_return_date)->format(
                                                    'd/m/Y',
                                                );
                                            } catch (\Throwable $e2) {
                                                $setorDateLabel = (string) $b->last_return_date;
                                            }
                                        }
                                    }

                                    // ====== LABEL OPERATOR SETOR TERAKHIR ======
                                    $setorByLabel = null;
                                    $opCode = $b->last_return_operator_code ?? null;
                                    $opName = $b->last_return_operator_name ?? null;

                                    if ($opCode && $opName) {
                                        $setorByLabel = $opCode . ' — ' . $opName;
                                    } elseif ($opName) {
                                        $setorByLabel = $opName;
                                    } elseif ($opCode) {
                                        $setorByLabel = $opCode;
                                    }
                                @endphp

                                <tr class="bundle-row bundle-row-base" data-bundle-id="{{ $b->id }}"
                                    @if ($selectMode) style="cursor: pointer;" @endif>
                                    {{-- DESKTOP INDEX SEDERHANA --}}
                                    <td class="td-desktop-only align-top">
                                        <span class="small text-muted">
                                            {{ $bundles->firstItem() + $idx }}
                                        </span>
                                        @if ($selectMode)
                                            <span class="select-badge">
                                                <i class="bi bi-check2"></i>
                                            </span>
                                        @endif

                                        {{-- Info setor di desktop (mini) --}}
                                        <div class="small text-muted mt-1">
                                            Setor:
                                            <span class="mono">{{ $setorDateLabel }}</span>
                                            @if ($setorByLabel)
                                                • {{ $setorByLabel }}
                                            @endif
                                        </div>
                                    </td>

                                    {{-- MOBILE / CARD VIEW --}}
                                    <td class="td-mobile-only" colspan="7">
                                        <div class="mobile-row-header position-relative">
                                            <div class="mobile-row-header-left">
                                                <div class="mobile-row-header-topline">
                                                    <span class="row-index">#{{ $bundles->firstItem() + $idx }}</span>
                                                    <span class="item-code mono">
                                                        {{ $item?->code ?? '-' }}
                                                    </span>
                                                </div>
                                                @if ($item?->name)
                                                    <div class="item-name text-truncate">
                                                        {{ $item->name }}
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="mobile-row-header-right">
                                                <div class="qty-label mb-1">WIP-FIN</div>
                                                <span class="qty-pill">
                                                    {{ number_format($wipQty, 2, ',', '.') }}
                                                </span>
                                            </div>

                                            @if ($selectMode)
                                                <span class="select-badge">
                                                    <i class="bi bi-check2"></i>
                                                </span>
                                            @endif
                                        </div>

                                        <div class="mobile-row-meta">
                                            <div class="mobile-badge-row">
                                                @if ($b->bundle_code)
                                                    <span class="badge-mini badge-bundle mono">
                                                        <span>Bundle</span>{{ $b->bundle_code }}
                                                    </span>
                                                @endif

                                                @if ($lot)
                                                    <span class="badge-mini badge-lot mono">
                                                        <span>Lot</span>{{ $lot->code }}
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="mobile-extra">
                                                <span class="mobile-muted-soft">
                                                    Setor:
                                                    <span class="mono">{{ $setorDateLabel }}</span>
                                                    @if ($setorByLabel)
                                                        • {{ $setorByLabel }}
                                                    @endif
                                                </span>
                                            </div>
                                        </div>

                                        @if ($lot && $lot->item)
                                            <div class="mobile-muted-soft mt-1">
                                                Kain: <span class="mono">{{ $lot->item->code }}</span>
                                                @if ($lot->item->color)
                                                    • {{ $lot->item->color }}
                                                @endif
                                            </div>
                                        @endif

                                        @if ($selectMode)
                                            <div class="mobile-muted-soft mt-1 text-end">
                                                Tap kartu untuk pilih / batal pilih
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted small py-3">
                                        Belum ada bundle di WIP-FIN dengan qty &gt; 0.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $bundles->links() }}
                </div>
            </div>
        </div>

        @if ($selectMode)
            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="{{ request()->fullUrlWithQuery(['select' => 0]) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i>
                    <span class="d-none d-sm-inline">Batal pilih</span>
                </a>

                <button type="submit" id="btn-next-finishing" class="btn btn-sm btn-primary" disabled>
                    <i class="bi bi-check2-circle"></i>
                    <span class="text-light">Lanjut Finishing Job</span>
                </button>
            </div>
            </form>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const rows = document.querySelectorAll('.bundle-row');
            const isSelectMode = "{{ $selectMode ? '1' : '0' }}";

            /** RIPPLE EFFECT (mirip behavior tap lembut) */
            rows.forEach(row => {
                row.addEventListener('mousedown', function(e) {
                    const rect = row.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;

                    row.style.setProperty('--ripple-x', `${x}px`);
                    row.style.setProperty('--ripple-y', `${y}px`);

                    row.classList.remove('ripple-active');
                    void row.offsetWidth;
                    row.classList.add('ripple-active');
                }, {
                    passive: true
                });
            });

            /** UPDATE SUMMARY + HIDDEN INPUTS */
            function updateSelectedBundles() {
                const summary = document.getElementById('summary-selected-count');
                const inputs = document.getElementById('selected-bundle-inputs');
                const btnNext = document.getElementById('btn-next-finishing');

                let selectedIds = [];

                rows.forEach(row => {
                    if (row.classList.contains('bundle-row-selected')) {
                        selectedIds.push(row.dataset.bundleId);
                    }
                });

                if (summary) summary.textContent = selectedIds.length;
                if (btnNext) btnNext.disabled = (selectedIds.length === 0);

                if (inputs) {
                    inputs.innerHTML = '';
                    selectedIds.forEach(id => {
                        const input = document.createElement('input');
                        input.type = "hidden";
                        input.name = "bundle_ids[]";
                        input.value = id;
                        inputs.appendChild(input);
                    });
                }
            }

            /** CLICK HANDLER: SELECT vs MONITOR */
            rows.forEach(row => {
                row.addEventListener('click', function(e) {
                    // kalau klik langsung ke input / button / link, biarkan default
                    if (e.target.tagName === 'INPUT' || e.target.closest('button, a')) return;

                    // MODE SELECT
                    if (isSelectMode === "1") {
                        row.classList.toggle('bundle-row-selected');
                        updateSelectedBundles();
                        return;
                    }

                    // MODE MONITOR → langsung arahkan ke create finishing job 1 bundle
                    const id = row.dataset.bundleId;
                    const url = "{{ route('production.finishing_jobs.create') }}" +
                        "?bundle_ids[]=" + id;
                    window.location.href = url;
                });
            });
        });
    </script>
@endpush
