{{-- resources/views/production/sewing_pickups/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Sewing Pickup')

@push('head')
    <style>
        :root {
            --card-radius-lg: 16px;
        }

        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding-inline: .9rem;
        }

        /* background lembut */
        body[data-theme="light"] .page-wrap {
            background: linear-gradient(to bottom,
                    #f4f5fb 0,
                    #f7f8fc 30%,
                    #f9fafb 100%);
        }

        .card {
            background: var(--card);
            border-radius: var(--card-radius-lg);
            border: 1px solid rgba(148, 163, 184, 0.18);
            box-shadow:
                0 10px 30px rgba(15, 23, 42, 0.08),
                0 0 0 1px rgba(15, 23, 42, 0.02);
        }

        .card-section {
            padding: 1rem 1.1rem;
        }

        @media (min-width: 768px) {
            .card-section {
                padding: 1.1rem 1.4rem;
            }
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

        /* ====== HEADER PAGE ====== */
        .header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .header-title {
            display: flex;
            flex-direction: column;
            gap: .1rem;
        }

        .header-title h1 {
            font-size: 1.05rem;
            font-weight: 700;
        }

        .header-subtitle {
            font-size: .8rem;
            color: var(--muted);
        }

        .btn-header-secondary {
            border-radius: 999px;
            padding-inline: .7rem;
            padding-block: .35rem;
            font-size: .8rem;
        }

        /* ====== FORM HEADER (TANGGAL + GUDANG (desktop)) ====== */
        .field-block {
            display: flex;
            flex-direction: column;
            gap: .2rem;
        }

        .field-label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-weight: 600;
            color: var(--muted);
        }

        .field-input-sm {
            font-size: .86rem;
        }

        .field-static {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .42rem .85rem;
            border-radius: 999px;
            background: rgba(248, 250, 252, 0.96);
            border: 1px solid rgba(148, 163, 184, 0.45);
            font-size: .82rem;
            max-width: 100%;
        }

        .field-static .code {
            font-weight: 600;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .field-static .name {
            color: var(--muted);
            font-size: .8rem;
            white-space: nowrap;
            text-overflow: ellipsis;
            overflow: hidden;
        }

        /* ====== BUNDLES HEADER / SUMMARY ====== */
        .summary-chip {
            border-radius: 999px;
            padding: .16rem .7rem;
            font-size: .74rem;
            background: rgba(148, 163, 184, 0.10);
        }

        .summary-selected {
            font-size: .78rem;
            color: var(--muted);
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            align-items: center;
        }

        .filter-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: .7rem;
            align-items: flex-start;
        }

        .filter-header-left {
            display: flex;
            flex-direction: column;
            gap: .25rem;
        }

        .filter-header-left-title {
            display: flex;
            align-items: center;
            gap: .3rem;
        }

        .filter-header-left-title h2 {
            margin: 0;
            font-size: .96rem;
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

        .btn-toggle-picked {
            border-radius: 999px;
        }

        .btn-toggle-picked.active {
            background: rgba(37, 99, 235, 0.08);
            border-color: rgba(37, 99, 235, .8);
            color: #1d4ed8;
        }

        .input-group-sm .form-control {
            font-size: .8rem;
        }

        .input-group-sm .input-group-text {
            font-size: .8rem;
        }

        /* ====== TABLE / ROW STATE ====== */
        .bundle-row {
            transition:
                background-color .16s ease,
                box-shadow .16s ease,
                border-color .16s ease,
                transform .1s ease;
        }

        .bundle-row td {
            border-top-color: rgba(148, 163, 184, 0.25) !important;
        }

        .row-empty {
            box-shadow: inset 3px 0 0 rgba(148, 163, 184, .35);
        }

        .row-picked {
            background: radial-gradient(circle at top left,
                    rgba(37, 99, 235, 0.10) 0,
                    rgba(255, 255, 255, 0.95) 45%);
            box-shadow:
                inset 3px 0 0 rgba(37, 99, 235, .9),
                0 0 0 1px rgba(191, 219, 254, 0.8);
        }

        .row-picked:hover {
            transform: translateY(-1px);
        }

        .qty-ready-pill {
            border-radius: 999px;
            padding: .08rem .58rem;
            font-size: .78rem;
            font-weight: 600;
            background: rgba(13, 110, 253, 0.08);
            color: #0d6efd;
            display: inline-block;
            max-width: 100%;
        }

        .qty-input {
            font-weight: 500;
            transition: font-weight .12s ease, box-shadow .12s ease, border-color .12s ease;
        }

        .qty-input-active {
            font-weight: 600;
            border-color: rgba(37, 99, 235, .75);
            box-shadow: 0 0 0 1px rgba(37, 99, 235, .4);
        }

        /* ============ MOBILE (<= 767.98px) ============ */
        @media (max-width: 767.98px) {

            .page-wrap {
                padding-bottom: 7.5rem;
                overflow-x: hidden;
            }

            /* saat keyboard muncul, tambah ruang bawah */
            body.keyboard-open .page-wrap {
                padding-bottom: 13rem;
            }

            .table-wrap {
                overflow-x: visible;
            }

            .table-sewing-pickup {
                width: 100%;
                table-layout: fixed;
                border-collapse: separate;
                border-spacing: 0 8px;
            }

            .table-sewing-pickup thead {
                display: none;
            }

            .table-sewing-pickup tbody tr {
                display: block;
                width: 100%;
                box-sizing: border-box;
                border-radius: 14px;
                border: 1px solid rgba(148, 163, 184, 0.3);
                padding: .7rem .8rem .75rem;
                margin-bottom: .45rem;
                background: rgba(255, 255, 255, 0.96);
                cursor: pointer;
                box-shadow:
                    0 8px 24px rgba(15, 23, 42, 0.10),
                    0 0 0 1px rgba(15, 23, 42, 0.02);
                overflow: hidden;
            }

            .table-sewing-pickup tbody tr:last-child {
                margin-bottom: 2.75rem;
            }

            .table-sewing-pickup td {
                display: block;
                border: none !important;
                padding: .1rem 0;
            }

            .td-mobile-extra {
                padding: 0 !important;
            }

            .td-desktop-only {
                display: none !important;
            }

            .card {
                border-radius: 15px;
                box-shadow:
                    0 10px 26px rgba(15, 23, 42, 0.10),
                    0 0 0 1px rgba(15, 23, 42, 0.02);
            }

            .card-section {
                padding: .85rem .9rem;
            }

            .header-row {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-header-secondary {
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

            .filter-controls button {
                white-space: nowrap;
            }

            /* ===== MOBILE CARD CONTENT ===== */
            .mobile-row-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: .75rem;
                margin-bottom: .22rem;
            }

            .mobile-row-header-left {
                font-size: .82rem;
                display: flex;
                flex-direction: column;
                gap: .06rem;
                flex: 1;
                min-width: 0;
            }

            .mobile-row-header-topline {
                display: flex;
                align-items: center;
                gap: .4rem;
                min-width: 0;
            }

            .mobile-row-header-left .row-index {
                font-size: .72rem;
                color: var(--muted);
                flex-shrink: 0;
            }

            /* FOCAL KODE ITEM */
            .mobile-row-header-left .item-code {
                font-size: 1.02rem !important;
                font-weight: 800 !important;
                color: #2563eb !important;
                letter-spacing: .12px;
                white-space: nowrap;
                max-width: 100%;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .mobile-row-header-left .item-name {
                font-size: .78rem !important;
                color: var(--muted) !important;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .mobile-row-header-right {
                text-align: right;
                font-size: .78rem;
                min-width: 96px;
                flex-shrink: 0;
            }

            .mobile-row-header-right .qty-ready-label {
                font-size: .65rem !important;
                font-weight: 600;
                color: var(--muted);
                text-transform: uppercase;
                letter-spacing: .28px;
                margin-bottom: .04rem;
            }

            .mobile-row-header-right .qty-ready-value {
                display: flex;
                justify-content: flex-end;
            }

            /* FOCAL QTY READY DI MOBILE */
            .mobile-row-header-right .qty-ready-value .qty-ready-pill {
                background: rgba(37, 99, 235, 0.12) !important;
                color: #1d4ed8 !important;
                padding: .18rem .58rem !important;
                border-radius: 999px !important;
                font-size: .94rem !important;
                font-weight: 800 !important;
                max-width: 100%;
                min-width: 0;
                text-align: center;
                display: inline-block;
            }

            .mobile-check-wrap {
                margin-top: .3rem;
                display: flex;
                justify-content: flex-end;
            }

            .mobile-check-wrap .row-check {
                transform: scale(0.95);
            }

            /* BUNDLE & LOT compact ala finishing */
            .mobile-row-meta {
                font-size: .74rem;
                color: var(--muted);
                margin-bottom: .18rem;
                display: flex;
                justify-content: space-between;
                gap: .5rem;
            }

            .mobile-row-meta-left {
                flex: 1;
                min-width: 0;
            }

            .mobile-row-meta-label {
                font-size: .68rem;
                text-transform: uppercase;
                color: var(--muted);
                opacity: .9;
                letter-spacing: .08em;
            }

            .mobile-row-meta-value {
                font-size: .78rem;
                word-break: break-word;
            }

            .mobile-row-meta-value .mono {
                font-size: .8rem;
            }

            .mobile-row-footer {
                margin-top: .2rem;
                display: flex;
                flex-direction: column;
                gap: .32rem;
            }

            .mobile-row-footer-left .pickup-label {
                font-size: .74rem !important;
                font-weight: 600 !important;
                color: #2563eb !important;
                margin-bottom: .1rem;
                letter-spacing: .17px;
                text-transform: uppercase;
            }

            /* FOCAL INPUT QTY – rata tengah */
            .mobile-row-footer input.qty-input {
                font-size: .94rem !important;
                font-weight: 600 !important;
                padding-block: .4rem !important;
                border: 1.4px solid rgba(37, 99, 235, .45) !important;
                border-radius: 10px !important;
                box-shadow: inset 0 0 0 1px rgba(37, 99, 235, .18) !important;
                text-align: center !important;
            }

            .mobile-row-footer input.qty-input:focus {
                border-color: #2563eb !important;
                box-shadow: 0 0 0 2px rgba(37, 99, 235, .3) !important;
            }

            /* Floating footer (Simpan + back) */
            .form-footer {
                position: fixed;
                right: .9rem;
                bottom: 4.2rem;
                left: auto;
                z-index: 30;
                display: inline-flex !important;
                flex-direction: row-reverse;
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

            /* sembunyikan gudang tujuan di mobile */
            .gudang-section {
                display: none !important;
            }
        }

        @media (min-width: 768px) {
            .td-mobile-extra {
                display: none !important;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap py-3 py-md-4">

        {{-- HEADER PAGE --}}
        <div class="card mb-3">
            <div class="card-section">
                <div class="header-row">
                    <div class="header-title">
                        <h1>Sewing Pickup</h1>
                        <div class="header-subtitle">
                            Pilih bundle dan isi qty pickup. Operator jahit dipilih saat menekan <strong>Simpan</strong>.
                        </div>
                    </div>

                    <a href="{{ route('production.sewing_pickups.bundles_ready') }}"
                        class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 btn-header-secondary">
                        <i class="bi bi-box-seam"></i>
                        <span>Bundles Ready</span>
                    </a>
                </div>
            </div>
        </div>

        <form id="sewing-pickup-form" action="{{ route('production.sewing_pickups.store') }}" method="post">
            @csrf

            @php
                $defaultWarehouseId = old('warehouse_id') ?: optional($warehouses->firstWhere('code', 'WIP-SEW'))->id;
                $defaultWarehouse = $defaultWarehouseId ? $warehouses->firstWhere('id', $defaultWarehouseId) : null;

                // default operator (hidden, dipilih / diubah di modal)
                $autoDefaultOperatorId = (int) old('operator_id') ?: optional($operators->first())->id;
            @endphp

            {{-- Hidden operator_id (di-set lewat modal), dan gudang --}}
            <input type="hidden" name="operator_id" id="operator_id_hidden" value="{{ $autoDefaultOperatorId }}">
            <input type="hidden" name="warehouse_id" value="{{ $defaultWarehouseId }}">

            {{-- HEADER FORM: TANGGAL + GUDANG (desktop only) --}}
            <div class="card mb-3">
                <div class="card-section">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-3">
                            <div class="field-block">
                                <div class="field-label">Tanggal ambil</div>
                                <input type="date" name="date"
                                    class="form-control form-control-sm field-input-sm @error('date') is-invalid @enderror"
                                    value="{{ old('date', now()->format('Y-m-d')) }}">
                                @error('date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12 col-md-5 gudang-section">
                            <div class="field-block">
                                <div class="field-label">Gudang tujuan</div>
                                <div class="field-static">
                                    @if ($defaultWarehouse)
                                        <span class="code">{{ $defaultWarehouse->code }}</span>
                                        <span class="name">— {{ $defaultWarehouse->name }}</span>
                                    @else
                                        <span class="text-danger small">Gudang WIP-SEW belum diset.</span>
                                    @endif
                                </div>
                                @error('warehouse_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-12 col-md-4 d-none d-md-block">
                            <div class="help">
                                Operator jahit akan dipilih di langkah konfirmasi saat menyimpan transaksi.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TABEL PILIH BUNDLE --}}
            <div class="card mb-3">
                <div class="card-section">
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

                    {{-- HEADER FILTER / SUMMARY --}}
                    <div class="filter-header mb-2">
                        <div class="filter-header-left">
                            <div class="filter-header-left-title">
                                <i class="bi bi-funnel text-muted"></i>
                                <h2>Pilih bundles yang mau dijahit</h2>
                            </div>
                            <div class="summary-selected">
                                <span class="summary-chip">
                                    <span id="summary-selected-bundles">0</span> bundle dipilih
                                </span>
                                <span class="summary-chip">
                                    Total pickup: <span id="summary-selected-qty">0,00</span> pcs
                                </span>
                                <span class="summary-chip">
                                    Ready: {{ number_format($totalQtyReady, 2, ',', '.') }} pcs
                                </span>
                                <span class="summary-chip d-none d-md-inline">
                                    Total bundle ready: {{ $totalBundlesReady }}
                                </span>
                            </div>
                        </div>
                        <div class="filter-header-right">
                            <div class="filter-controls">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text border-end-0">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text" id="bundle-filter-input"
                                        class="form-control form-control-sm border-start-0"
                                        placeholder="Cari kode, nama item, atau lot...">
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary btn-toggle-picked"
                                    id="toggle-picked-only">
                                    Tampilkan yang dipilih
                                </button>
                            </div>
                        </div>
                    </div>

                    @error('lines')
                        <div class="alert alert-danger py-1 small mb-2">
                            {{ $message }}
                        </div>
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

                                        $oldQtyName = 'lines.' . $idx . '.qty_bundle';

                                        $cutDateObj =
                                            $b->cuttingJob?->cutting_date ??
                                            ($b->cuttingJob?->cut_date ?? $b->cuttingJob?->created_at);
                                        $cutDateLabel = $cutDateObj ? $cutDateObj->format('d/m/Y') : '-';
                                    @endphp

                                    <tr class="bundle-row row-empty" data-row-index="{{ $idx }}"
                                        data-qty-ready="{{ $qtyRemain }}" data-bundle-code="{{ $b->bundle_code }}"
                                        data-item-code="{{ $b->finishedItem?->code }}"
                                        data-item-name="{{ $b->finishedItem?->name }}">

                                        {{-- HIDDEN bundle_id --}}
                                        <td class="d-none">
                                            <input type="hidden" name="lines[{{ $idx }}][bundle_id]"
                                                value="{{ $b->id }}">
                                        </td>

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
                                                class="form-control form-control-sm text-end qty-input @error($oldQtyName) is-invalid @enderror"
                                                value="{{ old($oldQtyName, $defaultQtyPickup) }}">
                                            @error($oldQtyName)
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </td>

                                        <td class="d-none d-md-table-cell td-desktop-only text-end">
                                            <button type="button"
                                                class="btn btn-outline-primary btn-sm py-0 px-2 btn-pick"
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
                                                        <span class="item-code mono">
                                                            {{ $b->finishedItem?->code ?? '-' }}
                                                        </span>
                                                    </div>
                                                    @if ($b->finishedItem?->name)
                                                        <div class="item-name text-truncate">
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
                                                    <div class="mobile-check-wrap">
                                                        <input type="checkbox" class="form-check-input row-check"
                                                            data-row-index="{{ $idx }}">
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- BUNDLE & LOT --}}
                                            <div class="mobile-row-meta">
                                                <div class="mobile-row-meta-left">
                                                    <div class="mobile-row-meta-label">Bundle</div>
                                                    <div class="mobile-row-meta-value">
                                                        <span class="mono">{{ $b->bundle_code }}</span>
                                                    </div>
                                                </div>
                                                <div class="mobile-row-meta-left text-end">
                                                    <div class="mobile-row-meta-label">Lot</div>
                                                    <div class="mobile-row-meta-value">
                                                        @if ($b->cuttingJob?->lot)
                                                            <span class="mono">{{ $b->cuttingJob->lot->code }}</span>
                                                            <span class="text-muted small">
                                                                ({{ $b->cuttingJob->lot->item?->code }})
                                                            </span>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- TGL CUTTING & CUTTING PCS --}}
                                            <div class="mobile-row-meta">
                                                <div class="mobile-row-meta-left">
                                                    <div class="mobile-row-meta-label">Tgl Cutting</div>
                                                    <div class="mobile-row-meta-value">
                                                        {{ $cutDateLabel }}
                                                    </div>
                                                </div>
                                                <div class="mobile-row-meta-left text-end">
                                                    <div class="mobile-row-meta-label">Cutting</div>
                                                    <div class="mobile-row-meta-value">
                                                        {{ number_format($b->qty_pcs, 2, ',', '.') }} pcs
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mobile-row-footer">
                                                <div class="mobile-row-footer-left">
                                                    <div class="pickup-label">
                                                        Pickup (maks {{ number_format($qtyRemain, 2, ',', '.') }})
                                                    </div>
                                                    <input type="number" step="0.01" min="0"
                                                        inputmode="decimal"
                                                        class="form-control form-control-sm qty-input @error($oldQtyName) is-invalid @enderror"
                                                        value="{{ old($oldQtyName, $defaultQtyPickup) }}"
                                                        placeholder="Masukkan ambil jahit">
                                                    @error($oldQtyName)
                                                        <div class="invalid-feedback">
                                                            {{ $message }}</div>
                                                    @enderror
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted small py-3">
                                            Belum ada bundle hasil QC Cutting dengan qty ready
                                            &gt; 0.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

            {{-- SUBMIT --}}
            <div class="d-flex justify-content-between align-items-center mb-5 form-footer">
                <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i>
                    <span class="d-none d-sm-inline">Batal</span>
                </a>

                <button type="submit" class="btn btn-sm btn-primary" id="btn-submit-main" disabled>
                    <i class="bi bi-check2-circle" id="btn-submit-icon"></i>
                    <span class="text-light" id="btn-submit-label">Simpan</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Modal konfirmasi + pilih operator --}}
    <div class="modal fade" id="confirmSubmitModal" tabindex="-1" aria-labelledby="confirmSubmitLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-sm modal-md">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="confirmSubmitLabel">Konfirmasi Sewing Pickup</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    {{-- PILIH OPERATOR --}}
                    <div class="mb-3">
                        <div class="text-muted small mb-1">
                            Pilih <strong>Operator Jahit</strong> untuk semua bundle yang diambil.
                        </div>

                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text border-end-0">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" id="operator-search-input"
                                placeholder="Cari kode / nama operator...">
                        </div>

                        <div class="list-group small" id="operator-list">
                            @foreach ($operators as $op)
                                <button type="button"
                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center operator-option"
                                    data-id="{{ $op->id }}" data-code="{{ $op->code }}"
                                    data-name="{{ $op->name }}">
                                    <div class="me-2">
                                        <div class="mono fw-semibold">{{ $op->code }}</div>
                                        <div class="text-muted">{{ $op->name }}</div>
                                    </div>
                                    <i class="bi bi-check-lg text-primary opacity-0 operator-check-icon"></i>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <hr class="my-2">

                    {{-- RINGKASAN BUNDLE --}}
                    <p class="text-muted small mb-2">
                        Periksa kembali tanggal & qty pickup per bundle di bawah ini sebelum menyimpan.
                    </p>

                    <div id="confirm-summary" class="pt-1 small"></div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary"
                        data-bs-dismiss="modal">Batal</button>
                    <button type="button" id="btn-confirm-submit" class="btn btn-sm btn-primary" disabled>
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

            const searchInput = document.getElementById('bundle-filter-input');
            const togglePickedBtn = document.getElementById('toggle-picked-only');
            let showPickedOnly = false;

            const operatorHidden = document.getElementById('operator_id_hidden');
            const operatorSearchInput = document.getElementById('operator-search-input');
            const operatorOptions = document.querySelectorAll('.operator-option');

            const submitBtn = document.getElementById('btn-submit-main');
            const submitLabel = document.getElementById('btn-submit-label');

            const confirmModalEl = document.getElementById('confirmSubmitModal');
            const confirmBtn = document.getElementById('btn-confirm-submit');

            let confirmModal = null;
            if (confirmModalEl && window.bootstrap && bootstrap.Modal) {
                confirmModal = new bootstrap.Modal(confirmModalEl);
            }

            // Tandai operator yang aktif di list (ceklist biru kecil)
            function refreshOperatorActive() {
                const currentId = operatorHidden ? operatorHidden.value : null;
                operatorOptions.forEach(function(btn) {
                    const checkIcon = btn.querySelector('.operator-check-icon');
                    if (!checkIcon) return;

                    if (currentId && btn.dataset.id === currentId.toString()) {
                        checkIcon.classList.remove('opacity-0');
                    } else {
                        checkIcon.classList.add('opacity-0');
                    }
                });

                if (confirmBtn) {
                    confirmBtn.disabled = !currentId;
                }
            }
            refreshOperatorActive();

            // Klik salah satu operator -> set hidden
            operatorOptions.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const code = this.dataset.code || '';
                    const name = this.dataset.name || '';

                    if (operatorHidden) {
                        operatorHidden.value = id;
                    }

                    refreshOperatorActive();
                });
            });

            // Search operator by code / name
            if (operatorSearchInput) {
                operatorSearchInput.addEventListener('input', function() {
                    const term = this.value.toLowerCase().trim();
                    operatorOptions.forEach(function(btn) {
                        const code = (btn.dataset.code || '').toLowerCase();
                        const name = (btn.dataset.name || '').toLowerCase();
                        const match = !term || code.includes(term) || name.includes(term);
                        btn.style.display = match ? '' : 'none';
                    });
                });
            }

            // Helper keyboard-open state (mirip sewing return)
            function setKeyboardOpen(isOpen) {
                if (isOpen) {
                    document.body.classList.add('keyboard-open');
                } else {
                    document.body.classList.remove('keyboard-open');
                }
            }

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
                const term = (searchInput && searchInput.value ? searchInput.value
                        .toLowerCase() : '')
                    .trim();

                rows.forEach(function(row) {
                    const qtyInputs = row.querySelectorAll('input.qty-input');
                    const current = qtyInputs.length ? parseFloat(qtyInputs[0].value || '0') :
                        0;
                    const isPicked = current > 0;

                    const text = row.textContent.toLowerCase();
                    const matchSearch = !term || text.includes(term);
                    const matchPicked = !showPickedOnly || isPicked;

                    row.style.display = (matchSearch && matchPicked) ? '' : 'none';
                });
            }

            function updateSubmitButtonState(pickedBundles, totalPickupQty) {
                if (!submitBtn || !submitLabel) return;

                const canSubmit = pickedBundles > 0 && totalPickupQty > 0;

                submitBtn.disabled = !canSubmit;
                if (canSubmit) {
                    submitLabel.textContent = 'Pilih Penjahit';
                } else {
                    submitLabel.textContent = 'Belum Ambil';
                }
            }

            function updateGlobalSummary() {
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

                updateSubmitButtonState(pickedBundles, totalPickupQty);
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

                const dateInput = document.querySelector('input[name="date"]');
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

                // Asumsi: qtyInputs[0] = DESKTOP, qtyInputs[1] = MOBILE
                const desktopInput = qtyInputs[0];
                const mobileInput = qtyInputs.length > 1 ? qtyInputs[1] : null;

                function getCurrentQty() {
                    return parseFloat(desktopInput.value || '0');
                }

                function isPicked() {
                    return getCurrentQty() > 0;
                }

                function syncInputsFromDesktop() {
                    const val = desktopInput.value;
                    if (mobileInput) {
                        mobileInput.value = val;
                    }
                }

                function syncDesktopFromMobile() {
                    if (!mobileInput) return;
                    desktopInput.value = mobileInput.value;
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

                    desktopInput.value = nextQty > 0 ? nextQty : '';
                    if (mobileInput) {
                        mobileInput.value = desktopInput.value;
                    }

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

                row.addEventListener('click', function(e) {
                    if (
                        e.target.tagName === 'INPUT' ||
                        e.target.closest('button')
                    ) {
                        return;
                    }
                    togglePicked();
                });

                pickButtons.forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        applyFromState(true); // Ambil semua = qty ready (desktop)
                    });
                });

                rowChecks.forEach(function(chk) {
                    chk.addEventListener('change', function(e) {
                        e.stopPropagation();
                        applyFromState(chk.checked);
                    });
                });

                // Desktop input
                desktopInput.addEventListener('focus', function() {
                    this.select();
                    this.classList.add('qty-input-active');
                });

                desktopInput.addEventListener('blur', function() {
                    this.classList.remove('qty-input-active');
                    syncInputsFromDesktop();
                    updateVisual();
                    updateGlobalSummary();
                });

                desktopInput.addEventListener('input', function() {
                    syncInputsFromDesktop();
                    updateVisual();
                    updateGlobalSummary();
                });

                // Mobile input (mirror ke desktop + behavior keyboard)
                if (mobileInput) {
                    mobileInput.addEventListener('focus', function() {
                        this.select();
                        this.classList.add('qty-input-active');
                        setKeyboardOpen(true);

                        if (window.innerWidth < 768) {
                            const inputEl = this;
                            setTimeout(function() {
                                try {
                                    inputEl.scrollIntoView({
                                        behavior: 'smooth',
                                        block: 'center',
                                        inline: 'nearest'
                                    });
                                } catch (e) {
                                    const rect = inputEl.getBoundingClientRect();
                                    const absoluteTop = rect.top + window.pageYOffset - 140;
                                    window.scrollTo({
                                        top: absoluteTop,
                                        behavior: 'smooth'
                                    });
                                }
                            }, 180);
                        }
                    });

                    mobileInput.addEventListener('blur', function() {
                        this.classList.remove('qty-input-active');
                        setKeyboardOpen(false);
                        syncDesktopFromMobile();
                        updateVisual();
                        updateGlobalSummary();
                    });

                    mobileInput.addEventListener('input', function() {
                        syncDesktopFromMobile();
                        updateVisual();
                        updateGlobalSummary();
                    });
                }

                // Initial visual state
                syncInputsFromDesktop();
                updateVisual();
            });

            // Pasang listener global supaya keyboard-open juga aktif jika ada input lain
            const allQtyInputs = document.querySelectorAll('input.qty-input');
            allQtyInputs.forEach(function(inp) {
                inp.addEventListener('focus', function() {
                    if (window.innerWidth < 768) {
                        setKeyboardOpen(true);
                    }
                });
                inp.addEventListener('blur', function() {
                    setKeyboardOpen(false);
                });
            });

            updateGlobalSummary();
            applyRowVisibility();

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    applyRowVisibility();
                });
            }

            if (togglePickedBtn) {
                togglePickedBtn.addEventListener('click', function() {
                    showPickedOnly = !showPickedOnly;
                    togglePickedBtn.classList.toggle('active', showPickedOnly);
                    applyRowVisibility();
                });
            }

            // Modal konfirmasi + submit
            const form = document.getElementById('sewing-pickup-form');

            if (form && confirmModal && confirmBtn) {
                let isConfirmed = false;

                form.addEventListener('submit', function(e) {
                    if (isConfirmed) {
                        isConfirmed = false;
                        return;
                    }

                    e.preventDefault();

                    // kalau belum ada pickup, jangan apa-apa (button sudah disabled sebenarnya)
                    let hasPickup = false;
                    rows.forEach(function(row) {
                        const qtyInputs = row.querySelectorAll('input.qty-input');
                        if (!qtyInputs.length) return;
                        const current = parseFloat(qtyInputs[0].value || '0');
                        if (current > 0) {
                            hasPickup = true;
                        }
                    });
                    if (!hasPickup) {
                        return;
                    }

                    buildConfirmSummary();
                    refreshOperatorActive();
                    confirmModal.show();
                });

                confirmBtn.addEventListener('click', function() {
                    if (!operatorHidden || !operatorHidden.value) {
                        return;
                    }

                    isConfirmed = true;
                    confirmModal.hide();

                    // state "Menyimpan..."
                    if (submitBtn && submitLabel) {
                        submitBtn.disabled = true;
                        submitLabel.textContent = 'Menyimpan...';
                    }

                    form.submit();
                });
            }
        });
    </script>
@endpush
