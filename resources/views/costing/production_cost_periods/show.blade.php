@extends('layouts.app')

@section('title', 'Detail Periode HPP Produksi')

@push('head')
    <style>
        .page-wrap {
            max-width: 1200px;
            margin-inline: auto;
            padding: .75rem .75rem 3.25rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.14) 0,
                    rgba(129, 140, 248, 0.12) 22%,
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

        .table-sticky thead th {
            position: sticky;
            top: 0;
            z-index: 1;
        }
    </style>
@endpush

@section('content')
    @php
        $fmt = fn($n, $dec = 2) => number_format($n ?? 0, $dec, ',', '.');
    @endphp

    <div class="page-wrap">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h4 class="mb-1">
                    Periode HPP Produksi: {{ $period->name ?? $period->code }}
                </h4>
                <div class="d-flex flex-wrap gap-2 align-items-center small">
                    <span class="badge-soft">
                        Kode: {{ $period->code }}
                    </span>
                    <span class="badge-soft">
                        Periode:
                        {{ optional($period->date_from)->format('d/m/Y') }} –
                        {{ optional($period->date_to)->format('d/m/Y') }}
                    </span>
                    <span class="badge-soft">
                        Snapshot: {{ optional($period->snapshot_date)->format('d/m/Y') }}
                    </span>
                    <span class="badge-soft">
                        Status: {{ strtoupper($period->status ?? 'draft') }}
                    </span>
                    @if ($period->is_active)
                        <span class="badge-soft text-primary border-primary-subtle">
                            Aktif
                        </span>
                    @endif
                </div>
                @if ($period->notes)
                    <p class="small text-muted mt-2 mb-0">
                        {!! nl2br(e($period->notes)) !!}
                    </p>
                @endif
            </div>

            <div class="d-flex flex-column align-items-end gap-2">
                @if (session('status'))
                    <div class="alert alert-success py-1 px-2 small mb-0">
                        {{ session('status') }}
                    </div>
                @endif

                <div class="d-flex gap-2">
                    <a href="{{ route('costing.production_cost_periods.index') }}" class="btn btn-sm btn-outline-secondary">
                        &larr; Kembali
                    </a>
                    <a href="{{ route('costing.production_cost_periods.edit', $period) }}"
                        class="btn btn-sm btn-outline-primary">
                        Edit
                    </a>
                    <form action="{{ route('costing.production_cost_periods.generate', $period) }}" method="POST"
                        onsubmit="return confirm('Generate ulang HPP dari payroll untuk periode ini?\nIni akan membuat snapshot baru per item.');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success">
                            Re-generate HPP
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Info payroll yang ter-attach --}}
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="card card-main h-100">
                    <div class="card-body py-2 px-3">
                        <div class="small text-muted mb-1">Payroll Cutting</div>
                        @if ($period->cuttingPayrollPeriod)
                            <div class="fw-semibold">
                                {{ $period->cuttingPayrollPeriod->name ?? 'Cutting #' . $period->cuttingPayrollPeriod->id }}
                            </div>
                            <div class="small text-muted">
                                {{ optional($period->cuttingPayrollPeriod->date_from)->format('d/m/Y') }} –
                                {{ optional($period->cuttingPayrollPeriod->date_to)->format('d/m/Y') }}
                            </div>
                        @else
                            <div class="small text-muted">
                                Belum di-link ke payroll Cutting.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-main h-100">
                    <div class="card-body py-2 px-3">
                        <div class="small text-muted mb-1">Payroll Sewing</div>
                        @if ($period->sewingPayrollPeriod)
                            <div class="fw-semibold">
                                {{ $period->sewingPayrollPeriod->name ?? 'Sewing #' . $period->sewingPayrollPeriod->id }}
                            </div>
                            <div class="small text-muted">
                                {{ optional($period->sewingPayrollPeriod->date_from)->format('d/m/Y') }} –
                                {{ optional($period->sewingPayrollPeriod->date_to)->format('d/m/Y') }}
                            </div>
                        @else
                            <div class="small text-muted">
                                Belum di-link ke payroll Sewing.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-main h-100">
                    <div class="card-body py-2 px-3">
                        <div class="small text-muted mb-1">Payroll Finishing</div>
                        @if ($period->finishingPayrollPeriod)
                            <div class="fw-semibold">
                                {{ $period->finishingPayrollPeriod->name ?? 'Finishing #' . $period->finishingPayrollPeriod->id }}
                            </div>
                            <div class="small text-muted">
                                {{ optional($period->finishingPayrollPeriod->date_from)->format('d/m/Y') }} –
                                {{ optional($period->finishingPayrollPeriod->date_to)->format('d/m/Y') }}
                            </div>
                        @else
                            <div class="small text-muted">
                                Belum di-link ke payroll Finishing.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabel snapshot HPP per item --}}
        <div class="card card-main">
            <div class="card-header py-2 px-3 d-flex justify-content-between align-items-center">
                <div>
                    <strong>Snapshot HPP per Item</strong>
                    <div class="small text-muted">
                        Diambil dari <code>item_cost_snapshots</code> dengan reference_type = production_cost_period.
                    </div>
                </div>
                <div class="small text-muted">
                    Total item: {{ $snapshots->count() }}
                </div>
            </div>

            <div class="table-responsive" style="max-height: 460px;">
                <table class="table table-sm mb-0 table-sticky align-middle">
                    <thead class="table-light small">
                        <tr>
                            <th>Item</th>
                            <th class="text-end">Qty Basis</th>
                            <th class="text-end">RM / unit</th>
                            <th class="text-end">Cutting / unit</th>
                            <th class="text-end">Sewing / unit</th>
                            <th class="text-end">Finishing / unit</th>
                            <th class="text-end">Packaging</th>
                            <th class="text-end">Overhead</th>
                            <th class="text-end">Total HPP / unit</th>
                            <th class="text-center">Aktif?</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @php
                            $grandQty = 0;
                            $grandTotalCost = 0;
                        @endphp

                        @forelse ($snapshots as $s)
                            @php
                                $unitCost =
                                    $s->unit_cost ??
                                    $s->rm_unit_cost +
                                        $s->cutting_unit_cost +
                                        $s->sewing_unit_cost +
                                        $s->finishing_unit_cost +
                                        $s->packaging_unit_cost +
                                        $s->overhead_unit_cost;

                                $grandQty += $s->qty_basis;
                                $grandTotalCost += $unitCost * $s->qty_basis;
                            @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">
                                        {{ $s->item->code ?? 'Item #' . $s->item_id }}
                                    </div>
                                    <div class="text-muted">
                                        {{ $s->item->name ?? '-' }}
                                    </div>
                                </td>
                                <td class="text-end">
                                    {{ $fmt($s->qty_basis, 0) }}
                                </td>
                                <td class="text-end">
                                    {{ $fmt($s->rm_unit_cost ?? 0) }}
                                </td>
                                <td class="text-end">
                                    {{ $fmt($s->cutting_unit_cost ?? 0) }}
                                </td>
                                <td class="text-end">
                                    {{ $fmt($s->sewing_unit_cost ?? 0) }}
                                </td>
                                <td class="text-end">
                                    {{ $fmt($s->finishing_unit_cost ?? 0) }}
                                </td>
                                <td class="text-end">
                                    {{ $fmt($s->packaging_unit_cost ?? 0) }}
                                </td>
                                <td class="text-end">
                                    {{ $fmt($s->overhead_unit_cost ?? 0) }}
                                </td>
                                <td class="text-end fw-semibold">
                                    {{ $fmt($unitCost ?? 0) }}
                                </td>
                                <td class="text-center">
                                    @if ($s->is_active)
                                        <span class="badge-soft text-success border-success-subtle">
                                            Ya
                                        </span>
                                    @else
                                        <span class="badge-soft text-muted border-secondary-subtle">
                                            Tidak
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-3">
                                    Belum ada snapshot HPP untuk periode ini.
                                    <br>
                                    <span class="small">
                                        Klik tombol <strong>Generate HPP</strong> untuk membuat snapshot.
                                    </span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    @if ($snapshots->isNotEmpty())
                        <tfoot class="table-light small">
                            <tr>
                                <th>Rata-rata tertimbang</th>
                                <th class="text-end">
                                    {{ $fmt($grandQty, 0) }}
                                </th>
                                <th colspan="6"></th>
                                <th class="text-end fw-semibold">
                                    {{ $grandQty > 0 ? $fmt($grandTotalCost / $grandQty) : '0,00' }}
                                </th>
                                <th></th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
