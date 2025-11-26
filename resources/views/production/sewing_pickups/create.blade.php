@extends('layouts.app')

@section('title', 'Produksi • Sewing Pickup')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .help {
            color: var(--muted);
            font-size: .84rem;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .14rem .5rem;
            font-size: .7rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        /* ====== HEADER ====== */
        .header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
        }

        /* ====== ROW STATE ====== */
        .bundle-row {
            transition:
                background-color .16s ease,
                box-shadow .16s ease,
                border-color .16s ease;
        }

        .bundle-row td {
            border-top-color: rgba(148, 163, 184, 0.25) !important;
        }

        /* belum ada Qty Pickup (qty = 0) → aksen abu kiri */
        .row-empty {
            box-shadow: inset 3px 0 0 rgba(148, 163, 184, .35);
        }

        /* qty > 0 → aksen biru */
        .row-picked {
            background: rgba(13, 110, 253, 0.03);
            box-shadow:
                inset 3px 0 0 rgba(13, 110, 253, .9),
                0 0 0 1px rgba(148, 163, 184, 0.32);
        }

        /* Qty Ready pill (biru, konsisten dengan Bundles Ready) */
        .qty-ready-pill {
            border-radius: 999px;
            padding: .06rem .55rem;
            font-size: .78rem;
            font-weight: 600;
            background: rgba(13, 110, 253, 0.08);
            color: #0d6efd;
        }

        .summary-chip {
            border-radius: 999px;
            padding: .15rem .6rem;
            font-size: .76rem;
            background: rgba(148, 163, 184, 0.12);
        }

        .summary-selected {
            font-size: .78rem;
            color: var(--muted);
        }

        /* Qty input lebih kontras (bold + saat fokus) */
        .qty-input {
            font-weight: 500;
            transition: font-weight .12s ease, box-shadow .12s ease, border-color .12s ease;
        }

        .qty-input-active {
            font-weight: 700;
            border-color: rgba(37, 99, 235, .75);
            box-shadow: 0 0 0 1px rgba(37, 99, 235, .5);
        }

        /* Filter controls */
        .filter-controls {
            gap: .5rem;
        }

        .btn-toggle-picked.active {
            background: rgba(37, 99, 235, 0.08);
            border-color: rgba(37, 99, 235, .8);
            color: #1d4ed8;
        }

        /* ============ MOBILE (<= 767.98px) ============ */
        @media (max-width: 767.98px) {
            .card {
                border-radius: 12px;
            }

            /* Sedikit lebih lega untuk bottom-nav + tombol floating */
            .page-wrap {
                padding-bottom: 6rem;
            }

            .header-row {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-header-secondary {
                width: 100%;
                justify-content: center;
                border-radius: 999px;
                padding-block: .45rem;
                font-size: .82rem;
            }

            .table-sewing-pickup {
                border-collapse: separate;
                border-spacing: 0 6px;
            }

            .table-sewing-pickup thead {
                display: none;
            }

            .table-sewing-pickup tbody tr {
                display: block;
                border-radius: 11px;
                border: 1px solid var(--line);
                padding: .52rem .6rem .6rem;
                margin-bottom: .5rem;
                background: var(--card);
                cursor: pointer;
            }

            .table-sewing-pickup tbody tr:last-child {
                margin-bottom: 0;
            }

            .table-sewing-pickup td {
                display: block;
                border: none !important;
                padding: .12rem 0;
            }

            .td-mobile-extra {
                padding: 0 !important;
            }

            .td-desktop-only {
                display: none !important;
            }

            .mobile-row-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: .75rem;
                margin-bottom: .2rem;
            }

            .mobile-row-header-left {
                font-size: .84rem;
                display: flex;
                flex-direction: column;
                gap: .1rem;
            }

            .mobile-row-header-topline {
                display: flex;
                align-items: center;
                gap: .32rem;
            }

            .mobile-row-header-left .row-index {
                font-size: .74rem;
                color: var(--muted);
            }

            .mobile-row-header-left .item-code {
                font-weight: 700;
            }

            .mobile-row-header-left .item-name {
                font-size: .78rem;
                color: var(--muted);
            }

            .row-check {
                transform: scale(0.95);
            }

            .mobile-row-header-right {
                text-align: right;
                font-size: .78rem;
            }

            .mobile-row-header-right .qty-ready-label {
                font-size: .7rem;
                text-transform: uppercase;
                color: var(--muted);
            }

            .mobile-row-header-right .qty-ready-value {
                font-weight: 600;
            }

            .mobile-row-meta {
                font-size: .75rem;
                color: var(--muted);
                margin-bottom: .18rem;
            }

            .mobile-row-footer {
                margin-top: .2rem;
                display: flex;
                flex-direction: column;
                gap: .35rem;
            }

            .mobile-row-footer-left .pickup-label {
                font-size: .7rem;
                text-transform: uppercase;
                color: var(--muted);
                margin-bottom: .1rem;
            }

            .mobile-row-footer-right .btn-pick {
                width: 100%;
                border-radius: 999px;
                padding-block: .32rem;
                font-size: .78rem;
            }

            /* FOOTER: floating minimalis kanan bawah */
            .form-footer {
                position: fixed;
                right: .9rem;
                bottom: 4.2rem;
                /* pas di atas mobile bottom-nav */
                left: auto;

                z-index: 30;

                display: inline-flex !important;
                flex-direction: row-reverse;
                /* Simpan di kanan, Batal di kiri */
                align-items: center !important;
                gap: .45rem;

                margin: 0;
                padding: 0;

                background: transparent;
                border: none;
            }

            .form-footer .btn {
                width: auto;
                border-radius: 999px;
                padding-inline: .9rem;
                padding-block: .35rem;
                box-shadow:
                    0 10px 20px rgba(15, 23, 42, .25),
                    0 3px 8px rgba(15, 23, 42, .2);
            }

            .form-footer .btn-primary {
                font-weight: 600;
                background: linear-gradient(135deg, #0d6efd 0%, #2563eb 60%, #1d4ed8 100%);
                border: none;
                display: inline-flex;
                align-items: center;
                gap: .35rem;
            }

            .form-footer .btn-outline-secondary {
                font-size: .78rem;
                padding-inline: .7rem;
                padding-block: .3rem;
                background: rgba(248, 250, 252, 0.96);
                border-color: rgba(148, 163, 184, .7);
                display: inline-flex;
                align-items: center;
                gap: .25rem;
            }
        }

        /* ============ DESKTOP (>= 768px) ============ */
        @media (min-width: 768px) {
            .td-mobile-extra {
                display: none !important;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap py-3 py-md-4">

        {{-- HEADER --}}
        <div class="card p-3 mb-3">
            <div class="header-row">
                <div>
                    <h1 class="h5 mb-1">Sewing Pickup</h1>
                    <div class="help">
                        Ambil bundle hasil cutting dari WIP-CUT ke WIP-SEW oleh operator jahit.
                    </div>
                </div>

                <a href="{{ route('production.sewing_pickups.bundles_ready') }}"
                    class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 btn-header-secondary">
                    <i class="bi bi-box-seam"></i>
                    <span>Bundles Ready</span>
                </a>
            </div>
        </div>

        <form id="sewing-pickup-form" action="{{ route('production.sewing_pickups.store') }}" method="post">
            @csrf

            {{-- HEADER FORM --}}
            @php
                $defaultWarehouseId = old('warehouse_id') ?: optional($warehouses->firstWhere('code', 'WIP-SEW'))->id;
                $defaultWarehouse = $defaultWarehouseId ? $warehouses->firstWhere('id', $defaultWarehouseId) : null;

                $oldOperatorId = (int) old('operator_id');
                $autoDefaultOperatorId = $oldOperatorId ?: ($operators->count() === 1 ? $operators->first()->id : null);
            @endphp

            <div class="card p-3 mb-3">
                <div class="row g-3">
                    {{-- CHANGED: in mobile hide Tanggal & Gudang Sewing --}}
                    <div class="col-md-3 col-6 d-none d-md-block">
                        <div class="help mb-1">Tanggal</div>
                        <input type="date" name="date"
                            class="form-control form-control-sm @error('date') is-invalid @enderror"
                            value="{{ old('date', now()->format('Y-m-d')) }}">
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Gudang Sewing (WIP-SEW, fixed) --}}
                    <div class="col-md-3 col-6 d-none d-md-block">
                        <div class="help mb-1">Gudang Sewing</div>

                        <div class="form-control form-control-sm bg-light">
                            @if ($defaultWarehouse)
                                <span class="mono">{{ $defaultWarehouse->code }}</span>
                                <span class="text-muted">— {{ $defaultWarehouse->name }}</span>
                            @else
                                <span class="text-danger small">Gudang WIP-SEW belum diset.</span>
                            @endif
                        </div>

                        <input type="hidden" name="warehouse_id" value="{{ $defaultWarehouseId }}">

                        @error('warehouse_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- CHANGED: text center di mobile, kiri di desktop --}}
                    <div class="col-md-3 col-12 text-center text-md-start">
                        <div class="help mb-1">Operator Jahit</div>
                        <select name="operator_id"
                            class="form-select form-select-sm @error('operator_id') is-invalid @enderror"
                            id="operator-select">
                            <option value="">-- Pilih Operator --</option>
                            @foreach ($operators as $op)
                                <option value="{{ $op->id }}"
                                    {{ $autoDefaultOperatorId === $op->id ? 'selected' : '' }}>
                                    {{ $op->code }} — {{ $op->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('operator_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- TABEL PILIH BUNDLE --}}
            <div class="card p-3 mb-3">
                @php
                    $oldLines = old('lines', []);
                    $preselectedBundleId = request('bundle_id');

                    $displayBundles = $preselectedBundleId
                        ? $bundles->where('id', (int) $preselectedBundleId)
                        : $bundles;

                    $displayBundles = $displayBundles
                        ->filter(function ($b) {
                            $qtyOk = (float) ($b->qty_cutting_ok ?? 0);
                            $qtyRemain = (float) ($b->qty_remaining_for_sewing ?? $qtyOk);
                            return $qtyRemain > 0;
                        })
                        ->values();

                    $totalBundlesReady = $displayBundles->count();
                    $totalQtyReady = $displayBundles->sum(function ($b) {
                        $qtyOk = (float) ($b->qty_cutting_ok ?? 0);
                        return (float) ($b->qty_remaining_for_sewing ?? $qtyOk);
                    });
                @endphp

                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <div>
                        <h2 class="h6 mb-0">Pilih Bundles</h2>
                        {{-- CHANGED: hilangkan teks bantuan mobile/desktop --}}
                        {{-- <div class="help d-none d-md-block">
                            Centang / klik baris / tombol <strong>Pick</strong> untuk isi Qty Pickup = Qty Ready.
                        </div>
                        <div class="help d-md-none">
                            Tap kartu / centang / tombol <strong>Ambil = Ready</strong>.
                        </div> --}}
                    </div>

                    {{-- CHANGED: hilangkan chip summary & summary-selected --}}
                    {{-- @if ($totalBundlesReady > 0)
                        <div class="d-flex flex-column align-items-md-end gap-1">
                            <div class="d-flex flex-wrap gap-2">
                                <span class="summary-chip mono">
                                    {{ number_format($totalBundlesReady, 0, ',', '.') }} bundle ready
                                </span>
                                <span class="summary-chip mono">
                                    {{ number_format($totalQtyReady, 2, ',', '.') }} pcs ready
                                </span>
                            </div>
                            <div class="summary-selected mono" id="summary-selected-wrapper">
                                <span id="summary-selected-bundles">0</span> bundle dipilih /
                                <span id="summary-selected-qty">0,00</span> pcs pickup
                            </div>
                        </div>
                    @endif --}}
                </div>

                {{-- CHANGED: hilangkan filter search + toggle --}}
                {{-- @if ($totalBundlesReady > 0)
                    <div class="d-flex flex-wrap align-items-center filter-controls mb-2">
                        <div class="input-group input-group-sm" style="max-width: 260px;">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" id="bundle-filter-input" class="form-control border-start-0"
                                placeholder="Cari bundle / item / lot...">
                        </div>

                        <button type="button" id="toggle-picked-only"
                            class="btn btn-sm btn-outline-secondary ms-0 ms-md-2">
                            <i class="bi bi-funnel"></i>
                            <span class="d-none d-sm-inline">Hanya yang diambil</span>
                        </button>
                    </div>
                @endif --}}

                @error('lines')
                    <div class="alert alert-danger py-1 small mb-2">
                        {{ $message }}</div>
                @enderror

                <div class="table-wrap">
                    <table class="table table-sm align-middle mono table-sewing-pickup mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px;" class="text-center">#</th>
                                <th style="width: 130px;">Bundle</th>
                                <th style="width: 160px;">Item Jadi</th>
                                <th style="width: 140px;">Lot</th>
                                <th style="width: 110px;" class="text-end">Cutting</th>
                                <th style="width: 110px;" class="text-end">Ready</th>
                                <th style="width: 130px;" class="text-end">Pickup</th>
                                <th style="width: 80px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($displayBundles as $idx => $b)
                                @php
                                    $qc = $b->qcResults
                                        ? $b->qcResults->where('stage', 'cutting')->sortByDesc('qc_date')->first()
                                        : null;

                                    $oldLine = $oldLines[$idx] ?? null;

                                    $qtyOk = (float) ($b->qty_cutting_ok ?? ($qc?->qty_ok ?? $b->qty_pcs));
                                    $qtyRemain = (float) ($b->qty_remaining_for_sewing ?? $qtyOk);

                                    if ($qtyRemain <= 0) {
                                        continue;
                                    }

                                    $defaultQtyPickup = $oldLine['qty_bundle'] ?? null;

                                    if ($defaultQtyPickup === null && $preselectedBundleId == $b->id) {
                                        $defaultQtyPickup = $qtyRemain;
                                    }
                                @endphp

                                <tr class="bundle-row row-empty" data-row-index="{{ $idx }}"
                                    data-qty-ready="{{ $qtyRemain }}" data-bundle-code="{{ $b->bundle_code }}"
                                    data-item-code="{{ $b->finishedItem?->code }}"
                                    data-item-name="{{ $b->finishedItem?->name }}">
                                    <input type="hidden" name="lines[{{ $idx }}][bundle_id]"
                                        value="{{ $b->id }}">

                                    {{-- DESKTOP --}}
                                    <td class="d-none d-md-table-cell td-desktop-only text-center">
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <input type="checkbox" class="form-check-input row-check"
                                                data-row-index="{{ $idx }}">
                                            <span class="small text-muted">{{ $loop->iteration }}</span>
                                        </div>
                                    </td>

                                    <td class="d-none d-md-table-cell td-desktop-only">
                                        <span class="fw-semibold">{{ $b->bundle_code }}</span>
                                    </td>

                                    <td class="d-none d-md-table-cell td-desktop-only">
                                        <span class="fw-bold">
                                            {{ $b->finishedItem?->code ?? '-' }}
                                        </span>
                                        <div class="small text-muted">
                                            {{ $b->finishedItem?->name ?? '' }}
                                        </div>
                                    </td>

                                    <td class="d-none d-md-table-cell td-desktop-only">
                                        {{ $b->cuttingJob?->lot?->item?->code ?? '-' }}
                                        @if ($b->cuttingJob?->lot)
                                            <span class="badge-soft bg-light border text-muted ms-1">
                                                {{ $b->cuttingJob->lot->code }}
                                            </span>
                                        @endif
                                    </td>

                                    <td class="d-none d-md-table-cell td-desktop-only text-end">
                                        {{ number_format($b->qty_pcs, 2, ',', '.') }}
                                    </td>

                                    <td class="d-none d-md-table-cell td-desktop-only text-end">
                                        <span class="qty-ready-pill">
                                            {{ number_format($qtyRemain, 2, ',', '.') }}
                                        </span>
                                    </td>

                                    <td class="d-none d-md-table-cell td-desktop-only text-end">
                                        <input type="number" step="0.01" min="0" inputmode="decimal"
                                            name="lines[{{ $idx }}][qty_bundle]"
                                            class="form-control form-control-sm text-end qty-input @error("lines.$idx.qty_bundle") is-invalid @enderror"
                                            value="{{ $defaultQtyPickup ?? '' }}"
                                            placeholder="{{ number_format($qtyRemain, 2, ',', '.') }}">
                                        @error("lines.$idx.qty_bundle")
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    <td class="d-none d-md-table-cell td-desktop-only text-end">
                                        <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2 btn-pick"
                                            data-row-index="{{ $idx }}">
                                            Pick
                                        </button>
                                    </td>

                                    {{-- MOBILE CARD --}}
                                    <td class="td-mobile-extra" colspan="8">
                                        <div class="mobile-row-header">
                                            <div class="mobile-row-header-left">
                                                <div class="mobile-row-header-topline">
                                                    <span class="row-index">#{{ $loop->iteration }}</span>
                                                    <input type="checkbox" class="form-check-input row-check"
                                                        data-row-index="{{ $idx }}">
                                                    <span class="item-code">
                                                        {{ $b->finishedItem?->code ?? '-' }}
                                                    </span>
                                                </div>
                                                @if ($b->finishedItem?->name)
                                                    <div class="item-name">
                                                        {{ $b->finishedItem?->name }}
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="mobile-row-header-right">
                                                <div class="qty-ready-label">Qty Ready</div>
                                                <div class="qty-ready-value">
                                                    <span class="qty-ready-pill">
                                                        {{ number_format($qtyRemain, 2, ',', '.') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mobile-row-meta">
                                            @if ($b->cuttingJob?->lot)
                                                Lot:
                                                <span class="mono">
                                                    {{ $b->cuttingJob->lot->code }}
                                                </span>
                                                <span class="text-muted">
                                                    ({{ $b->cuttingJob->lot->item?->code }})
                                                </span>
                                            @else
                                                Lot: -
                                            @endif
                                        </div>

                                        <div class="mobile-row-footer">
                                            <div class="mobile-row-footer-left">
                                                <div class="pickup-label">
                                                    Pickup (max {{ number_format($qtyRemain, 2, ',', '.') }})
                                                </div>
                                                <input type="number" step="0.01" min="0" inputmode="decimal"
                                                    name="lines[{{ $idx }}][qty_bundle]"
                                                    class="form-control form-control-sm text-end qty-input @error("lines.$idx.qty_bundle") is-invalid @enderror"
                                                    value="{{ $defaultQtyPickup ?? '' }}"
                                                    placeholder="{{ number_format($qtyRemain, 2, ',', '.') }}">
                                                @error("lines.$idx.qty_bundle")
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                            <div class="mobile-row-footer-right">
                                                <button type="button" class="btn btn-primary btn-sm btn-pick"
                                                    data-row-index="{{ $idx }}">
                                                    Ambil = Ready
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted small py-3">
                                        Belum ada bundle hasil QC Cutting dengan qty ready &gt; 0.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>

            {{-- SUBMIT --}}
            <div class="d-flex justify-content-between align-items-center mb-5 form-footer">
                <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i>
                    <span class="d-none d-sm-inline">Batal</span>
                </a>

                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-check2-circle"></i>
                    <span class="text-light">Simpan</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Bootstrap Modal Konfirmasi --}}
    <div class="modal fade" id="confirmSubmitModal" tabindex="-1" aria-labelledby="confirmSubmitLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm modal-md">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="confirmSubmitLabel">Simpan Sewing Pickup?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">
                        Pastikan operator jahit dan qty pickup per bundle sudah benar.
                    </p>
                    <p class="text-muted small mb-2">
                        Transaksi ini akan memindahkan stok dari <strong>WIP-CUT</strong> ke
                        <strong>WIP-SEW</strong>.
                    </p>

                    {{-- Summary operator, tanggal & bundles terpilih --}}
                    <div id="confirm-summary" class="border-top pt-2 mt-2 small"></div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-bs-dismiss="modal">Batal</button>
                    <button type="button" id="btn-confirm-submit" class="btn btn-sm btn-primary">
                        Ya, Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.bundle-row');
            const summaryBundlesSpan = document.getElementById('summary-selected-bundles');
            const summaryQtySpan = document.getElementById('summary-selected-qty');
            const confirmSummaryEl = document.getElementById('confirm-summary');

            // CHANGED: filter elements sekarang tidak ada di DOM,
            // tapi kode di bawah aman karena cek null.
            const searchInput = document.getElementById('bundle-filter-input');
            const togglePickedBtn = document.getElementById('toggle-picked-only');
            let showPickedOnly = false;

            // Formatter qty
            let nf;
            try {
                nf = new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            } catch (e) {
                nf = {
                    format: function(num) {
                        return (num || 0).toFixed(2);
                    }
                };
            }

            function applyRowVisibility() {
                const term = (searchInput && searchInput.value ? searchInput.value.toLowerCase() : '').trim();

                rows.forEach(function(row) {
                    const qtyInputs = row.querySelectorAll('input.qty-input');
                    const current = qtyInputs.length ? parseFloat(qtyInputs[0].value || '0') : 0;
                    const isPicked = current > 0;

                    const text = row.textContent.toLowerCase();
                    const matchSearch = !term || text.includes(term);
                    const matchPicked = !showPickedOnly || isPicked;

                    row.style.display = (matchSearch && matchPicked) ? '' : 'none';
                });
            }

            function updateGlobalSummary() {
                // CHANGED: sekarang summary di header di-hide, jadi kalau element tidak ada langsung keluar
                if (!summaryBundlesSpan || !summaryQtySpan) return;

                let pickedBundles = 0;
                let totalPickupQty = 0;

                rows.forEach(function(row) {
                    const qtyInputs = row.querySelectorAll('input.qty-input');
                    if (!qtyInputs.length) return;

                    const current = parseFloat(qtyInputs[0].value || '0');
                    if (current > 0) {
                        pickedBundles += 1;
                        totalPickupQty += current;
                    }
                });

                summaryBundlesSpan.textContent = pickedBundles.toString();
                summaryQtySpan.textContent = nf.format(totalPickupQty);

                // apply filter condition (especially when toggle "hanya yang diambil" aktif)
                applyRowVisibility();
            }

            function escapeHtml(str) {
                if (!str) return '';
                return str
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function buildConfirmSummary() {
                if (!confirmSummaryEl) return;

                const operatorSelect = document.getElementById('operator-select');
                let operatorText = '(belum dipilih)';

                if (operatorSelect && operatorSelect.value) {
                    operatorText = operatorSelect.options[operatorSelect.selectedIndex].text;
                }

                const dateInput = document.querySelector('input[name="date"]'); // CHANGED: ambil Tanggal Ambil
                const pickupDate = dateInput ? dateInput.value : '';

                const lines = [];
                rows.forEach(function(row) {
                    const qtyInputs = row.querySelectorAll('input.qty-input');
                    if (!qtyInputs.length) return;

                    const current = parseFloat(qtyInputs[0].value || '0');
                    if (current > 0) {
                        const bundleCode = row.dataset.bundleCode || '-';
                        const itemCode = row.dataset.itemCode || '-';
                        const itemName = row.dataset.itemName || '';

                        lines.push({
                            bundle: bundleCode,
                            item: itemCode,
                            name: itemName,
                            qty: current
                        });
                    }
                });

                if (!lines.length) {
                    confirmSummaryEl.innerHTML = `
                        <div class="text-muted">
                            Belum ada bundle yang diambil.
                        </div>
                    `;
                    return;
                }

                // CHANGED: list Item Jadi + Qty, plus menampilkan Tanggal Ambil
                const listHtml = lines.map(function(line) {
                    const itemLabel =
                        `${escapeHtml(line.item)}${line.name ? ' — ' + escapeHtml(line.name) : ''}`;
                    const bundleLabel = escapeHtml(line.bundle);

                    return `
                        <li class="d-flex justify-content-between align-items-start mb-1">
                            <div class="me-2" style="max-width: 70%;">
                                <div class="mono fw-semibold text-truncate">
                                    ${itemLabel}
                                </div>
                                <div class="text-muted small">
                                    Bundle: ${bundleLabel}
                                </div>
                            </div>
                            <span class="mono text-end">${nf.format(line.qty)} pcs</span>
                        </li>
                    `;
                }).join('');

                confirmSummaryEl.innerHTML = `
                    <div class="mb-2">
                        <div class="text-muted small mb-1">Tanggal Ambil</div>
                        <div class="fw-semibold">
                            ${pickupDate ? escapeHtml(pickupDate) : '-'}
                        </div>
                    </div>
                    <div class="mb-2">
                        <div class="text-muted small mb-1">Operator Jahit</div>
                        <div class="fw-semibold">
                            ${escapeHtml(operatorText)}
                        </div>
                    </div>
                    <div class="text-muted small mb-1">Bundles diambil (Item Jadi &amp; Qty)</div>
                    <ul class="list-unstyled mb-0">
                        ${listHtml}
                    </ul>
                `;
            }

            rows.forEach(function(row) {
                const qtyReady = parseFloat(row.dataset.qtyReady || '0');
                const qtyInputs = row.querySelectorAll('input.qty-input');
                const pickButtons = row.querySelectorAll('.btn-pick');
                const rowChecks = row.querySelectorAll('.row-check');

                if (!qtyInputs.length) return;

                function getCurrentQty() {
                    return parseFloat(qtyInputs[0].value || '0');
                }

                function isPicked() {
                    return getCurrentQty() > 0;
                }

                function updateVisual() {
                    const picked = isPicked();

                    rowChecks.forEach(function(chk) {
                        chk.checked = picked;
                    });

                    if (picked) {
                        row.classList.add('row-picked');
                        row.classList.remove('row-empty');
                    } else {
                        row.classList.remove('row-picked');
                        row.classList.add('row-empty');
                    }
                }

                function applyFromState(picked) {
                    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

                    const nextQty = picked ? qtyReady : 0;

                    qtyInputs.forEach(function(input) {
                        input.value = nextQty > 0 ? nextQty : '';
                    });

                    updateVisual();
                    updateGlobalSummary();

                    window.scrollTo({
                        top: scrollTop,
                        behavior: 'auto'
                    });
                }

                function togglePicked() {
                    const nextState = !isPicked();
                    applyFromState(nextState);
                }

                // Klik baris → toggle (kecuali klik input/checkbox/button)
                row.addEventListener('click', function(e) {
                    if (
                        e.target.tagName === 'INPUT' ||
                        e.target.closest('button')
                    ) {
                        return;
                    }
                    togglePicked();
                });

                // Tombol Pick → toggle
                pickButtons.forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        togglePicked();
                    });
                });

                // Checkbox → set qty = ready / 0
                rowChecks.forEach(function(chk) {
                    chk.addEventListener('change', function(e) {
                        e.stopPropagation();
                        applyFromState(chk.checked);
                    });
                });

                // Input qty: select all + bold saat fokus, update summary
                qtyInputs.forEach(function(input) {
                    input.addEventListener('focus', function() {
                        this.select();
                        this.classList.add('qty-input-active');
                    });

                    input.addEventListener('blur', function() {
                        this.classList.remove('qty-input-active');
                        updateVisual();
                        updateGlobalSummary();
                    });

                    input.addEventListener('input', function() {
                        updateVisual();
                        updateGlobalSummary();
                    });
                });

                // init state dari old()
                updateVisual();
            });

            // summary awal + visibility awal
            updateGlobalSummary();
            applyRowVisibility();

            // FILTER: search (saat ini tidak ada input-nya, tapi cek null aman)
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    applyRowVisibility();
                });
            }

            // FILTER: toggle hanya yang diambil (button sudah tidak ada, ini aman)
            if (togglePickedBtn) {
                togglePickedBtn.addEventListener('click', function() {
                    showPickedOnly = !showPickedOnly;
                    togglePickedBtn.classList.toggle('active', showPickedOnly);
                    applyRowVisibility();
                });
            }

            // Konfirmasi dengan Bootstrap Modal
            const form = document.getElementById('sewing-pickup-form');
            const confirmModalEl = document.getElementById('confirmSubmitModal');
            const confirmBtn = document.getElementById('btn-confirm-submit');

            if (form && confirmModalEl && confirmBtn && window.bootstrap && bootstrap.Modal) {
                const confirmModal = new bootstrap.Modal(confirmModalEl);
                let isConfirmed = false;

                form.addEventListener('submit', function(e) {
                    if (isConfirmed) {
                        isConfirmed = false;
                        return;
                    }

                    e.preventDefault();

                    // CHANGED: peringatan jika operator belum dipilih
                    const operatorSelect = document.getElementById('operator-select');
                    if (!operatorSelect || !operatorSelect.value) {
                        alert('Silakan pilih operator jahit terlebih dahulu sebelum menyimpan.');
                        return;
                    }

                    buildConfirmSummary(); // isi daftar bundle + operator + tanggal
                    confirmModal.show();
                });

                confirmBtn.addEventListener('click', function() {
                    isConfirmed = true;
                    confirmModal.hide();
                    form.submit();
                });
            }

            // Fokus otomatis ke Operator Jahit di mobile
            const operatorSelect = document.getElementById('operator-select');
            if (operatorSelect && window.innerWidth < 768) {
                operatorSelect.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                setTimeout(function() {
                    operatorSelect.focus();
                    try {
                        operatorSelect.click();
                    } catch (e) {}
                }, 300);
            }
        });
    </script>
@endpush
