{{-- resources/views/production/sewing_returns/aging_wip_sew.blade.php --}}
@extends('layouts.app')

@section('title', 'Production • Aging WIP-SEW')

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

        .card-soft {
            background: color-mix(in srgb, var(--card) 80%, var(--line) 20%);
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
        }

        .muted {
            color: var(--muted);
            font-size: .85rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .filters-grid {
            display: grid;
            gap: .75rem;
        }

        @media (min-width: 768px) {
            .filters-grid {
                grid-template-columns: minmax(0, 2.2fr) repeat(2, minmax(0, 1.1fr)) auto;
                align-items: end;
            }
        }

        .badge-pill-soft {
            border-radius: 999px;
            padding: .18rem .6rem;
            font-size: .75rem;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            border-radius: 999px;
            padding: .1rem .55rem;
            font-size: .75rem;
            background: color-mix(in srgb, var(--card) 70%, var(--line) 30%);
            color: var(--muted);
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap py-3 py-md-4">

        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h1 class="h4 mb-1">Aging WIP-SEW</h1>
                <div class="muted">
                    Outstanding sewing WIP grouped by aging days since pickup date.
                </div>
            </div>

            {{-- 🔁 route disesuaikan ke production.reports.operators --}}
            <a href="{{ route('production.reports.operators') }}" class="btn btn-outline-secondary btn-sm">
                Back to operator dashboard
            </a>
        </div>

        {{-- Filters --}}
        <div class="card p-3 mb-3">
            <form method="GET" class="filters-grid">
                <div class="w-100">
                    <label class="form-label mb-1">Operator</label>
                    <select name="operator_id" class="form-select">
                        <option value="">All operators</option>
                        @foreach ($operators as $op)
                            <option value="{{ $op->id }}" @selected($operatorId == $op->id)>
                                {{ $op->code }} — {{ $op->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="w-100">
                    <label class="form-label mb-1">Pickup date from</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                </div>

                <div class="w-100">
                    <label class="form-label mb-1">Pickup date to</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        Apply
                    </button>

                    {{-- 🔁 route reset ke production.reports.aging_wip_sew --}}
                    <a href="{{ route('production.reports.aging_wip_sew') }}" class="btn btn-outline-secondary">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- Active filters --}}
        @if ($dateFrom || $dateTo || $operatorId)
            <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
                <span class="muted me-1">Active filters:</span>
                @if ($operatorId)
                    @php
                        $op = $operators->firstWhere('id', $operatorId);
                    @endphp
                    @if ($op)
                        <span class="chip">
                            Operator: {{ $op->code }} — {{ $op->name }}
                        </span>
                    @endif
                @endif
                @if ($dateFrom)
                    <span class="chip">
                        From: {{ id_date($dateFrom) }}
                    </span>
                @endif
                @if ($dateTo)
                    <span class="chip">
                        To: {{ id_date($dateTo) }}
                    </span>
                @endif
                <span class="chip">
                    As of: {{ id_date($today) }}
                </span>
            </div>
        @endif

        {{-- Summary cards --}}
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="card card-soft p-2">
                    <div class="muted mb-1">Total outstanding (pcs)</div>
                    <div class="mono fs-5">{{ number_format($totalOutstanding) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-soft p-2">
                    <div class="muted mb-1">0–3 days</div>
                    <div class="mono fs-5">{{ number_format($bucket0_3) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-soft p-2">
                    <div class="muted mb-1">4–7 days</div>
                    <div class="mono fs-5">{{ number_format($bucket4_7) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-soft p-2">
                    <div class="muted mb-1">8–14 days</div>
                    <div class="mono fs-5">{{ number_format($bucket8_14) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3 mt-2">
                <div class="card card-soft p-2">
                    <div class="muted mb-1">&gt; 14 days</div>
                    <div class="mono fs-5">{{ number_format($bucket15p) }}</div>
                </div>
            </div>
            @if ($unknownAging > 0)
                <div class="col-6 col-md-3 mt-2">
                    <div class="card card-soft p-2">
                        <div class="muted mb-1">Unknown aging</div>
                        <div class="mono fs-5">{{ number_format($unknownAging) }}</div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Table --}}
        <div class="card p-0">
            <div class="table-wrap">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Pickup</th>
                            <th>Operator</th>
                            <th>Item</th>
                            <th class="text-end">Picked</th>
                            <th class="text-end">Returned OK</th>
                            <th class="text-end">Returned reject</th>
                            <th class="text-end">Outstanding</th>
                            <th class="text-end">Aging (days)</th>
                            <th class="text-end">Bucket</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lines as $line)
                            @php
                                $aging = $line->aging_days;
                                if ($aging === null) {
                                    $bucketLabel = 'Unknown';
                                    $bucketClass = 'bg-secondary text-dark';
                                } elseif ($aging <= 3) {
                                    $bucketLabel = '0–3 d';
                                    $bucketClass = 'bg-success text-white';
                                } elseif ($aging <= 7) {
                                    $bucketLabel = '4–7 d';
                                    $bucketClass = 'bg-warning text-dark';
                                } elseif ($aging <= 14) {
                                    $bucketLabel = '8–14 d';
                                    $bucketClass = 'bg-warning text-dark';
                                } else {
                                    $bucketLabel = '>14 d';
                                    $bucketClass = 'bg-danger text-white';
                                }
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold mono">
                                        {{ $line->sewingPickup->code ?? '-' }}
                                    </div>
                                    <div class="muted">
                                        {{ id_date($line->sewingPickup->date ?? null) }}
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold">
                                        {{ $line->sewingPickup->operator->code ?? '-' }}
                                    </div>
                                    <div class="muted">
                                        {{ $line->sewingPickup->operator->name ?? '' }}
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold">
                                        {{ $line->finishedItem->sku ?? ($line->finishedItem->code ?? '') }}
                                    </div>
                                    <div class="muted">
                                        {{ $line->finishedItem->name ?? '' }}
                                    </div>
                                </td>
                                <td class="text-end mono">{{ number_format($line->picked) }}</td>
                                <td class="text-end mono text-success">{{ number_format($line->returned_ok) }}</td>
                                <td class="text-end mono text-danger">{{ number_format($line->returned_reject) }}</td>
                                <td class="text-end mono">
                                    <span class="badge-pill-soft bg-warning text-dark">
                                        {{ number_format($line->outstanding) }}
                                    </span>
                                </td>
                                <td class="text-end mono">
                                    {{ $aging === null ? '—' : $aging }}
                                </td>
                                <td class="text-end mono">
                                    <span class="badge-pill-soft {{ $bucketClass }}">
                                        {{ $bucketLabel }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-3 text-muted">
                                    No outstanding WIP-SEW for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-2 border-top muted small">
                Aging days = difference between today ({{ id_date($today) }}) and pickup date.
                Outstanding = Picked − Returned OK − Returned reject.
            </div>
        </div>

    </div>
@endsection
