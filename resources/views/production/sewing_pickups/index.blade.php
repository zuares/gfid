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
            font-size: .82rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .header-main {
            min-width: 0;
        }

        .header-actions {
            display: flex;
            gap: .4rem;
        }

        /* Mini summary */
        .summary-row {
            display: flex;
            flex-wrap: wrap;
            gap: .35rem;
            margin-top: .3rem;
        }

        .summary-pill {
            border-radius: 999px;
            padding: .12rem .6rem;
            font-size: .78rem;
            background: rgba(148, 163, 184, 0.14);
            color: var(--muted);
        }

        .summary-pill-accent {
            background: rgba(13, 110, 253, 0.12);
            color: #0d6efd;
        }

        .pickup-row {
            cursor: pointer;
            transition: background-color .12s ease, box-shadow .12s ease;
        }

        .pickup-row:hover {
            background: color-mix(in srgb, var(--card) 82%, #0d6efd 6%);
            box-shadow: 0 0 0 1px rgba(148, 163, 184, 0.45);
        }

        /* ================= MOBILE ================= */
        @media (max-width: 767.98px) {
            .card {
                border-radius: 12px;
            }

            .page-wrap {
                padding-bottom: 4.5rem;
            }

            .table-pickups {
                border-collapse: separate;
                border-spacing: 0 6px;
            }

            .table-pickups thead {
                display: none;
            }

            .table-pickups tbody tr {
                display: block;
                background: var(--card);
                border-radius: 10px;
                border: 1px solid var(--line);
                padding: .45rem .55rem;
                margin-bottom: .35rem;
            }

            .table-pickups tbody tr:last-child {
                margin-bottom: 0;
            }

            .table-pickups td {
                border: none !important;
                padding: .14rem 0 !important;
            }

            .td-desktop-only {
                display: none !important;
            }

            .td-mobile-card {
                display: block;
            }

            .mobile-top {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: .5rem;
                margin-bottom: .2rem;
            }

            .mobile-code {
                font-weight: 700;
                font-size: .9rem;
            }

            .mobile-date {
                font-size: .75rem;
                color: var(--muted);
            }

            .mobile-status-badge {
                font-size: .7rem;
                padding: .08rem .45rem;
            }

            .mobile-middle {
                font-size: .78rem;
                color: var(--muted);
                margin-bottom: .15rem;
            }

            .mobile-middle span.mono {
                font-size: .8rem;
            }

            .mobile-bottom {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: .1rem;
                font-size: .8rem;
            }

            .btn-detail-mobile {
                padding-block: .2rem;
                padding-inline: .6rem;
                font-size: .78rem;
                border-radius: 999px;
            }

            .header-row {
                flex-direction: column;
                align-items: stretch;
            }

            .header-actions {
                width: 100%;
            }

            .header-actions a {
                flex: 1;
                justify-content: center;
            }
        }

        /* ================ DESKTOP ================ */
        @media (min-width: 768px) {
            .td-mobile-card {
                display: none !important;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap py-3 py-md-4">

        @php
            // mini summary (berdasarkan data di halaman ini)
            $totalBundlesPage = 0;
            $totalQtyPage = 0;
            $todayPickups = 0;
            $todayDate = now()->toDateString();

            foreach ($pickups as $p) {
                $totalBundlesPage += $p->lines->count();
                $totalQtyPage += $p->lines->sum('qty_bundle');
                if (optional($p->date)?->format('Y-m-d') === $todayDate) {
                    $todayPickups++;
                }
            }

            $totalPickups =
                $pickups instanceof \Illuminate\Pagination\AbstractPaginator ? $pickups->total() : $pickups->count();
        @endphp

        {{-- HEADER --}}
        <div class="card p-3 mb-3">
            <div class="header-row">
                <div class="header-main">
                    <h1 class="h5 mb-0">Sewing Pickup</h1>

                    @if ($totalPickups > 0)
                        <div class="summary-row">
                            <span class="summary-pill mono">
                                {{ number_format($totalPickups, 0, ',', '.') }} pickup
                            </span>
                            <span class="summary-pill mono">
                                {{ number_format($totalBundlesPage, 0, ',', '.') }} bundle
                            </span>
                            <span class="summary-pill mono">
                                {{ number_format($totalQtyPage, 2, ',', '.') }} pcs
                            </span>
                            @if ($todayPickups > 0)
                                <span class="summary-pill summary-pill-accent mono">
                                    {{ number_format($todayPickups, 0, ',', '.') }} hari ini
                                </span>
                            @endif
                        </div>
                    @else
                        <div class="help mt-1">
                            Belum ada sewing pickup tercatat.
                        </div>
                    @endif
                </div>

                <div class="header-actions">
                    <a href="{{ route('production.sewing_pickups.bundles_ready') }}"
                        class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center">
                        Bundles Ready
                    </a>
                    <a href="{{ route('production.sewing_pickups.create') }}"
                        class="btn btn-sm btn-primary d-inline-flex align-items-center justify-content-center">
                        + Sewing Pickup
                    </a>
                </div>
            </div>
        </div>

        {{-- TABLE / MOBILE CARDS --}}
        <div class="card p-3">
            <h2 class="h6 mb-2">Daftar Sewing Pickup</h2>

            <div class="table-wrap">
                <table class="table table-sm align-middle mono table-pickups mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="width: 130px;">Code</th>
                            <th style="width: 100px;">Tanggal</th>
                            <th style="width: 170px;">Operator</th>
                            <th style="width: 150px;">Bundle / Qty</th>
                            <th style="width: 110px;">Status</th>
                            <th style="width: 90px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pickups as $pickup)
                            @php
                                $totalBundlesPickup = $pickup->lines->count();
                                $totalQtyPickup = $pickup->lines->sum('qty_bundle');

                                $statusMap = [
                                    'draft' => ['label' => 'DRAFT', 'class' => 'secondary'],
                                    'posted' => ['label' => 'POSTED', 'class' => 'primary'],
                                    'closed' => ['label' => 'CLOSED', 'class' => 'success'],
                                ];

                                $cfg = $statusMap[$pickup->status] ?? [
                                    'label' => strtoupper($pickup->status ?? '-'),
                                    'class' => 'secondary',
                                ];

                                $showUrl = Route::has('production.sewing_pickups.show')
                                    ? route('production.sewing_pickups.show', $pickup)
                                    : null;
                            @endphp
                            <tr class="pickup-row" @if ($showUrl) data-href="{{ $showUrl }}" @endif>
                                {{-- DESKTOP CELLS --}}
                                <td class="td-desktop-only">
                                    {{ $loop->iteration + ($pickups->currentPage() - 1) * $pickups->perPage() }}
                                </td>
                                <td class="td-desktop-only">
                                    {{ $pickup->code }}
                                </td>
                                <td class="td-desktop-only">
                                    {{ $pickup->date?->format('Y-m-d') ?? $pickup->date }}
                                </td>
                                <td class="td-desktop-only">
                                    @if ($pickup->operator)
                                        {{ $pickup->operator->code }} — {{ $pickup->operator->name }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="td-desktop-only">
                                    {{ $totalBundlesPickup }} bundle /
                                    {{ number_format($totalQtyPickup, 2, ',', '.') }} pcs
                                </td>
                                <td class="td-desktop-only">
                                    <span class="badge bg-{{ $cfg['class'] }}">
                                        {{ $cfg['label'] }}
                                    </span>
                                </td>
                                <td class="td-desktop-only text-end">
                                    @if ($showUrl)
                                        <a href="{{ $showUrl }}" class="btn btn-sm btn-outline-primary">
                                            Detail
                                        </a>
                                    @endif
                                </td>

                                {{-- MOBILE CARD VIEW --}}
                                <td class="td-mobile-card" colspan="7">
                                    <div class="mobile-top">
                                        <div>
                                            <div class="mobile-code">
                                                {{ $pickup->code }}
                                            </div>
                                            <div class="mobile-date">
                                                {{ $pickup->date?->format('Y-m-d') ?? $pickup->date }}
                                            </div>
                                        </div>
                                        <div>
                                            <span
                                                class="badge mobile-status-badge bg-{{ $cfg['class'] }}">{{ $cfg['label'] }}</span>
                                        </div>
                                    </div>

                                    <div class="mobile-middle">
                                        <div>
                                            @if ($pickup->operator)
                                                <span class="mono">{{ $pickup->operator->code }}</span>
                                                <span>— {{ $pickup->operator->name }}</span>
                                            @else
                                                <span class="text-muted">Operator: -</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mobile-bottom">
                                        <div>
                                            <span class="mono">
                                                {{ $totalBundlesPickup }} bundle /
                                                {{ number_format($totalQtyPickup, 2, ',', '.') }} pcs
                                            </span>
                                        </div>

                                        @if ($showUrl)
                                            <a href="{{ $showUrl }}"
                                                class="btn btn-sm btn-outline-primary btn-detail-mobile"
                                                onclick="event.stopPropagation();">
                                                Detail
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted small">
                                    Belum ada Sewing Pickup.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($pickups instanceof \Illuminate\Pagination\AbstractPaginator)
                <div class="mt-2">
                    {{ $pickups->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.pickup-row[data-href]').forEach(function(row) {
                row.addEventListener('click', function(e) {
                    if (e.target.closest('a, button')) {
                        return;
                    }
                    const url = row.dataset.href;
                    if (url) {
                        window.location.href = url;
                    }
                });
            });
        });
    </script>
@endpush
