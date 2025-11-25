@extends('layouts.app')

@section('title', 'Produksi • Sewing Returns')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .help {
            color: var(--muted);
            font-size: .85rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .15rem .5rem;
            font-size: .7rem;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h5 mb-1">Sewing Returns</h1>
                    <div class="help">
                        Daftar pengembalian hasil jahit (OK & Reject) dari gudang sewing ke WIP-FIN / REJECT.
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    {{-- Filter status sederhana (opsional) --}}
                    <form method="get" class="d-flex align-items-center gap-2">
                        <div class="help mb-0">Status</div>
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            @php
                                $currentStatus = $filters['status'] ?? '';
                            @endphp
                            <option value="">Semua</option>
                            <option value="draft" {{ $currentStatus === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="posted" {{ $currentStatus === 'posted' ? 'selected' : '' }}>Posted</option>
                            <option value="closed" {{ $currentStatus === 'closed' ? 'selected' : '' }}>Closed</option>
                        </select>
                    </form>
                </div>
            </div>
        </div>

        {{-- TABEL LIST --}}
        <div class="card p-3">
            <div class="table-wrap">
                <table class="table table-sm align-middle mono">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="width: 130px;">Return Code</th>
                            <th style="width: 110px;">Tanggal</th>
                            <th style="width: 130px;">Pickup</th>
                            <th style="width: 160px;">Gudang Sewing</th>
                            <th style="width: 170px;">Operator Jahit</th>
                            <th style="width: 130px;">Bundles</th>
                            <th style="width: 130px;">Qty OK</th>
                            <th style="width: 130px;">Qty Reject</th>
                            <th style="width: 100px;">Status</th>
                            <th style="width: 90px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($returns as $ret)
                            @php
                                $lines = $ret->lines;
                                $totalBundles = $lines->count();
                                $qtyOk = $lines->sum('qty_ok');
                                $qtyReject = $lines->sum('qty_reject');

                                $firstLine = $lines->first();
                                $pickupLine = $firstLine?->pickupLine;
                                $pickup = $pickupLine?->pickup;
                                $warehouse = $ret->warehouse ?? $pickup?->warehouse;

                                $statusMap = [
                                    'draft' => ['DRAFT', 'secondary'],
                                    'posted' => ['POSTED', 'primary'],
                                    'closed' => ['CLOSED', 'success'],
                                ];
                                $cfg = $statusMap[$ret->status] ?? [strtoupper($ret->status ?? '-'), 'secondary'];
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration + ($returns->currentPage() - 1) * $returns->perPage() }}</td>
                                <td>{{ $ret->code }}</td>
                                <td>{{ $ret->date?->format('Y-m-d') ?? $ret->date }}</td>
                                <td>{{ $pickup?->code ?? '-' }}</td>
                                <td>
                                    {{ $warehouse?->code ?? '-' }}
                                    @if ($warehouse)
                                        <span class="badge-soft bg-light border text-muted">
                                            {{ $warehouse->name }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($ret->operator)
                                        {{ $ret->operator->code }} — {{ $ret->operator->name }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $totalBundles }} bundle</td>
                                <td>{{ number_format($qtyOk, 2, ',', '.') }}</td>
                                <td>{{ number_format($qtyReject, 2, ',', '.') }}</td>
                                <td>
                                    <span class="badge bg-{{ $cfg[1] }}">
                                        {{ $cfg[0] }}
                                    </span>
                                </td>
                                <td>
                                    @if (Route::has('production.sewing_returns.show'))
                                        <a href="{{ route('production.sewing_returns.show', $ret) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Detail
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center text-muted small">
                                    Belum ada Sewing Return yang tercatat.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($returns instanceof \Illuminate\Pagination\AbstractPaginator)
                <div class="mt-2">
                    {{ $returns->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection
