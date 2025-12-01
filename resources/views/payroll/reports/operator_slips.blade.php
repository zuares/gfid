@extends('layouts.app')

@section('title', 'Slip Borongan per Operator')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .9rem .9rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.10) 0,
                    rgba(45, 212, 191, 0.08) 26%,
                    #f9fafb 60%);
        }

        .card-slip {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.30);
            box-shadow:
                0 8px 22px rgba(15, 23, 42, 0.06),
                0 0 0 1px rgba(15, 23, 42, 0.02);
            page-break-inside: avoid;
        }

        .card-slip .card-body {
            padding: 1rem 1.1rem 1.3rem;
        }

        .slip-header {
            padding-bottom: .5rem;
            margin-bottom: .6rem;
            border-bottom: 1px dashed rgba(148, 163, 184, 0.7);
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .help-text {
            font-size: .8rem;
            color: var(--muted);
        }

        .table-slip th,
        .table-slip td {
            padding-top: .3rem !important;
            padding-bottom: .3rem !important;
        }

        .table-slip thead th {
            font-size: .78rem;
        }

        .table-slip tfoot td {
            padding-top: .45rem !important;
            padding-bottom: .45rem !important;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .6rem;
            }

            .card-slip .card-body {
                padding-inline: .85rem;
            }

            .table-slip {
                font-size: .84rem;
            }
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

            .card-slip {
                box-shadow: none;
                border-color: #aaa;
                margin-bottom: .75rem;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $moduleLabel = match ($module) {
            'cutting' => 'Cutting',
            'sewing' => 'Sewing',
            'all' => 'Cutting + Sewing',
            default => ucfirst($module),
        };
    @endphp

    <div class="page-wrap">
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-start mb-3 no-print">
            <div>
                <h1 class="h5 mb-1">Slip Borongan per Operator</h1>
                <p class="help-text mb-0">
                    Modul: <strong>{{ $moduleLabel }}</strong> • {{ $labelRange }}
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

        {{-- FILTER SINGKAT --}}
        <div class="card mb-3 no-print">
            <div class="card-body py-2 px-3">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-6 col-md-3">
                        <label class="form-label mb-1 small">Modul</label>
                        <select name="module" class="form-select form-select-sm">
                            <option value="cutting" {{ $module === 'cutting' ? 'selected' : '' }}>Cutting</option>
                            <option value="sewing" {{ $module === 'sewing' ? 'selected' : '' }}>Sewing</option>
                            <option value="all" {{ $module === 'all' ? 'selected' : '' }}>Cutting + Sewing</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label mb-1 small">Tipe</label>
                        <select name="range_type" class="form-select form-select-sm">
                            <option value="monthly" {{ $rangeType === 'monthly' ? 'selected' : '' }}>Bulanan</option>
                            <option value="weekly" {{ $rangeType === 'weekly' ? 'selected' : '' }}>Mingguan</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label mb-1 small">Tanggal referensi</label>
                        <input type="date" name="ref_date" value="{{ $refDate }}"
                            class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-3">
                        <button class="btn btn-outline-secondary btn-sm w-100">
                            Terapkan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- SLIP PER OPERATOR --}}
        @forelse ($slips as $slip)
            <div class="card-slip mb-3">
                <div class="card-body">
                    {{-- HEADER SLIP --}}
                    <div class="slip-header d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-semibold">
                                Slip Borongan • {{ $slip['employee_name'] }}
                            </div>
                            <div class="help-text">
                                Periode:
                                <span class="mono">
                                    {{ id_date($start) }} s/d {{ id_date($end) }}
                                </span>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="help-text mb-1">Grand Total Amount</div>
                            <div class="mono fw-semibold">
                                {{ number_format($slip['total_amount'], 0, ',', '.') }}
                            </div>
                        </div>
                    </div>

                    {{-- TABEL DETAIL --}}
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 table-slip">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 14%;">Jenis</th>
                                    <th style="width: 16%;">Kategori</th>
                                    <th style="width: 22%;">Item</th>
                                    <th class="text-end" style="width: 12%;">Qty (pcs)</th>
                                    <th class="text-end" style="width: 14%;">Harga Satuan</th>
                                    <th class="text-end" style="width: 14%;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($slip['details'] as $detail)
                                    <tr>
                                        <td>{{ $detail['module'] }}</td>
                                        <td>{{ $detail['category'] ?? '-' }}</td>
                                        <td>
                                            <span class="mono">{{ $detail['item_code'] ?? '-' }}</span>
                                            @if (!empty($detail['item_name']))
                                                <div class="small text-muted">
                                                    {{ $detail['item_name'] }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-end mono">
                                            {{ number_format($detail['qty'], 2, ',', '.') }}
                                        </td>
                                        <td class="text-end mono">
                                            {{ number_format($detail['rate'], 0, ',', '.') }}
                                        </td>
                                        <td class="text-end mono">
                                            {{ number_format($detail['amount'], 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-2">
                                            Tidak ada detail borongan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr class="fw-semibold table-light">
                                    <td colspan="5">Total Dibayarkan</td>
                                    <td class="text-end mono">
                                        {{ number_format($slip['total_amount'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- FOOTER TANDA TANGAN --}}
                    <div class="row mt-3 small">
                        <div class="col-6 text-start">
                            <div class="help-text mb-1">Diterima oleh,</div>
                            <div style="height: 40px;"></div>
                            <div class="mono">{{ $slip['employee_name'] }}</div>
                        </div>
                        <div class="col-6 text-end">
                            <div class="help-text mb-1">Disetujui,</div>
                            <div style="height: 40px;"></div>
                            <div class="mono">{{ auth()->user()->name ?? '................' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="card">
                <div class="card-body text-center text-muted py-4">
                    Tidak ada data payroll untuk range ini.
                </div>
            </div>
        @endforelse
    </div>
@endsection
