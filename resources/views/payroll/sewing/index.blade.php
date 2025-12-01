@extends('layouts.app')

@section('title', 'Payroll • Sewing')

@push('head')
    <style>
        .page-wrap {
            max-width: 980px;
            margin-inline: auto;
            padding: .75rem .75rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.12) 0,
                    rgba(45, 212, 191, 0.08) 26%,
                    #f9fafb 60%);
        }

        .card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.25);
            box-shadow:
                0 10px 30px rgba(15, 23, 42, 0.06),
                0 0 0 1px rgba(15, 23, 42, 0.02);
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .help-text {
            font-size: .78rem;
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
        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h1 class="h5 mb-1">Payroll Sewing</h1>
                <p class="help-text mb-0">
                    Rekap borongan sewing per periode. Gunakan tombol <strong>Generate</strong> untuk membuat periode baru.
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('payroll.sewing.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-magic"></i>
                    <span class="d-none d-sm-inline">Generate Periode</span>
                </a>
            </div>
        </div>

        {{-- FILTER --}}
        <div class="card mb-3">
            <div class="card-body py-2">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-6 col-md-3">
                        <label class="form-label mb-1 small">Dari tanggal</label>
                        <input type="date" name="from" value="{{ request('from') }}"
                            class="form-control form-control-sm">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label mb-1 small">Sampai tanggal</label>
                        <input type="date" name="to" value="{{ request('to') }}"
                            class="form-control form-control-sm">
                    </div>
                    <div class="col-12 col-md-3">
                        <button class="btn btn-outline-secondary btn-sm w-100">
                            <i class="bi bi-funnel"></i> Terapkan Filter
                        </button>
                    </div>
                </form>
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

        {{-- TABLE PERIODE --}}
        <div class="card">
            <div class="card-body pb-1">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 mb-0">Daftar Periode</h2>
                    <span class="help-text">
                        Total {{ $periods->total() }} periode
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-1">
                        <thead class="table-light">
                            <tr>
                                <th>Periode</th>
                                <th>Status</th>
                                <th class="text-end">Total Qty</th>
                                <th class="text-end">Total Amount</th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($periods as $period)
                                @php
                                    // kalau controller sudah pakai loadSum('lines as lines_total_amount', 'amount')
                                    // dan pengen ngikut itu, tinggal ganti:
                                    // $totalAmount = $period->lines_total_amount ?? 0;
                                    $totalQty = $period->lines->sum('total_qty_ok');
                                    $totalAmount = $period->lines->sum('amount');
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold mono">
                                            {{ id_date($period->period_start) }} &mdash; {{ id_date($period->period_end) }}
                                        </div>
                                        <div class="help-text">
                                            Dibuat {{ id_datetime($period->created_at) }}
                                        </div>
                                    </td>
                                    <td>
                                        @if ($period->status === 'final')
                                            <span
                                                class="badge bg-success-subtle text-success border border-success-subtle">Final</span>
                                        @else
                                            <span
                                                class="badge bg-warning-subtle text-warning border border-warning-subtle">Draft</span>
                                        @endif
                                    </td>
                                    <td class="text-end mono">
                                        {{ number_format($totalQty, 2, ',', '.') }}
                                    </td>
                                    <td class="text-end mono">
                                        {{ number_format($totalAmount, 0, ',', '.') }}
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('payroll.sewing.show', $period) }}"
                                            class="btn btn-outline-secondary btn-sm">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Belum ada periode payroll sewing.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-2">
                    {{ $periods->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
