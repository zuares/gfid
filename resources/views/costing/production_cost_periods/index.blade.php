@extends('layouts.app')

@section('title', 'Periode HPP Produksi')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .75rem .75rem 3rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.10) 0,
                    rgba(45, 212, 191, 0.08) 26%,
                    #f9fafb 60%);
        }

        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.28);
            box-shadow:
                0 10px 24px rgba(15, 23, 42, 0.06),
                0 0 0 1px rgba(15, 23, 42, 0.03);
        }

        .badge-soft {
            border-radius: 999px;
            padding: .2rem .7rem;
            font-size: .75rem;
            border: 1px solid rgba(148, 163, 184, .6);
            background: rgba(15, 23, 42, 0.02);
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0">Periode HPP Produksi</h4>
                <p class="text-muted small mb-0">
                    Snapshot HPP per item berdasarkan payroll cutting / sewing / finishing.
                </p>
            </div>

            {{-- kalau nanti ada route create, aktifkan tombol ini --}}
            {{-- <a href="{{ route('costing.production_cost_periods.create') }}" class="btn btn-primary btn-sm">
                + Periode Baru
            </a> --}}
        </div>

        @if (session('status'))
            <div class="alert alert-success py-2 px-3 small">
                {{ session('status') }}
            </div>
        @endif

        <div class="card card-main">
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr class="table-light">
                            <th>Nama / Kode</th>
                            <th>Range Tanggal</th>
                            <th>Tgl Snapshot</th>
                            <th>Payroll</th>
                            <th>Status</th>
                            <th>Aktif</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($periods as $p)
                            <tr>
                                <td>
                                    <a href="{{ route('costing.production_cost_periods.show', $p) }}"
                                        class="fw-semibold text-decoration-none">
                                        {{ $p->name ?? $p->code }}
                                    </a>
                                    <div class="text-muted small">
                                        {{ $p->code }}
                                    </div>
                                </td>
                                <td class="small">
                                    {{ optional($p->date_from)->format('d/m/Y') }} –
                                    {{ optional($p->date_to)->format('d/m/Y') }}
                                </td>
                                <td class="small">
                                    {{ optional($p->snapshot_date)->format('d/m/Y') }}
                                </td>
                                <td class="small">
                                    <div>
                                        <span class="text-muted">Cutting:</span>
                                        {{ optional($p->cuttingPayrollPeriod)->name ?? '-' }}
                                    </div>
                                    <div>
                                        <span class="text-muted">Sewing:</span>
                                        {{ optional($p->sewingPayrollPeriod)->name ?? '-' }}
                                    </div>
                                    <div>
                                        <span class="text-muted">Finishing:</span>
                                        {{ optional($p->finishingPayrollPeriod)->name ?? '-' }}
                                    </div>
                                </td>
                                <td>
                                    @if ($p->status === 'posted')
                                        <span class="badge-soft text-success border-success-subtle">
                                            POSTED
                                        </span>
                                    @else
                                        <span class="badge-soft text-secondary border-secondary-subtle">
                                            DRAFT
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($p->is_active ?? false)
                                        <span class="badge-soft text-primary border-primary-subtle">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="badge-soft text-muted border-secondary-subtle">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('costing.production_cost_periods.show', $p) }}"
                                        class="btn btn-outline-secondary btn-xs btn-sm">
                                        Detail
                                    </a>
                                    <a href="{{ route('costing.production_cost_periods.edit', $p) }}"
                                        class="btn btn-outline-primary btn-xs btn-sm">
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted small py-3">
                                    Belum ada periode HPP produksi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($periods instanceof \Illuminate\Pagination\AbstractPaginator)
                <div class="card-footer py-2">
                    {{ $periods->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
