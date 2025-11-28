@extends('layouts.app')

@section('title', 'Partial Pickup Report')

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

        .badge-soft {
            font-size: .75rem;
            padding: .2rem .6rem;
            border-radius: 999px;
            background: color-mix(in srgb, var(--accent-soft) 25%, var(--card) 75%);
        }

        .mono {
            font-variant-numeric: tabular-nums;
        }

        .table-wrap {
            overflow-x: auto;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="fw-bold">🟩 Partial Pickup Report</h3>
        </div>

        {{-- FILTER --}}
        <form method="GET" class="card p-3 mb-3">
            <div class="row g-2">
                <div class="col-md-3">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100">Filter</button>
                </div>
            </div>
        </form>

        {{-- TABEL --}}
        <div class="card p-0 table-wrap">
            <table class="table table-hover align-middle mono">
                <thead class="table-light">
                    <tr>
                        <th>Pickup Date</th>
                        <th>Operator</th>
                        <th>Item</th>
                        <th>Bundle Qty</th>
                        <th>Returned</th>
                        <th>Outstanding</th>
                        <th>Aging (days)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $r)
                        <tr>
                            <td>{{ $r->pickup_date }}</td>
                            <td>{{ $r->sewingPickup->operator->name ?? '-' }}</td>
                            <td>{{ $r->bundle->finishedItem->name ?? '-' }}</td>

                            <td>{{ $r->qty_bundle }}</td>

                            <td>{{ $r->qty_returned_ok + $r->qty_returned_reject }}</td>

                            <td>
                                <span class="badge-soft">{{ $r->outstanding }}</span>
                            </td>

                            <td>
                                @if ($r->days_aging >= 5)
                                    <span class="text-danger fw-bold">{{ $r->days_aging }}</span>
                                @else
                                    {{ $r->days_aging }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                Tidak ada partial pickup.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </div>
@endsection
