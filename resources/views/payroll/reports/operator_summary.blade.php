@extends('layouts.app')

@section('title', 'Rekap Payroll per Operator')

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
            border: 1px solid rgba(148, 163, 184, 0.22);
            box-shadow:
                0 8px 25px rgba(15, 23, 42, 0.06),
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

        .sub-row {
            background: color-mix(in srgb, var(--card) 90%, #d1d5db 10%);
        }

        .sub-row td {
            padding-top: 2px !important;
            padding-bottom: 2px !important;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .6rem;
            }

            .table-responsive {
                font-size: .86rem;
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

            .card {
                box-shadow: none;
                border-color: #ccc;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $moduleLabel = match ($module) {
            'cutting' => 'Cutting',
            'sewing' => 'Sewing',
            'all' => 'Semua modul (Cutting + Sewing)',
            default => ucfirst($module),
        };
    @endphp

    <div class="page-wrap">
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-start mb-3 no-print">
            <div>
                <h1 class="h5 mb-1">Rekap Payroll per Operator</h1>
                <p class="help-text mb-0">
                    Modul:
                    <strong>{{ $moduleLabel }}</strong> •
                    {{ $labelRange }}
                </p>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('payroll.reports.operator_slips', request()->query()) }}"
                    class="btn btn-outline-secondary btn-sm">
                    Slip per Operator
                </a>

                <button onclick="window.print()" class="btn btn-outline-primary btn-sm">
                    Print
                </button>
            </div>
        </div>

        {{-- FILTER --}}
        <div class="card mb-3 no-print">
            <div class="card-body py-2">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-6 col-md-3">
                        <label class="form-label mb-1 small">Modul</label>
                        <select name="module" class="form-select form-select-sm">
                            @if (($hasCutting ?? true) || $module === 'cutting')
                                <option value="cutting" {{ $module === 'cutting' ? 'selected' : '' }}>Cutting</option>
                            @endif
                            @if (($hasSewing ?? true) || $module === 'sewing')
                                <option value="sewing" {{ $module === 'sewing' ? 'selected' : '' }}>Sewing</option>
                            @endif
                            <option value="all" {{ $module === 'all' ? 'selected' : '' }}>Semua modul</option>
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
                        <label class="form-label mb-1 small">Urutkan</label>
                        <div class="d-flex gap-2">
                            <select name="sort" class="form-select form-select-sm">
                                <option value="name" {{ ($sort ?? 'name') === 'name' ? 'selected' : '' }}>
                                    Nama (A–Z)
                                </option>
                                <option value="amount" {{ ($sort ?? '') === 'amount' ? 'selected' : '' }}>
                                    Nominal terbesar
                                </option>
                            </select>
                            <button class="btn btn-outline-secondary btn-sm px-3">
                                Terapkan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- MAIN TABLE --}}
        <div class="card">
            <div class="card-body pb-1">
                {{-- SUMMARY BAR --}}
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="help-text">
                        Range:
                        <span class="mono">{{ id_date($start) }} s/d {{ id_date($end) }}</span> •
                        Operator:
                        <span class="mono">{{ $byEmployee->count() }}</span>
                    </div>
                    <div class="text-end">
                        <div class="help-text">Grand Total Amount</div>
                        <div class="mono fw-semibold">
                            {{ number_format($grandTotalAmount, 0, ',', '.') }}
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:40px;">#</th>
                                <th style="width:180px;">Operator</th>
                                {{-- Ringkasan Pengerjaan dibagi jadi 4 kolom --}}
                                <th style="width:130px;">Jenis</th>
                                <th class="text-end" style="width:120px;">Qty (pcs)</th>
                                <th class="text-end" style="width:140px;">Harga Satuan</th>
                                <th class="text-end" style="width:140px;">Total</th>
                                <th class="text-end" style="width:160px;">Grand Total Amount</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($byEmployee as $i => $row)
                                @php
                                    $cuttingQty = $row['cutting_qty'] ?? 0;
                                    $cuttingAmount = $row['cutting_amount'] ?? 0;
                                    $cuttingRate = $row['cutting_rate'] ?? 0;

                                    $sewingQty = $row['sewing_qty'] ?? 0;
                                    $sewingAmount = $row['sewing_amount'] ?? 0;
                                    $sewingRate = $row['sewing_rate'] ?? 0;
                                @endphp

                                {{-- ROW GRAND TOTAL PER OPERATOR --}}
                                <tr class="fw-semibold">
                                    <td>{{ $i + 1 }}</td>
                                    <td>
                                        <div>{{ $row['employee_name'] }}</div>

                                        @if (!empty($row['employee_id']))
                                            <div class="small">
                                                <a href="{{ route(
                                                    'payroll.reports.operator_detail',
                                                    [
                                                        'employee' => $row['employee_id'],
                                                    ] + request()->query(),
                                                ) }}"
                                                    class="text-decoration-none">
                                                    <span class="text-muted">Lihat riwayat</span>
                                                </a>
                                            </div>
                                        @endif
                                    </td>
                                    <td colspan="4"></td>
                                    <td class="text-end mono">
                                        {{ number_format($row['total_amount'], 0, ',', '.') }}
                                    </td>
                                </tr>

                                {{-- SUB-ROW: CUTTING --}}
                                @if ($cuttingQty > 0 || $cuttingAmount > 0)
                                    <tr class="sub-row">
                                        <td></td>
                                        <td></td>
                                        <td>CUTTING</td>
                                        <td class="text-end mono">
                                            {{ number_format($cuttingQty, 2, ',', '.') }}
                                        </td>
                                        <td class="text-end mono">
                                            {{ number_format($cuttingRate, 0, ',', '.') }}
                                        </td>
                                        <td class="text-end mono">
                                            {{ number_format($cuttingAmount, 0, ',', '.') }}
                                        </td>
                                        <td></td>
                                    </tr>
                                @endif

                                {{-- SUB-ROW: SEWING --}}
                                @if ($sewingQty > 0 || $sewingAmount > 0)
                                    <tr class="sub-row">
                                        <td></td>
                                        <td></td>
                                        <td>SEWING</td>
                                        <td class="text-end mono">
                                            {{ number_format($sewingQty, 2, ',', '.') }}
                                        </td>
                                        <td class="text-end mono">
                                            {{ number_format($sewingRate, 0, ',', '.') }}
                                        </td>
                                        <td class="text-end mono">
                                            {{ number_format($sewingAmount, 0, ',', '.') }}
                                        </td>
                                        <td></td>
                                    </tr>
                                @endif

                                {{-- spacer antar-operator --}}
                                <tr>
                                    <td colspan="7" class="py-1"></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Tidak ada data payroll untuk range ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                        @if ($byEmployee->isNotEmpty())
                            <tfoot>
                                <tr class="table-light fw-semibold">
                                    <td colspan="6">GRAND TOTAL SEMUA OPERATOR</td>
                                    <td class="text-end mono">
                                        {{ number_format($grandTotalAmount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

            </div>
        </div>
    </div>
@endsection
