@extends('layouts.app')

@section('title', 'Slip Borongan Cutting • Semua Operator')

@push('head')
    <style>
        .page-wrap {
            max-width: 900px;
            margin-inline: auto;
            padding: 1.2rem;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .slip-operator {
            background: var(--card);
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .25);
            padding: .9rem 1rem;
            margin-bottom: .75rem;
        }

        .slip-operator h2 {
            font-size: .95rem;
            margin-bottom: .1rem;
        }

        .slip-operator small {
            font-size: .78rem;
            color: var(--muted);
        }

        .table-sm th,
        .table-sm td {
            padding: .25rem .35rem;
            font-size: .8rem;
        }

        .section-divider {
            border-top: 1px dashed rgba(148, 163, 184, .6);
            margin: .85rem 0;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white;
            }

            .page-wrap {
                padding: 0;
                max-width: 100%;
            }

            .slip-operator {
                box-shadow: none;
                border-color: #ccc;
                page-break-inside: avoid;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        <div class="d-flex justify-content-between align-items-center mb-3 no-print">
            <div>
                <h1 class="h6 mb-1">Slip Borongan Cutting • Semua Operator</h1>
                <p class="text-muted small mb-0">
                    Periode: {{ id_date($period->period_start) }} — {{ id_date($period->period_end) }}
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('payroll.cutting.show', $period) }}" class="btn btn-link btn-sm px-0">
                    ← Kembali
                </a>
                <button onclick="window.print()" class="btn btn-primary btn-sm">
                    Print
                </button>
            </div>
        </div>

        {{-- Grand total --}}
        <div class="mb-2 small text-muted">
            Grand Total Qty OK:
            <span class="mono">{{ number_format($grandTotalQty, 2, ',', '.') }}</span> •
            Grand Total Amount:
            <span class="mono">{{ number_format($grandTotalAmount, 0, ',', '.') }}</span>
        </div>

        @foreach ($byEmployee as $employeeId => $chunk)
            @php
                /** @var \App\Models\Employee|null $emp */
                $emp = $chunk['employee'];
                $lines = $chunk['lines'];
            @endphp

            <div class="slip-operator">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div>
                        <h2 class="mb-0">
                            {{ $emp?->name ?? 'Tanpa nama' }}
                        </h2>
                        <small>
                            ID: {{ $emp?->id ?? $employeeId }}
                        </small>
                    </div>
                    <div class="text-end">
                        <div class="mono fw-semibold">
                            Rp {{ number_format($chunk['total_amount'], 0, ',', '.') }}
                        </div>
                        <small class="text-muted">
                            Qty OK: {{ number_format($chunk['total_qty'], 2, ',', '.') }}
                        </small>
                    </div>
                </div>

                <table class="table table-sm align-middle mb-1">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 26%">Kategori</th>
                            <th style="width: 24%">Item</th>
                            <th class="text-end" style="width: 15%">Qty</th>
                            <th class="text-end" style="width: 15%">Rate</th>
                            <th class="text-end" style="width: 20%">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($lines as $line)
                            <tr>
                                <td>{{ $line->category->name ?? '-' }}</td>
                                <td>
                                    <span class="mono">{{ $line->item->code ?? '-' }}</span>
                                    @if ($line->item?->name)
                                        <div class="text-muted small">{{ $line->item->name }}</div>
                                    @endif
                                </td>
                                <td class="text-end mono">
                                    {{ number_format($line->total_qty_ok, 2, ',', '.') }}
                                </td>
                                <td class="text-end mono">
                                    {{ number_format($line->rate_per_pcs, 0, ',', '.') }}
                                </td>
                                <td class="text-end mono">
                                    {{ number_format($line->amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="d-flex justify-content-between align-items-center mt-1">
                    <small class="text-muted">
                        Tanda tangan penerima:
                        __________________________________
                    </small>
                    <small class="text-muted">
                        Total: <span class="mono">Rp {{ number_format($chunk['total_amount'], 0, ',', '.') }}</span>
                    </small>
                </div>
            </div>

            @if (!$loop->last)
                <div class="section-divider"></div>
            @endif
        @endforeach

        <div class="no-print mt-3 small text-muted">
            Dicetak: {{ id_datetime(now()) }}
        </div>
    </div>
@endsection
