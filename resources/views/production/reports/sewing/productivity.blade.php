{{-- resources/views/production/sewing_returns/productivity.blade.php --}}
@extends('layouts.app')

@section('title', 'Production • Sewing Productivity')

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

        .muted {
            color: var(--muted);
            font-size: .85rem;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
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
                grid-template-columns: minmax(0, 1.8fr) minmax(0, 1.2fr) minmax(0, 1.2fr) minmax(0, 1.1fr) auto;
                align-items: end;
            }
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

        .trend-up {
            color: #16a34a;
        }

        .trend-down {
            color: #dc2626;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap py-3 py-md-4">

        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h1 class="h4 mb-1">Sewing Productivity per Operator</h1>
                <div class="muted">
                    Summary of OK & reject quantities per sewing operator with average {{ $period }} output.
                </div>
            </div>

            <div class="d-flex flex-column text-end small text-muted">
                <span>Period mode:
                    <span class="fw-semibold text-capitalize">{{ $period }}</span>
                </span>
            </div>
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
                    <label class="form-label mb-1">Date from</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                </div>

                <div class="w-100">
                    <label class="form-label mb-1">Date to</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                </div>

                <div class="w-100">
                    <label class="form-label mb-1">Period mode</label>
                    <select name="period" class="form-select">
                        <option value="daily" @selected($period === 'daily')>Daily</option>
                        <option value="weekly" @selected($period === 'weekly')>Weekly</option>
                        <option value="monthly" @selected($period === 'monthly')>Monthly</option>
                    </select>
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        Apply
                    </button>
                    {{-- 🔁 route reset ke production.reports.productivity --}}
                    <a href="{{ route('production.reports.productivity') }}" class="btn btn-outline-secondary">
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
                        $opFilter = $operators->firstWhere('id', $operatorId);
                    @endphp
                    @if ($opFilter)
                        <span class="chip">
                            Operator: {{ $opFilter->code }} — {{ $opFilter->name }}
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
                    Mode: {{ ucfirst($period) }}
                </span>
            </div>
        @endif

        {{-- Summary cards --}}
        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="card card-soft p-2">
                    <div class="muted mb-1">Total OK (pcs)</div>
                    <div class="mono fs-5">{{ number_format($grandTotalOk) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-soft p-2">
                    <div class="muted mb-1">Total reject (pcs)</div>
                    <div class="mono fs-5 text-danger">{{ number_format($grandTotalReject) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3 mt-2 mt-md-0">
                <div class="card card-soft p-2">
                    <div class="muted mb-1">Grand total (OK + reject)</div>
                    <div class="mono fs-5">{{ number_format($grandTotalAll) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3 mt-2 mt-md-0">
                <div class="card card-soft p-2">
                    <div class="muted mb-1">Overall efficiency</div>
                    <div class="mono fs-5">
                        @if (!is_null($grandEfficiency))
                            {{ number_format($grandEfficiency, 1) }}%
                        @else
                            —
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="card p-0">
            <div class="table-wrap">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Operator</th>
                            <th class="text-end">Total OK</th>
                            <th class="text-end">Total reject</th>
                            <th class="text-end">Total (OK+R)</th>
                            <th class="text-end">Efficiency</th>
                            <th class="text-end">
                                Avg per {{ $period === 'daily' ? 'day' : ($period === 'weekly' ? 'week' : 'month') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>
                                    <div class="fw-semibold">
                                        {{ $row->operator_code }}
                                    </div>
                                    <div class="muted">
                                        {{ $row->operator_name }}
                                    </div>
                                </td>
                                <td class="text-end mono">
                                    {{ number_format($row->total_ok) }}
                                </td>
                                <td class="text-end mono text-danger">
                                    {{ number_format($row->total_reject) }}
                                </td>
                                <td class="text-end mono">
                                    {{ number_format($row->total_all) }}
                                </td>
                                <td class="text-end mono">
                                    @if (!is_null($row->efficiency))
                                        <span class="badge-pill-soft bg-success-subtle text-success">
                                            {{ number_format($row->efficiency, 1) }}%
                                        </span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end mono">
                                    {{ number_format($row->avg_per_period, 1) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-3 text-muted">
                                    No sewing return data for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-2 border-top muted small">
                Efficiency = OK / (OK + reject). Average per period uses only periods (day / week / month)
                where the operator has at least one return.
            </div>
        </div>
    </div>
@endsection
