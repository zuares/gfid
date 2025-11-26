{{-- resources/views/production/sewing_pickups/bundles_ready.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Bundles Ready to Pick')

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
            font-size: .85rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .bundle-row {
            cursor: pointer;
            transition: background-color .12s ease, box-shadow .12s ease;
        }

        .bundle-row:hover {
            background: color-mix(in srgb, var(--card) 80%, #0d6efd 4%);
            box-shadow: 0 0 0 1px rgba(148, 163, 184, 0.4);
        }

        .qty-badge {
            border-radius: 999px;
            padding: .08rem .6rem;
            font-size: .85rem;
            font-weight: 600;
            background: rgba(13, 110, 253, 0.08);
        }

        /* HEADER */
        .header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .header-main h1 {
            font-size: 1rem;
        }

        /* SUMMARY CARDS */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .5rem;
        }

        .summary-card {
            padding: .6rem .7rem;
            border-radius: 10px;
            border: 1px dashed var(--line);
            font-size: .8rem;
        }

        .summary-label {
            color: var(--muted);
            margin-bottom: .2rem;
        }

        .summary-value {
            font-size: .95rem;
            font-weight: 600;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .5rem;
            }

            .card {
                border-radius: 12px;
            }

            .header-row {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-new-pickup {
                width: 100%;
                justify-content: center;
                border-radius: 999px;
                padding-block: .45rem;
                font-size: .82rem;
            }

            .table.table-sm> :not(caption)>*>* {
                padding-block: .35rem;
            }

            .col-bundle-code,
            .col-lot {
                display: none !important;
            }

            .item-name {
                font-size: .78rem;
                color: var(--muted);
            }

            .qty-badge {
                font-size: .95rem;
                padding: .12rem .8rem;
            }

            .pick-cell a {
                padding-block: .25rem;
                padding-inline: .6rem;
                font-size: .8rem;
                border-radius: 999px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap py-3 py-md-4">
        @php
            // Hitung hanya bundle yang qty_ready > 0
            $totalBundle = $bundles
                ->filter(function ($b) {
                    $qc = $b->qcResults->where('stage', 'cutting')->sortByDesc('qc_date')->first();

                    $qtyOk = (float) ($b->qty_cutting_ok ?? ($qc?->qty_ok ?? $b->qty_pcs));
                    $qtyReady = (float) ($b->qty_remaining_for_sewing ?? $qtyOk);

                    return $qtyReady > 0;
                })
                ->count();

            $totalReady = $bundles->sum(function ($b) {
                $qc = $b->qcResults->where('stage', 'cutting')->sortByDesc('qc_date')->first();

                $qtyOk = (float) ($b->qty_cutting_ok ?? ($qc?->qty_ok ?? $b->qty_pcs));
                $qtyReady = (float) ($b->qty_remaining_for_sewing ?? $qtyOk);

                return $qtyReady > 0 ? $qtyReady : 0;
            });
        @endphp

        {{-- HEADER + SUMMARY --}}
        <div class="card p-3 mb-3">
            <div class="header-row mb-2">
                <div class="header-main">
                    <h1 class="h5 mb-1">Bundles Ready to Pick</h1>
                    <div class="help">
                        Hanya bundle yang masih punya Qty Ready &gt; 0.
                    </div>
                </div>

                <a href="{{ route('production.sewing_pickups.create') }}"
                    class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1 btn-new-pickup">
                    + Sewing Pickup
                </a>
            </div>

            <div class="summary-grid">
                <div class="summary-card">
                    <div class="summary-label">Total Bundle Ready</div>
                    <div class="summary-value mono">
                        {{ number_format($totalBundle, 0, ',', '.') }} bundle
                    </div>
                </div>
                <div class="summary-card text-end">
                    <div class="summary-label">Total Qty Ready (pcs)</div>
                    <div class="summary-value mono">
                        {{ number_format($totalReady, 2, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- LIST BUNDLE --}}
        <div class="card p-3">
            <h2 class="h6 mb-2">Daftar Bundle</h2>

            <div class="table-wrap">
                <table class="table table-sm align-middle mono mb-0">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th class="col-bundle-code">Bundle</th>
                            <th>Item Jadi</th>
                            <th class="col-lot">Lot</th>
                            <th class="text-end">Qty Ready</th>
                            <th style="width: 80px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $rowNumber = 0; @endphp

                        @foreach ($bundles as $b)
                            @php
                                $qc = $b->qcResults->where('stage', 'cutting')->sortByDesc('qc_date')->first();

                                $qtyOk = (float) ($b->qty_cutting_ok ?? ($qc?->qty_ok ?? $b->qty_pcs));
                                $qtyReady = (float) ($b->qty_remaining_for_sewing ?? $qtyOk);

                                if ($qtyReady <= 0) {
                                    continue;
                                }

                                $rowNumber++;
                                $pickUrl = route('production.sewing_pickups.create', ['bundle_id' => $b->id]);
                            @endphp

                            <tr class="bundle-row" data-href="{{ $pickUrl }}">
                                <td>{{ $rowNumber }}</td>

                                <td class="col-bundle-code">
                                    {{ $b->bundle_code }}
                                </td>

                                <td>
                                    <div>
                                        <strong>{{ $b->finishedItem?->code ?? '—' }}</strong>
                                        @if ($b->finishedItem?->name)
                                            <div class="item-name">
                                                {{ $b->finishedItem->name }}
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <td class="col-lot">
                                    @if ($b->cuttingJob?->lot)
                                        {{ $b->cuttingJob->lot->item?->code }}
                                        <span class="badge bg-light text-muted border ms-1">
                                            {{ $b->cuttingJob->lot->code }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    <span class="qty-badge">
                                        {{ number_format($qtyReady, 2, ',', '.') }}
                                    </span>
                                </td>

                                <td class="pick-cell text-end">
                                    <a href="{{ $pickUrl }}" class="btn btn-sm btn-outline-primary"
                                        onclick="event.stopPropagation();">
                                        Pick
                                    </a>
                                </td>
                            </tr>
                        @endforeach

                        @if ($rowNumber === 0)
                            <tr>
                                <td colspan="6" class="text-center text-muted small py-3">
                                    Tidak ada bundle dengan Qty Ready &gt; 0.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.bundle-row').forEach(function(row) {
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
