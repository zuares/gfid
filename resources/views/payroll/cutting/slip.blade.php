@extends('layouts.app')

@section('title', 'Slip Borongan • ' . $employee->name)

@push('head')
    <style>
        .page-wrap {
            max-width: 700px;
            margin-inline: auto;
            padding: 1.2rem
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular
        }

        .slip-card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .25);
            padding: 1.25rem;
            box-shadow: 0 8px 25px rgba(15, 23, 42, .08);
        }

        @media print {
            .no-print {
                display: none
            }

            body {
                background: white
            }

            .slip-card {
                box-shadow: none;
                border: 1px solid #ccc
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        <div class="d-flex justify-content-between no-print mb-3">
            <a href="{{ route('payroll.cutting.show', $period) }}" class="btn btn-link px-0">
                ← Kembali
            </a>
            <button onclick="window.print()" class="btn btn-primary btn-sm">
                Print
            </button>
        </div>

        <div class="slip-card">
            <h1 class="h5 mb-1">Slip Borongan Cutting</h1>
            <p class="text-muted small mb-3">
                Periode: {{ id_date($period->period_start) }} — {{ id_date($period->period_end) }} <br>
                Operator: <strong>{{ $employee->name }}</strong>
            </p>

            <table class="table table-sm mb-3">
                <thead class="table-light">
                    <tr>
                        <th>Kategori</th>
                        <th>Item</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Rate</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lines as $line)
                        <tr>
                            <td>{{ $line->category->name ?? '-' }}</td>
                            <td>
                                <span class="mono">{{ $line->item->code ?? '-' }}</span>
                            </td>
                            <td class="text-end mono">{{ number_format($line->total_qty_ok, 2, ',', '.') }}</td>
                            <td class="text-end mono">{{ number_format($line->rate_per_pcs, 0, ',', '.') }}</td>
                            <td class="text-end mono">{{ number_format($line->amount, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="fw-semibold">
                        <td colspan="2">Total</td>
                        <td class="text-end mono">{{ number_format($totalQty, 2, ',', '.') }}</td>
                        <td></td>
                        <td class="text-end mono">{{ number_format($totalAmount, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>

            <p class="small text-muted mt-3">
                Dicetak: {{ id_datetime(now()) }}
            </p>
        </div>

    </div>
@endsection
