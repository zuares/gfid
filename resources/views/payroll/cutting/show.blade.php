@extends('layouts.app')

@section('title', 'Payroll • Cutting Periode ' . $period->period_start . ' s/d ' . $period->period_end)

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .75rem .75rem 4.5rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(129, 140, 248, 0.16) 0,
                    rgba(45, 212, 191, 0.10) 24%,
                    #f9fafb 65%);
        }

        .card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.25);
            box-shadow:
                0 12px 35px rgba(15, 23, 42, 0.08),
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

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .6rem;
            }

            .table-responsive {
                font-size: .86rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        {{-- TOP BAR --}}
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <a href="{{ route('payroll.cutting.index') }}" class="btn btn-link px-0 small mb-1">
                    ← Kembali ke daftar periode
                </a>
                <h1 class="h5 mb-1">
                    Payroll Cutting
                    <span class="d-block fs-6 fw-normal">
                        Periode {{ id_date($period->period_start) }} &mdash; {{ id_date($period->period_end) }}
                    </span>
                </h1>
                <p class="help-text mb-0">
                    Status:
                    @if ($period->status === 'final')
                        <span class="badge bg-success-subtle text-success border border-success-subtle">Final</span>
                    @else
                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Draft</span>
                    @endif
                </p>
            </div>
            <div class="d-flex flex-column flex-sm-row gap-2">
                @if ($period->status !== 'final')
                    <form action="{{ route('payroll.cutting.regenerate', $period) }}" method="POST"
                        onsubmit="return confirm('Generate ulang akan menghapus perhitungan sebelumnya. Lanjutkan?')">
                        @csrf
                        <button class="btn btn-outline-secondary btn-sm w-100">
                            Regenerate
                        </button>
                    </form>
                    <form action="{{ route('payroll.cutting.finalize', $period) }}" method="POST"
                        onsubmit="return confirm('Set periode ini menjadi FINAL? Data tidak bisa diedit setelah final.')">
                        @csrf
                        <button class="btn btn-success btn-sm w-100">
                            Finalkan
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- ALERT --}}
        @if (session('status'))
            <div class="alert alert-success py-2 small">
                {{ session('status') }}
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger py-2 small">
                {{ session('error') }}
            </div>
        @endif

        {{-- SUMMARY PER OPERATOR --}}
        <div class="card mb-3">
            <div class="card-body">
                <h2 class="h6 mb-2">Ringkasan per Operator</h2>
                <p class="help-text mb-3">
                    Total qty dan amount per operator untuk periode ini.
                </p>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Operator</th>
                                <th class="text-end">Total Qty OK</th>
                                <th class="text-end">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($summaryByEmployee as $row)
                                <tr>
                                    <td>
                                        {{ $row['employee_name'] }}
                                        @if (!empty($row['employee_id']))
                                            <a href="{{ route('payroll.cutting.slip', [$period, $row['employee_id']]) }}"
                                                class="badge bg-primary-subtle text-primary border border-primary-subtle ms-2">
                                                Slip
                                            </a>
                                        @endif
                                    </td>
                                    <td class="text-end mono">
                                        {{ number_format($row['total_qty'], 2, ',', '.') }}
                                    </td>
                                    <td class="text-end mono">
                                        {{ number_format($row['total_amount'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">
                                        Belum ada data payroll untuk periode ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if ($summaryByEmployee->isNotEmpty())
                            <tfoot>
                                <tr class="fw-semibold">
                                    <td>Grand Total</td>
                                    <td class="text-end mono">
                                        {{ number_format($grandTotalQty, 2, ',', '.') }}
                                    </td>
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

        {{-- DETAIL LINES --}}
        <div class="card">
            <div class="card-body">
                <h2 class="h6 mb-2">Detail per Item</h2>
                <p class="help-text mb-3">
                    Breakdown per operator × kategori × item.
                </p>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Operator</th>
                                <th>Kategori</th>
                                <th>Item</th>
                                <th class="text-end">Qty OK</th>
                                <th class="text-end">Rate / pcs</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lines as $line)
                                <tr>
                                    <td>{{ $line->employee->name ?? '-' }}</td>
                                    <td>{{ $line->category->name ?? '-' }}</td>
                                    <td>
                                        <span class="mono">{{ $line->item->code ?? '-' }}</span>
                                        <span class="text-muted d-block small">
                                            {{ $line->item->name ?? '' }}
                                        </span>
                                    </td>
                                    <td class="text-end mono">
                                        {{ number_format($line->total_qty_ok, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end mono">
                                        {{ number_format($line->rate_per_pcs, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end mono">
                                        {{ number_format($line->amount, 0, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">
                                        Tidak ada detail payroll.
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
