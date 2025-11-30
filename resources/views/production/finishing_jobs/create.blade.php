@extends('layouts.app')

@section('title', 'Produksi • Finishing Job Baru')

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

        /* Background lembut, selaras dengan WIP-FIN */
        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(129, 140, 248, 0.08) 0,
                    rgba(45, 212, 191, 0.10) 18%,
                    #f9fafb 52%,
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
            font-size: .8rem;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .14rem .5rem;
            font-size: .7rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        /* ===== HEADER PAGE ===== */
        .header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: .7rem;
        }

        .header-title-block {
            display: flex;
            flex-direction: column;
            gap: .1rem;
        }

        .header-title-block h1 {
            font-size: 1.06rem;
            font-weight: 700;
        }

        .header-subtitle {
            font-size: .8rem;
            color: var(--muted);
        }

        .header-icon {
            width: 40px;
            height: 40px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 30% 30%,
                    rgba(45, 212, 191, 0.9),
                    rgba(129, 140, 248, 0.18));
            box-shadow:
                0 0 0 1px rgba(79, 70, 229, 0.40),
                0 12px 26px rgba(15, 23, 42, 0.32);
            color: #0f766e;
        }

        .btn-header-secondary {
            border-radius: 999px;
            padding-inline: .7rem;
            padding-block: .35rem;
            font-size: .8rem;
        }

        /* ===== SUMMARY / PILL ===== */
        .summary-row {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
            align-items: center;
        }

        .summary-chip {
            border-radius: 999px;
            padding: .16rem .7rem;
            font-size: .74rem;
            display: inline-flex;
            align-items: center;
            gap: .25rem;
        }

        .summary-chip-date {
            background: rgba(148, 163, 184, 0.10);
            color: #111827;
        }

        .summary-chip-src {
            background: rgba(45, 212, 191, 0.12);
            color: #0f766e;
            font-weight: 600;
        }

        .summary-chip-lines {
            background: rgba(129, 140, 248, 0.10);
            color: #4f46e5;
            font-weight: 600;
        }

        .pill-label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
        }

        .pill-value {
            font-size: .86rem;
        }

        /* ===== TABEL DETAIL ===== */
        .finishing-line-row td {
            vertical-align: middle;
        }

        .qty-pill-balance {
            border-radius: 999px;
            padding: .12rem .65rem;
            font-size: .8rem;
            font-weight: 600;
            background: rgba(129, 140, 248, 0.11);
            color: #312e81;
        }

        .qty-ok-text {
            font-size: .86rem;
            font-weight: 600;
            color: #15803d;
        }

        .qty-label-small {
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: .09em;
            color: var(--muted);
        }

        .form-control-sm.mono {
            font-variant-numeric: tabular-nums;
        }

        .table thead th {
            font-size: .76rem;
            text-transform: uppercase;
            letter-spacing: .09em;
            color: var(--muted);
            border-bottom-color: rgba(148, 163, 184, .4);
        }

        .badge-lot {
            border-radius: 999px;
            padding: .04rem .45rem;
            font-size: .68rem;
            background: rgba(15, 23, 42, 0.04);
            color: #4b5563;
        }

        /* ===== FOKUS: KODE BARANG LEBIH MENONJOL ===== */
        .item-code-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: .18rem .7rem;
            font-size: .9rem;
            font-weight: 700;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
            text-transform: uppercase;
            letter-spacing: .06em;
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.14),
                    rgba(37, 99, 235, 0.04));
            box-shadow:
                0 0 0 1px rgba(59, 130, 246, 0.35),
                0 6px 14px rgba(15, 23, 42, 0.18);
            color: #1d4ed8;
        }

        body[data-theme="dark"] .item-code-pill {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.30),
                    rgba(15, 23, 42, 0.9));
            box-shadow:
                0 0 0 1px rgba(129, 140, 248, 0.7),
                0 12px 30px rgba(15, 23, 42, 0.9);
            color: #e0e7ff;
        }

        .item-name-sub {
            font-size: .78rem;
            color: var(--muted);
        }

        /* BND & LOT compact badge */
        .bundle-label {
            font-size: .7rem;
            border-radius: 999px;
            padding: .04rem .5rem;
            background: rgba(15, 23, 42, 0.04);
            color: #4b5563;
        }

        body[data-theme="dark"] .bundle-label {
            background: rgba(15, 23, 42, 0.6);
            color: #e5e7eb;
        }

        /* ===== MOBILE ===== */
        @media (max-width: 767.98px) {
            .card {
                border-radius: 15px;
                box-shadow:
                    0 10px 26px rgba(15, 23, 42, 0.10),
                    0 0 0 1px rgba(15, 23, 42, 0.02);
            }

            .card-section {
                padding: .85rem .9rem;
            }

            .page-wrap {
                padding-bottom: 5rem;
            }

            .header-row {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-header-secondary {
                width: 100%;
                justify-content: center;
            }

            .summary-row {
                align-items: stretch;
            }

            .summary-chip {
                width: 100%;
                justify-content: space-between;
            }

            .table-wrap {
                margin: 0 -.35rem;
            }

            .table-finishing-lines thead {
                display: none;
            }

            .table-finishing-lines tbody tr {
                display: block;
                border-radius: 14px;
                border: 1px solid rgba(148, 163, 184, 0.3);
                padding: .7rem .7rem .65rem;
                margin-bottom: .45rem;
                background: rgba(255, 255, 255, 0.98);
                box-shadow:
                    0 8px 20px rgba(15, 23, 42, 0.09),
                    0 0 0 1px rgba(15, 23, 42, 0.02);
            }

            body[data-theme="dark"] .table-finishing-lines tbody tr {
                background: rgba(15, 23, 42, 0.96);
            }

            .table-finishing-lines tbody tr:last-child {
                margin-bottom: 0;
            }

            .table-finishing-lines td {
                display: block;
                border-top: none !important;
                padding: .12rem 0;
            }

            .line-mobile-header {
                display: flex;
                justify-content: space-between;
                gap: .6rem;
                margin-bottom: .35rem;
            }

            .line-mobile-header-left {
                font-size: .82rem;
            }

            .line-mobile-item {
                font-size: .78rem;
                color: var(--muted);
            }

            .line-mobile-header-right {
                text-align: right;
                min-width: 96px;
            }

            .line-mobile-qty {
                font-size: .9rem;
            }

            .line-mobile-qty-label {
                font-size: .68rem;
                text-transform: uppercase;
                letter-spacing: .09em;
                color: var(--muted);
            }

            .mobile-inline-inputs {
                display: flex;
                justify-content: space-between;
                gap: .6rem;
                margin-top: .25rem;
            }

            .mobile-inline-inputs>div {
                flex: 1;
            }

            .mobile-operator {
                margin-top: .4rem;
            }

            .desktop-only {
                display: none !important;
            }
        }

        @media (min-width: 768px) {
            .mobile-only {
                display: none !important;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $linesCount = is_array($lines ?? null) ? count($lines) : 0;
    @endphp

    <div class="page-wrap py-3 py-md-4">

        {{-- HEADER PAGE --}}
        <div class="card mb-3">
            <div class="card-section">
                <div class="header-row">
                    <div class="header-left">
                        <div class="header-icon">
                            <i class="bi bi-stars"></i>
                        </div>
                        <div class="header-title-block">
                            <h1>Finishing Job Baru</h1>
                            <div class="header-subtitle">
                                Proseskan bundles dari gudang <span class="mono">WIP-FIN</span> menjadi barang jadi.
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-column align-items-end gap-2 desktop-only">
                        <div class="pill-label mb-1">
                            Tanggal Finishing
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-calendar4-week text-muted"></i>
                            <span class="mono">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>

                {{-- Mobile: tanggal di bawah header --}}
                <div class="mt-3 mobile-only">
                    <div class="pill-label mb-1">Tanggal Finishing</div>
                    <div class="d-inline-flex align-items-center gap-2 px-2 py-1 rounded-pill"
                        style="background: rgba(148,163,184,.1);">
                        <i class="bi bi-calendar4-week text-muted"></i>
                        <span class="mono">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- FORM UTAMA --}}
        <form action="{{ route('production.finishing_jobs.store') }}" method="POST">
            @csrf

            {{-- CARD: RINGKASAN & SETTING --}}
            <div class="card mb-3">
                <div class="card-section">
                    @if ($errors->any())
                        <div class="alert alert-danger py-2 px-3 mb-3">
                            <div class="small fw-semibold mb-1">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Ada data yang perlu dicek lagi
                            </div>
                            <ul class="mb-0 small ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <input type="hidden" name="date" value="{{ $date }}">

                    <div class="summary-row mb-2">
                        <span class="summary-chip summary-chip-date">
                            <span class="pill-label">Tanggal</span>
                            <span class="pill-value mono">
                                {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
                            </span>
                        </span>

                        <span class="summary-chip summary-chip-src">
                            <span class="pill-label">Sumber</span>
                            <span class="pill-value">
                                Bundles WIP-FIN
                            </span>
                        </span>

                        <span class="summary-chip summary-chip-lines">
                            <span class="pill-label">Baris bundle</span>
                            <span class="pill-value mono">
                                {{ $linesCount }} baris
                            </span>
                        </span>
                    </div>

                    <div class="help">
                        @if ($linesCount > 0)
                            Qty masuk Finishing bisa disesuaikan per baris. Qty OK dihitung otomatis dari
                            <span class="mono">Qty Masuk - Reject</span>.
                        @else
                            Belum ada bundle terpilih dari WIP-FIN. Kembali ke halaman
                            <a href="{{ route('production.finishing_jobs.bundles_ready') }}">Bundles WIP-FIN</a>
                            lalu pilih bundle yang mau diproses.
                        @endif
                    </div>
                </div>
            </div>

            {{-- CARD: DETAIL BUNDLES --}}
            <div class="card mb-3">
                <div class="card-section">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <div class="pill-label mb-1">Detail Bundles</div>
                            <div class="help">
                                Satu baris = satu bundle dari WIP-FIN. Atur qty masuk & reject jika ada cacat.
                            </div>
                        </div>
                        <div class="desktop-only">
                            <a href="{{ route('production.finishing_jobs.bundles_ready', ['select' => 1]) }}"
                                class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                                <i class="bi bi-layers"></i>
                                <span>Pilih ulang bundles</span>
                            </a>
                        </div>
                    </div>

                    <div class="mobile-only mb-2">
                        <a href="{{ route('production.finishing_jobs.bundles_ready', ['select' => 1]) }}"
                            class="btn btn-sm btn-outline-secondary w-100 d-inline-flex align-items-center justify-content-center gap-1">
                            <i class="bi bi-layers"></i>
                            <span>Pilih ulang bundles</span>
                        </a>
                    </div>

                    @if ($linesCount === 0)
                        <div class="text-center text-muted small py-3">
                            Belum ada baris. Pilih dulu bundles dari halaman WIP-FIN.
                        </div>
                    @else
                        <div class="table-wrap">
                            <table class="table table-sm align-middle table-finishing-lines mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;">#</th>
                                        <th style="width: 240px;">Kode & Item</th>
                                        <th style="width: 130px;" class="text-end">Saldo WIP-FIN</th>
                                        <th style="width: 130px;" class="text-end">Qty Masuk</th>
                                        <th style="width: 130px;" class="text-end">Reject</th>
                                        <th style="width: 140px;" class="text-end">Qty OK</th>
                                        <th style="width: 150px;">Opr Jahit</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($lines as $i => $line)
                                        @php
                                            $bundleModel = $bundles->firstWhere('id', $line['bundle_id']);
                                            $itemLabel = $line['item_label'] ?? '';
                                            $wipBalance = (float) ($line['wip_balance'] ?? 0);

                                            // default qty_in = wip_balance (kecuali old() override)
                                            $defaultQtyIn = $wipBalance;
                                            $qtyIn = (float) old("lines.$i.qty_in", $defaultQtyIn);
                                            $qtyReject = (float) old("lines.$i.qty_reject", $line['qty_reject'] ?? 0);
                                            $qtyOk = max($qtyIn - $qtyReject, 0);

                                            $itemCode = $line['item_code'] ?? null;
                                            $itemName = $itemLabel;

                                            if (!$itemCode && $itemLabel) {
                                                $parts = preg_split('/\s+—\s+|\s+/', trim($itemLabel), 2);
                                                $itemCode = $parts[0] ?? '';
                                                $itemName = $parts[1] ?? $itemLabel;
                                            }

                                            $operatorName =
                                                $line['sewing_operator_name'] ?? ($line['operator_name'] ?? null);

                                        @endphp

                                        <tr class="finishing-line-row" data-line-index="{{ $i }}">
                                            {{-- HIDDEN binding minimal --}}
                                            <input type="hidden" name="lines[{{ $i }}][bundle_id]"
                                                value="{{ $line['bundle_id'] }}">
                                            <input type="hidden" name="lines[{{ $i }}][item_id]"
                                                value="{{ $line['item_id'] }}">
                                            <input type="hidden" name="lines[{{ $i }}][wip_balance]"
                                                value="{{ $wipBalance }}">

                                            {{-- DESKTOP LAYOUT --}}
                                            <td class="desktop-only">
                                                <span class="small text-muted">{{ $i + 1 }}</span>
                                            </td>

                                            <td class="desktop-only">
                                                <div class="d-flex flex-column gap-1">
                                                    @if ($itemCode)
                                                        <div class="item-code-pill">
                                                            {{ $itemCode }}
                                                        </div>
                                                        @if ($itemName && $itemName !== $itemCode)
                                                            <div class="item-name-sub text-truncate">
                                                                {{ $itemName }}
                                                            </div>
                                                        @endif
                                                    @else
                                                        <div class="fw-semibold text-truncate">
                                                            {{ $itemLabel }}
                                                        </div>
                                                    @endif

                                                    <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                                                        <span class="bundle-label mono">
                                                            BND: {{ $bundleModel?->bundle_code ?? '#' . ($i + 1) }}
                                                        </span>

                                                        @if ($bundleModel?->lot)
                                                            <span class="badge-lot mono">
                                                                LOT: {{ $bundleModel->lot->code }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>

                                            <td class="text-end desktop-only">
                                                <div class="qty-label-small mb-1">WIP-FIN</div>
                                                <span class="qty-pill-balance mono" data-role="wip-balance-display">
                                                    {{ number_format($wipBalance, 2, ',', '.') }}
                                                </span>
                                            </td>

                                            <td class="text-end desktop-only">
                                                <div class="qty-label-small mb-1">Masuk Finishing</div>
                                                <input type="number" step="0.01" min="0"
                                                    class="form-control form-control-sm text-end mono qty-in-input"
                                                    name="lines[{{ $i }}][qty_in]"
                                                    value="{{ old("lines.$i.qty_in", $defaultQtyIn) }}"
                                                    data-role="qty-in-input">
                                            </td>

                                            <td class="text-end desktop-only">
                                                <div class="qty-label-small mb-1">Reject</div>
                                                <input type="number" step="0.01" min="0"
                                                    class="form-control form-control-sm text-end mono qty-reject-input"
                                                    name="lines[{{ $i }}][qty_reject]"
                                                    value="{{ old("lines.$i.qty_reject", $line['qty_reject'] ?? 0) }}"
                                                    data-role="qty-reject-input">
                                            </td>

                                            <td class="text-end desktop-only">
                                                <div class="qty-label-small mb-1">Qty OK</div>
                                                <div class="qty-ok-text mono" data-role="qty-ok-display">
                                                    {{ number_format($qtyOk, 2, ',', '.') }}
                                                </div>
                                                <input type="hidden" name="lines[{{ $i }}][qty_ok]"
                                                    value="{{ $qtyOk }}" data-role="qty-ok-hidden">
                                            </td>

                                            {{-- Operator jahit (read-only) --}}
                                            <td class="desktop-only" style="min-width: 150px;">
                                                <div class="qty-label-small mb-1">Opr Jahit</div>
                                                @if ($operatorName)
                                                    <div class="badge-soft mono">
                                                        {{ $operatorName }}
                                                    </div>
                                                @else
                                                    <span class="text-muted small">-</span>
                                                @endif
                                            </td>

                                            {{-- MOBILE CARD VERSION --}}
                                            <td class="mobile-only" colspan="7">
                                                <div class="line-mobile-header">
                                                    <div class="line-mobile-header-left">
                                                        @if ($itemCode)
                                                            <div class="item-code-pill mb-1">
                                                                {{ $itemCode }}
                                                            </div>
                                                            @if ($itemName && $itemName !== $itemCode)
                                                                <div class="line-mobile-item text-truncate">
                                                                    {{ $itemName }}
                                                                </div>
                                                            @endif
                                                        @else
                                                            <div class="line-mobile-item text-truncate fw-semibold">
                                                                {{ $itemLabel }}
                                                            </div>
                                                        @endif

                                                        <div class="d-flex flex-wrap gap-1 align-items-center mt-1">
                                                            <span class="bundle-label mono">
                                                                BND: {{ $bundleModel?->bundle_code ?? '#' . ($i + 1) }}
                                                            </span>
                                                            @if ($bundleModel?->lot)
                                                                <span class="badge-lot mono">
                                                                    LOT: {{ $bundleModel->lot->code }}
                                                                </span>
                                                            @endif
                                                        </div>

                                                        @if ($operatorName)
                                                            <div class="mt-1">
                                                                <span class="badge-soft mono">
                                                                    Opr Jahit: {{ $operatorName }}
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="line-mobile-header-right">
                                                        <div class="line-mobile-qty-label">
                                                            WIP-FIN
                                                        </div>
                                                        <div class="line-mobile-qty mono" data-role="wip-balance-display">
                                                            {{ number_format($wipBalance, 2, ',', '.') }}
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mobile-inline-inputs">
                                                    <div>
                                                        <div class="qty-label-small mb-1">Masuk</div>
                                                        <input type="number" step="0.01" min="0"
                                                            class="form-control form-control-sm text-end mono qty-in-input"
                                                            name="lines[{{ $i }}][qty_in]"
                                                            value="{{ old("lines.$i.qty_in", $defaultQtyIn) }}"
                                                            data-role="qty-in-input">
                                                    </div>
                                                    <div>
                                                        <div class="qty-label-small mb-1">Reject</div>
                                                        <input type="number" step="0.01" min="0"
                                                            class="form-control form-control-sm text-end mono qty-reject-input"
                                                            name="lines[{{ $i }}][qty_reject]"
                                                            value="{{ old("lines.$i.qty_reject", $line['qty_reject'] ?? 0) }}"
                                                            data-role="qty-reject-input">
                                                    </div>
                                                    <div>
                                                        <div class="qty-label-small mb-1">OK</div>
                                                        <div class="qty-ok-text mono text-end" data-role="qty-ok-display">
                                                            {{ number_format($qtyOk, 2, ',', '.') }}
                                                        </div>
                                                        <input type="hidden" name="lines[{{ $i }}][qty_ok]"
                                                            value="{{ $qtyOk }}" data-role="qty-ok-hidden">
                                                    </div>
                                                </div>

                                                {{-- Tidak ada select operator di mobile --}}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- FOOTER ACTIONS --}}
            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="{{ route('production.finishing_jobs.index') }}"
                    class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
                    <i class="bi bi-arrow-left"></i>
                    <span>Kembali</span>
                </a>

                <button type="submit" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-2"
                    @if ($linesCount === 0) disabled @endif>
                    <i class="bi bi-check2-circle"></i>
                    <span>Simpan Finishing Job</span>
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.finishing-line-row');

            rows.forEach(row => {
                const qtyInInput = row.querySelector('[data-role="qty-in-input"]');
                const qtyRejectInput = row.querySelector('[data-role="qty-reject-input"]');
                const qtyOkDisplay = row.querySelector('[data-role="qty-ok-display"]');
                const qtyOkHidden = row.querySelector('[data-role="qty-ok-hidden"]');
                const wipBalanceEl = row.querySelector('[data-role="wip-balance-display"]');

                if (!qtyInInput || !qtyRejectInput || !qtyOkDisplay || !qtyOkHidden) {
                    return;
                }

                const wipBalance = wipBalanceEl ?
                    parseFloat((wipBalanceEl.textContent || '0').replace(/\./g, '').replace(',', '.')) ||
                    0 :
                    parseFloat(qtyInInput.value || '0') || 0;

                function recalc() {
                    let qtyIn = parseFloat(qtyInInput.value || '0');
                    let qtyReject = parseFloat(qtyRejectInput.value || '0');

                    if (qtyIn < 0) qtyIn = 0;
                    if (qtyReject < 0) qtyReject = 0;

                    if (qtyIn > wipBalance) qtyIn = wipBalance;
                    if (qtyReject > qtyIn) qtyReject = qtyIn;

                    qtyInInput.value = qtyIn.toFixed(2);
                    qtyRejectInput.value = qtyReject.toFixed(2);

                    const qtyOk = qtyIn - qtyReject;
                    qtyOkHidden.value = qtyOk.toFixed(2);

                    const formatted = qtyOk.toFixed(2).replace('.', ',');
                    qtyOkDisplay.textContent = formatted;
                }

                qtyInInput.addEventListener('input', recalc);
                qtyRejectInput.addEventListener('input', recalc);

                recalc();
            });
        });
    </script>
@endpush
