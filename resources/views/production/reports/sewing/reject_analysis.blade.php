{{-- resources/views/production/reports/sewing/reject_analysis.blade.php --}}
@extends('layouts.app')

@section('title', 'Production • Reject Sewing Analysis')

@push('head')
    <style>
        .page-wrap {
            max-width: 1200px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .card-soft {
            background: color-mix(in srgb, var(--card) 82%, var(--line) 18%);
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
                grid-template-columns: minmax(0, 2.2fr) minmax(0, 2.2fr) minmax(0, 1.5fr) auto;
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
            padding: .12rem .55rem;
            font-size: .75rem;
        }

        .btn-report-tab {
            border-radius: 999px;
            padding-inline: .9rem;
        }

        .btn-report-tab.active {
            background: var(--accent);
            color: #fff;
            border-color: var(--accent);
        }

        .section-title {
            font-size: .86rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap py-3 py-md-4">

        {{-- Header --}}
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
            <div>
                <h1 class="h4 mb-1">Reject Sewing Analysis</h1>
                <div class="muted">
                    Analyze sewing rejects by operator and item to find root causes and improvement targets.
                </div>
            </div>

            {{-- Quick nav between sewing reports --}}
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('production.reports.productivity') }}"
                    class="btn btn-sm btn-outline-secondary btn-report-tab">
                    Productivity
                </a>
                <a href="{{ route('production.reports.aging_wip_sew') }}"
                    class="btn btn-sm btn-outline-secondary btn-report-tab">
                    Aging WIP-SEW
                </a>
                <a href="{{ route('production.reports.partial_pickup') }}"
                    class="btn btn-sm btn-outline-secondary btn-report-tab">
                    Partial Pickup
                </a>
                <a href="{{ route('production.reports.report_reject') }}"
                    class="btn btn-sm btn-outline-secondary btn-report-tab active">
                    Reject Analysis
                </a>
            </div>
        </div>

        {{-- Filters --}}
        <div class="card p-3 mb-3">
            <form method="GET" class="filters-grid">
                {{-- Operator --}}
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

                {{-- Item --}}
                <div class="w-100">
                    <label class="form-label mb-1">Item</label>
                    <select name="item_id" class="form-select">
                        <option value="">All items</option>
                        @foreach ($items as $it)
                            <option value="{{ $it->id }}" @selected($itemId == $it->id)>
                                {{ $it->code }} — {{ $it->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Date from / to --}}
                <div class="d-flex flex-column flex-md-row gap-2 w-100">
                    <div class="flex-fill">
                        <label class="form-label mb-1">Return date from</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                    </div>
                    <div class="flex-fill">
                        <label class="form-label mb-1">Return date to</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                    </div>
                </div>

                {{-- Actions --}}
                <div class="d-flex gap-2 justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        Apply
                    </button>
                    <a href="{{ route('production.reports.report_reject') }}" class="btn btn-outline-secondary">
                        Reset
                    </a>
                </div>
            </form>

            {{-- Active filter chips --}}
            @if ($dateFrom || $dateTo || $operatorId || $itemId)
                <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
                    <span class="muted me-1">Active filters:</span>
                    @if ($operatorId)
                        @php $op = $operators->firstWhere('id', $operatorId); @endphp
                        @if ($op)
                            <span class="chip">
                                Operator: {{ $op->code }} — {{ $op->name }}
                            </span>
                        @endif
                    @endif

                    @if ($itemId)
                        @php $item = $items->firstWhere('id', $itemId); @endphp
                        @if ($item)
                            <span class="chip">
                                Item: {{ $item->code }} — {{ $item->name }}
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
                </div>
            @endif
        </div>

        {{-- Top summary cards --}}
        @php
            $totalCases = $details->count();
            $rejectRatio = $totalOk + $totalReject > 0 ? ($totalReject / max($totalOk + $totalReject, 1)) * 100 : 0;
        @endphp

        <div class="row g-2 mb-3">
            <div class="col-6 col-md-3">
                <div class="card card-soft p-2">
                    <div class="muted mb-1">Total reject (pcs)</div>
                    <div class="mono fs-5">{{ number_format($totalReject) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-soft p-2">
                    <div class="muted mb-1">Total OK (pcs)</div>
                    <div class="mono fs-5">{{ number_format($totalOk) }}</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-soft p-2">
                    <div class="muted mb-1">Reject ratio</div>
                    <div class="mono fs-5">
                        {{ number_format($rejectRatio, 2) }}%
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card card-soft p-2">
                    <div class="muted mb-1">Reject cases</div>
                    <div class="mono fs-5">{{ number_format($totalCases) }}</div>
                </div>
            </div>
        </div>

        {{-- Summary by operator + by item --}}
        <div class="row g-3 mb-3">
            <div class="col-12 col-lg-6">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="section-title">Top operators by reject</div>
                        <span class="muted small">sorted by total reject</span>
                    </div>

                    <div class="table-wrap">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Operator</th>
                                    <th class="text-end">Reject (pcs)</th>
                                    <th class="text-end">OK (pcs)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($summaryByOperator->take(10) as $row)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">
                                                {{ $row['operator_code'] }}
                                            </div>
                                            <div class="muted">
                                                {{ $row['operator_name'] }}
                                            </div>
                                        </td>
                                        <td class="text-end mono text-danger">
                                            {{ number_format($row['total_reject']) }}
                                        </td>
                                        <td class="text-end mono text-success">
                                            {{ number_format($row['total_ok']) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-2 muted">
                                            No reject data by operator.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card p-3 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="section-title">Top items by reject</div>
                        <span class="muted small">sorted by total reject</span>
                    </div>

                    <div class="table-wrap">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="text-end">Reject (pcs)</th>
                                    <th class="text-end">OK (pcs)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($summaryByItem->take(10) as $row)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">
                                                {{ $row['item_code'] }}
                                            </div>
                                            <div class="muted">
                                                {{ $row['item_name'] }}
                                            </div>
                                        </td>
                                        <td class="text-end mono text-danger">
                                            {{ number_format($row['total_reject']) }}
                                        </td>
                                        <td class="text-end mono text-success">
                                            {{ number_format($row['total_ok']) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center py-2 muted">
                                            No reject data by item.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Detail table --}}
        <div class="card p-0">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <div class="section-title mb-0">Reject detail</div>
                <div class="muted small">
                    Showing {{ number_format($details->count()) }} rows
                </div>
            </div>

            <div class="table-wrap">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Return</th>
                            <th>Operator</th>
                            <th>Item</th>
                            <th class="text-end">OK (pcs)</th>
                            <th class="text-end text-danger">Reject (pcs)</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($details as $row)
                            <tr>
                                <td>
                                    <span class="mono">
                                        {{ id_date($row->return_date) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="mono fw-semibold">
                                        {{ $row->return_code }}
                                    </span>
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
                                    <span class="badge-pill-soft bg-danger text-white">
                                        {{ number_format($row->qty_reject) }}
                                    </span>
                                </td>
                                <td style="max-width: 220px;">
                                    <span class="muted small">
                                        {{ $row->notes }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-3 text-muted">
                                    No sewing rejects found for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-2 border-top muted small">
                Data source: sewing_returns & sewing_return_lines with qty_reject &gt; 0.
            </div>
        </div>
    </div>
@endsection
