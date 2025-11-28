{{-- resources/views/production/reports/sewing/lead_time.blade.php --}}
@extends('layouts.app')

@section('title', 'Production • Lead Time Sewing')

@push('head')
    <style>
        .page-wrap {
            max-width: 1120px;
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

        .muted {
            color: var(--muted);
            font-size: .85rem;
        }

        .small-muted {
            color: var(--muted);
            font-size: .75rem;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
        }

        .filters-grid {
            display: grid;
            gap: .75rem;
        }

        @media (min-width: 768px) {
            .filters-grid {
                grid-template-columns: minmax(0, 2.3fr) repeat(2, minmax(0, 1.2fr)) auto;
                align-items: end;
            }
        }

        .table-wrap {
            overflow-x: auto;
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

        .badge-pill-soft {
            border-radius: 999px;
            padding: .18rem .6rem;
            font-size: .75rem;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap py-3 py-md-4">

        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h1 class="h4 mb-1">Lead Time Sewing</h1>
                <div class="muted">
                    Time duration from <strong>Pickup</strong> to <strong>Return</strong>, per operator and per transaction.
                </div>
            </div>

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

                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        Apply
                    </button>
                    <a href="{{ route('production.reports.lead_time') }}" class="btn btn-outline-secondary">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- Active Filters --}}
        @if ($dateFrom || $dateTo || $operatorId)
            <div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
                <span class="muted me-1">Active filters:</span>
                @if ($operatorId)
                    @php $op = $operators->firstWhere('id', $operatorId); @endphp
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
                    <div class="muted mb-1">Avg lead time (days)</div>
                    <div class="mono fs-5">
                        {{ $overallAvgLead !== null ? number_format($overallAvgLead, 2) : '—' }}
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card card-soft p-2">
                    <div class="muted mb-1">Total returns</div>
                    <div class="mono fs-5">{{ number_format($totalReturns) }}</div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="card card-soft p-2">
                    <div class="muted mb-1">Total OK qty</div>
                    <div class="mono fs-5">{{ number_format($totalQtyOk) }}</div>
                </div>
            </div>

            @if ($byOperator->count())
                @php
                    $fastest = $byOperator->first();
                    $slowest = $byOperator->sortByDesc('avg_lead_days')->first();
                @endphp
                <div class="col-6 col-md-3">
                    <div class="card card-soft p-2">
                        <div class="muted mb-1">Fastest operator</div>
                        <div class="mono">
                            {{ $fastest->operator_code ?? '-' }}
                        </div>
                        <div class="muted">
                            {{ $fastest->operator_name ?? '' }} — {{ number_format($fastest->avg_lead_days, 2) }} d
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mt-2">
                    <div class="card card-soft p-2">
                        <div class="muted mb-1">Slowest operator</div>
                        <div class="mono">
                            {{ $slowest->operator_code ?? '-' }}
                        </div>
                        <div class="muted">
                            {{ $slowest->operator_name ?? '' }} — {{ number_format($slowest->avg_lead_days, 2) }} d
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- 🔹 Mini Chart: Avg Lead Time per Operator --}}
        @if ($byOperator->count())
            <div class="card p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div class="fw-semibold">
                        Lead time per operator (avg days)
                    </div>
                    <div class="small-muted">
                        Lower is better (faster turnaround)
                    </div>
                </div>
                <div style="height: 260px;">
                    <canvas id="leadTimeChart"></canvas>
                </div>
            </div>
        @endif

        {{-- Table: Summary per operator --}}
        <div class="card p-0 mb-3">
            <div class="p-2 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="fw-semibold">
                        Lead time summary per operator
                    </div>
                    <div class="muted small">
                        Based on selected filters
                    </div>
                </div>
            </div>

            <div class="table-wrap">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Operator</th>
                            <th class="text-end">Avg (days)</th>
                            <th class="text-end">Min</th>
                            <th class="text-end">Max</th>
                            <th class="text-end">Returns</th>
                            <th class="text-end">OK qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($byOperator as $op)
                            <tr>
                                <td>
                                    <div class="fw-semibold">
                                        {{ $op->operator_code }}
                                    </div>
                                    <div class="muted">
                                        {{ $op->operator_name }}
                                    </div>
                                </td>
                                <td class="text-end mono">
                                    {{ $op->avg_lead_days !== null ? number_format($op->avg_lead_days, 2) : '—' }}
                                </td>
                                <td class="text-end mono">
                                    {{ $op->min_lead_days !== null ? number_format($op->min_lead_days, 0) : '—' }}
                                </td>
                                <td class="text-end mono">
                                    {{ $op->max_lead_days !== null ? number_format($op->max_lead_days, 0) : '—' }}
                                </td>
                                <td class="text-end mono">
                                    {{ number_format($op->count_returns) }}
                                </td>
                                <td class="text-end mono">
                                    {{ number_format($op->total_qty_ok) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-3 text-muted">
                                    No data for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Table: Detail per transaction --}}
        <div class="card p-0">
            <div class="p-2 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="fw-semibold">
                        Transaction details
                    </div>
                    <div class="muted small">
                        Each row = 1 sewing return line
                    </div>
                </div>
            </div>

            <div class="table-wrap">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Pickup</th>
                            <th>Return</th>
                            <th>Operator</th>
                            <th>Item</th>
                            <th class="text-end">OK</th>
                            <th class="text-end">Reject</th>
                            <th class="text-end">Lead time (days)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>
                                    <div class="fw-semibold mono">
                                        {{ $row->pickup_code }}
                                    </div>
                                    <div class="muted">
                                        {{ id_date($row->pickup_date) }}
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold mono">
                                        {{ $row->return_code }}
                                    </div>
                                    <div class="muted">
                                        {{ id_date($row->return_date) }}
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold">
                                        {{ $row->operator_code }}
                                    </div>
                                    <div class="muted">
                                        {{ $row->operator_name }}
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold">
                                        {{ $row->item_code }}
                                    </div>
                                    <div class="muted">
                                        {{ $row->item_name }}
                                    </div>
                                </td>
                                <td class="text-end mono text-success">
                                    {{ number_format($row->qty_ok) }}
                                </td>
                                <td class="text-end mono text-danger">
                                    {{ number_format($row->qty_reject) }}
                                </td>
                                <td class="text-end mono">
                                    @if ($row->lead_time_days === null)
                                        —
                                    @else
                                        <span class="badge-pill-soft bg-light">
                                            {{ $row->lead_time_days }} d
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-3 text-muted">
                                    No data available.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-2 border-top muted small">
                Lead time (days) = difference between pickup date and return date.
                0 days means same-day pickup & return.
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    {{-- Chart.js CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('leadTimeChart');
            if (!canvas) return;

            const labels = @json($byOperator->pluck('operator_code'));
            const values = @json($byOperator->pluck('avg_lead_days')->map(fn($v) => round($v, 2)));

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Avg lead time (days)',
                        data: values,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(ctx) {
                                    return ctx.parsed.y + ' days';
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endpush
