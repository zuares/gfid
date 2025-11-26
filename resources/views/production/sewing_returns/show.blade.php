@extends('layouts.app')

@section('title', 'Produksi • Sewing Return ' . $return->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .help {
            color: var(--muted);
            font-size: .82rem;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .15rem .55rem;
            font-size: .7rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border-radius: 999px;
            padding: .18rem .8rem;
            font-size: .8rem;
            background: rgba(15, 23, 42, 0.03);
        }

        .pill-label {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
        }

        .pill-value {
            font-weight: 600;
        }

        .stat-label {
            font-size: .76rem;
            color: var(--muted);
        }

        .stat-value {
            font-size: .95rem;
            font-weight: 600;
        }

        .table-sewing-return th {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: var(--muted);
            border-top: none;
        }

        .cell-label {
            display: none;
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
        }

        .chip-status {
            border-radius: 999px;
            padding: .15rem .8rem;
            font-size: .75rem;
        }

        .header-code {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .header-meta {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem .75rem;
            margin-top: .4rem;
            font-size: .78rem;
            color: var(--muted);
        }

        .meta-dot::before {
            content: '•';
            margin: 0 .4rem;
            color: var(--muted);
        }

        /* ============ MOBILE ============ */
        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .75rem;
                padding-bottom: 5rem;
            }

            .card {
                border-radius: 14px;
            }

            .header-main {
                flex-direction: column;
                align-items: stretch;
                gap: .75rem;
            }

            .header-actions {
                align-items: stretch !important;
                width: 100%;
            }

            .header-actions .btn {
                width: 100%;
                justify-content: center;
            }

            .pill-group {
                flex-direction: column;
                align-items: stretch;
                gap: .4rem;
            }

            .pill {
                justify-content: space-between;
            }

            .table-sewing-return {
                border-collapse: separate;
                border-spacing: 0 .55rem;
            }

            .table-sewing-return thead {
                display: none;
            }

            .table-sewing-return tbody tr {
                display: block;
                border-radius: 12px;
                border: 1px solid var(--line);
                padding: .55rem .7rem .6rem;
                background: var(--card);
                margin-bottom: .4rem;
            }

            .table-sewing-return tbody tr:last-child {
                margin-bottom: 0;
            }

            .table-sewing-return td {
                display: block;
                border: none !important;
                padding: .06rem 0;
                font-size: .8rem;
            }

            .cell-label {
                display: block;
            }

            .cell-main {
                font-size: .86rem;
                font-weight: 600;
            }

            .cell-sub {
                font-size: .78rem;
                color: var(--muted);
            }

            .cell-top-row {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: .5rem;
                margin-bottom: .15rem;
            }

            .cell-qty-badges {
                display: flex;
                flex-direction: column;
                align-items: flex-end;
                gap: .12rem;
                font-size: .76rem;
            }

            .qty-tag {
                border-radius: 999px;
                padding: .08rem .6rem;
                font-size: .76rem;
                background: rgba(15, 23, 42, 0.03);
            }

            .qty-tag-ok {
                border: 1px solid rgba(16, 185, 129, 0.4);
            }

            .qty-tag-reject {
                border: 1px solid rgba(239, 68, 68, 0.4);
                color: #dc2626;
            }

            .cell-notes {
                margin-top: .25rem;
                font-size: .76rem;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $totalLines = $return->lines->count();
        $totalOk = $return->lines->sum('qty_ok');
        $totalReject = $return->lines->sum('qty_reject');

        $statusMap = [
            'draft' => ['label' => 'DRAFT', 'class' => 'secondary'],
            'posted' => ['label' => 'POSTED', 'class' => 'primary'],
            'closed' => ['label' => 'CLOSED', 'class' => 'success'],
        ];

        $cfg = $statusMap[$return->status] ?? [
            'label' => strtoupper($return->status ?? '-'),
            'class' => 'secondary',
        ];

        $pickup = $return->pickup;
    @endphp

    <div class="page-wrap py-3 py-md-4">

        {{-- HEADER --}}
        <div class="card p-3 p-md-4 mb-3">
            <div class="d-flex justify-content-between align-items-start gap-3 header-main">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <div class="header-code mono">
                            {{ $return->code }}
                        </div>
                        <span class="chip-status bg-{{ $cfg['class'] }} text-light">
                            {{ $cfg['label'] }}
                        </span>
                    </div>

                    <div class="header-meta">
                        <span>
                            <span class="text-muted">Tanggal</span>
                            <span class="mono">{{ $return->date?->format('Y-m-d') ?? $return->date }}</span>
                        </span>

                        <span class="meta-dot">
                            <span class="text-muted">Gudang</span>
                            <span class="mono">
                                {{ $return->warehouse?->code ?? '-' }}
                            </span>
                            <span class="text-muted">
                                — {{ $return->warehouse?->name ?? '-' }}
                            </span>
                        </span>

                        <span class="meta-dot">
                            <span class="text-muted">Operator</span>
                            @if ($return->operator)
                                <span class="mono">
                                    {{ $return->operator->code }} — {{ $return->operator->name }}
                                </span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </span>

                        @if ($pickup)
                            <span class="meta-dot">
                                <span class="text-muted">Pickup</span>
                                <a href="{{ route('production.sewing_pickups.show', $pickup) }}" class="link-primary mono">
                                    {{ $pickup->code }}
                                </a>
                            </span>
                        @endif
                    </div>

                    @if ($return->notes)
                        <div class="mt-2 small text-muted">
                            {{ $return->notes }}
                        </div>
                    @endif
                </div>

                <div class="d-flex flex-column align-items-end gap-2 header-actions">
                    <a href="{{ route('production.sewing_returns.index') }}"
                        class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
                        <i class="bi bi-arrow-left"></i>
                        <span>Daftar Sewing Return</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- RINGKASAN --}}
        <div class="card p-3 p-md-4 mb-3">
            <div class="d-flex flex-wrap gap-2 mb-3 pill-group">
                <div class="pill">
                    <span class="pill-label">Baris</span>
                    <span class="pill-value mono">{{ $totalLines }}</span>
                </div>
                <div class="pill">
                    <span class="pill-label">Total OK</span>
                    <span class="pill-value mono">{{ number_format($totalOk, 2, ',', '.') }} pcs</span>
                </div>
                <div class="pill">
                    <span class="pill-label">Total Reject</span>
                    <span class="pill-value mono text-danger">
                        {{ number_format($totalReject, 2, ',', '.') }} pcs
                    </span>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="stat-label mb-1">Gudang OK</div>
                    <div class="stat-value mono">WIP-FIN</div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="stat-label mb-1">Status Reject</div>
                    <div class="stat-value mono text-danger">
                        {{ $totalReject > 0 ? 'Masuk laporan loss / reject' : 'Tidak ada reject' }}
                    </div>
                </div>
            </div>
        </div>

        {{-- DETAIL BUNDLES --}}
        <div class="card p-3 p-md-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 mb-0">Detail Bundles</h2>
                <div class="help mb-0 d-none d-md-block">
                    Rekap per bundle hasil jahit.
                </div>
            </div>

            <div class="table-wrap">
                <table class="table table-sm align-middle mono table-sewing-return mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="width: 170px;">Bundle / Pickup</th>
                            <th style="width: 180px;">Item Jadi</th>
                            <th style="width: 180px;">Lot</th>
                            <th style="width: 110px;" class="text-end">Pickup</th>
                            <th style="width: 110px;" class="text-end">OK</th>
                            <th style="width: 110px;" class="text-end">Reject</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($return->lines as $line)
                            @php
                                $pickupLine = $line->sewingPickupLine;
                                $pickupRow = $pickupLine?->sewingPickup;
                                $bundle = $pickupLine?->bundle;
                                $lot = $bundle?->cuttingJob?->lot;
                            @endphp
                            <tr>
                                {{-- DESKTOP index --}}
                                <td class="d-none d-md-table-cell">
                                    {{ $loop->iteration }}
                                </td>

                                {{-- BUNDLE + PICKUP --}}
                                <td>
                                    <div class="cell-label d-md-none">Bundle / Pickup</div>

                                    <div class="cell-top-row d-md-none">
                                        <div>
                                            <div class="cell-main">
                                                {{ $bundle?->bundle_code ?? '-' }}
                                            </div>
                                            <div class="cell-sub">
                                                @if ($pickupRow)
                                                    Pickup:
                                                    <a href="{{ route('production.sewing_pickups.show', $pickupRow) }}"
                                                        class="link-primary">
                                                        {{ $pickupRow->code }}
                                                    </a>
                                                @else
                                                    Pickup: -
                                                @endif
                                            </div>
                                        </div>
                                        <div class="cell-qty-badges">
                                            <span class="qty-tag qty-tag-ok">
                                                OK:
                                                {{ number_format($line->qty_ok ?? 0, 2, ',', '.') }}
                                            </span>
                                            <span class="qty-tag qty-tag-reject">
                                                RJ:
                                                {{ number_format($line->qty_reject ?? 0, 2, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="d-none d-md-block">
                                        <div class="fw-semibold">
                                            {{ $bundle?->bundle_code ?? '-' }}
                                        </div>
                                        <div class="small text-muted">
                                            @if ($pickupRow)
                                                Pickup:
                                                <a href="{{ route('production.sewing_pickups.show', $pickupRow) }}"
                                                    class="link-primary">
                                                    {{ $pickupRow->code }}
                                                </a>
                                            @else
                                                Pickup: -
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- ITEM JADI --}}
                                <td>
                                    <div class="cell-label d-md-none">Item Jadi</div>
                                    <div class="cell-main">
                                        {{ $bundle?->finishedItem?->code ?? '-' }}
                                    </div>
                                    @if ($bundle?->finishedItem?->name)
                                        <div class="cell-sub">
                                            {{ $bundle->finishedItem->name }}
                                        </div>
                                    @endif
                                </td>

                                {{-- LOT --}}
                                <td>
                                    <div class="cell-label d-md-none">Lot</div>
                                    @if ($lot)
                                        <div class="cell-main">
                                            {{ $lot->item?->code ?? '-' }}
                                        </div>
                                        <div class="cell-sub">
                                            <span class="badge-soft bg-light border text-muted">
                                                {{ $lot->code }}
                                            </span>
                                        </div>
                                    @else
                                        <div class="cell-main">-</div>
                                    @endif
                                </td>

                                {{-- DESKTOP QTY --}}
                                <td class="text-end d-none d-md-table-cell">
                                    {{ number_format($pickupLine->qty_bundle ?? 0, 2, ',', '.') }}
                                </td>
                                <td class="text-end d-none d-md-table-cell">
                                    {{ number_format($line->qty_ok ?? 0, 2, ',', '.') }}
                                </td>
                                <td class="text-end d-none d-md-table-cell text-danger">
                                    {{ number_format($line->qty_reject ?? 0, 2, ',', '.') }}
                                </td>
                                <td class="small d-none d-md-table-cell">
                                    {{ $line->notes ?? '-' }}
                                </td>

                                {{-- MOBILE: notes --}}
                                <td class="d-md-none">
                                    <div class="cell-notes text-muted">
                                        @if ($line->notes)
                                            {{ $line->notes }}
                                        @else
                                            <span class="fst-italic">Tanpa catatan</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted small py-3">
                                    Belum ada detail Sewing Return.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
