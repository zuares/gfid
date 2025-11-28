{{-- resources/views/production/sewing_returns/report_outstanding.blade.php --}}
@extends('layouts.app')

@section('title', 'Production • Sewing Outstanding Report')

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
    </style>
@endpush

@section('content')
    <div class="page-wrap py-3 py-md-4">

        {{-- Header --}}
        <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h1 class="h4 mb-1">Sewing Outstanding Report</h1>
                <div class="muted">
                    Lines where returned quantity (OK + reject) is still less than picked.
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
                    <label class="form-label mb-1">Date from (pickup)</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                </div>

                <div class="w-100">
                    <label class="form-label mb-1">Date to (pickup)</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        Apply
                    </button>

                    {{-- 🔁 route reset ke production.reports.outstanding --}}
                    <a href="{{ route('production.reports.outstanding') }}" class="btn btn-outline-secondary">
                        Reset
                    </a>
                </div>
            </form>
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
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($lines as $line)
                            @php
                                $picked = $line->qty_bundle ?? 0;
                                $returnedOk = $line->qty_returned_ok ?? 0;
                                $returnedReject = $line->qty_returned_reject ?? 0;
                                $outstanding = max($picked - $returnedOk - $returnedReject, 0);
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold mono">
                                        {{ $line->pickup->code ?? '-' }}
                                    </div>
                                    <div class="muted">
                                        {{ id_date($line->pickup->date ?? null) }}
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold">
                                        {{ $line->pickup->operator->code ?? '-' }}
                                    </div>
                                    <div class="muted">
                                        {{ $line->pickup->operator->name ?? '' }}
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
                                <td class="text-end mono">
                                    {{ number_format($picked) }}
                                </td>
                                <td class="text-end mono text-success">
                                    {{ number_format($returnedOk) }}
                                </td>
                                <td class="text-end mono text-danger">
                                    {{ number_format($returnedReject) }}
                                </td>
                                <td class="text-end mono">
                                    @if ($outstanding > 0)
                                        <span class="badge-pill-soft bg-warning-subtle text-dark">
                                            {{ number_format($outstanding) }}
                                        </span>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-3 text-muted">
                                    No outstanding sewing lines for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-2 border-top muted small">
                Note: Outstanding = Picked − Returned OK − Returned reject.
            </div>
        </div>

    </div>
@endsection
