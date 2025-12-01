@extends('layouts.app')

@section('title', 'Detail Riwayat Operator • ' . $employee->name)

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .75rem .75rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.10) 0,
                    rgba(45, 212, 191, 0.08) 26%,
                    #f9fafb 60%);
        }

        .card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.25);
            box-shadow:
                0 8px 22px rgba(15, 23, 42, 0.06),
                0 0 0 1px rgba(15, 23, 42, 0.02);
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .help-text {
            font-size: .8rem;
            color: var(--muted);
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .card {
                box-shadow: none;
                border-color: #aaa;
            }

            body {
                background: white;
            }

            .page-wrap {
                padding: 0;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-start mb-3 no-print">
            <div>
                <h1 class="h5 mb-1">Detail Riwayat Operator</h1>
                <p class="help-text mb-0">
                    <strong>{{ $employee->name }}</strong>
                    • {{ $labelRange }}
                </p>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('payroll.reports.operators', request()->query()) }}" class="btn btn-link btn-sm">
                    ← Rekap
                </a>

                <button onclick="window.print()" class="btn btn-outline-primary btn-sm">
                    Print
                </button>
            </div>
        </div>

        {{-- SUMMARY --}}
        <div class="card mb-3">
            <div class="card-body">

                <h2 class="h6 mb-2">Ringkasan</h2>
                <p class="help-text mb-3">
                    Total qty dan amount per modul.
                </p>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Modul</th>
                                <th class="text-end">Qty Total</th>
                                <th class="text-end">Amount Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Cutting</td>
                                <td class="text-end mono">
                                    {{ number_format($summary['cutting_qty'], 2, ',', '.') }}
                                </td>
                                <td class="text-end mono">
                                    {{ number_format($summary['cutting_amount'], 0, ',', '.') }}
                                </td>
                            </tr>

                            <tr>
                                <td>Sewing</td>
                                <td class="text-end mono">
                                    {{ number_format($summary['sewing_qty'], 2, ',', '.') }}
                                </td>
                                <td class="text-end mono">
                                    {{ number_format($summary['sewing_amount'], 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>

                        <tfoot>
                            <tr class="fw-semibold table-light">
                                <td>Grand Total</td>
                                <td class="text-end mono">
                                    {{ number_format($summary['grand_qty'], 2, ',', '.') }}
                                </td>
                                <td class="text-end mono">
                                    {{ number_format($summary['grand_amount'], 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            </div>
        </div>

        {{-- CUTTING DAILY DETAIL --}}
        <div class="card mb-3">
            <div class="card-body">
                <h2 class="h6 mb-2">Cutting • Detail Harian</h2>
                <p class="help-text mb-3">Hasil QC per tanggal.</p>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:14%">Tanggal</th>
                                <th style="width:20%">Kategori</th>
                                <th style="width:25%">Item</th>
                                <th class="text-end" style="width:12%">Qty</th>
                                <th class="text-end" style="width:14%">Rate</th>
                                <th class="text-end" style="width:15%">Amount</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($cuttingDaily as $date => $rows)
                                @foreach ($rows as $line)
                                    <tr>
                                        <td>{{ id_date($date) }}</td>

                                        <td>{{ $line->category->name ?? '-' }}</td>

                                        <td>
                                            <span class="mono">{{ $line->item->code ?? '-' }}</span>
                                            <div class="small text-muted">{{ $line->item->name ?? '' }}</div>
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
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">
                                        Tidak ada data cutting.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        {{-- SEWING DETAIL DAILY --}}
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h6 mb-2">Sewing • Detail Harian</h2>
                <p class="help-text mb-3">Hasil setor per tanggal.</p>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width:14%">Tanggal</th>
                                <th style="width:20%">Kategori</th>
                                <th style="width:25%">Item</th>
                                <th class="text-end" style="width:12%">Qty</th>
                                <th class="text-end" style="width:14%">Rate</th>
                                <th class="text-end" style="width:15%">Amount</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($sewingDaily as $date => $rows)
                                @foreach ($rows as $line)
                                    <tr>
                                        <td>{{ id_date($date) }}</td>

                                        <td>{{ $line->category->name ?? '-' }}</td>

                                        <td>
                                            <span class="mono">{{ $line->item->code ?? '-' }}</span>
                                            <div class="small text-muted">{{ $line->item->name ?? '' }}</div>
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
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">
                                        Tidak ada data sewing.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>
@endsection
