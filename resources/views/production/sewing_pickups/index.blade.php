@extends('layouts.app')

@section('title', 'Produksi • Sewing Pickup')

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
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .help {
            color: var(--muted);
            font-size: .85rem;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .15rem .5rem;
            font-size: .7rem;
        }

        .table-wrap {
            overflow-x: auto;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h5 mb-1">Sewing Pickup</h1>
                    <div class="help">
                        Daftar semua pengambilan bundle dari WIP Cutting ke gudang sewing.
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('production.sewing_pickups.bundles_ready') }}" class="btn btn-sm btn-outline-secondary">
                        Bundles Ready
                    </a>
                    <a href="{{ route('production.sewing_pickups.create') }}" class="btn btn-sm btn-primary">
                        + Sewing Pickup
                    </a>
                </div>
            </div>
        </div>

        {{-- FLASH MESSAGE --}}
        @if (session('success'))
            <div class="alert alert-success py-2">
                {{ session('success') }}
            </div>
        @endif

        {{-- TABLE --}}
        <div class="card p-3">
            <h2 class="h6 mb-2">Daftar Sewing Pickup</h2>

            <div class="table-wrap">
                <table class="table table-sm align-middle mono">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="width: 130px;">Code</th>
                            <th style="width: 100px;">Tanggal</th>
                            <th style="width: 150px;">Gudang Sewing</th>
                            <th style="width: 170px;">Operator Jahit</th>
                            <th style="width: 150px;">Bundles (Qty)</th>
                            <th style="width: 110px;">Status</th>
                            <th style="width: 90px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pickups as $pickup)
                            @php
                                $totalBundles = $pickup->lines->count();
                                $totalQty = $pickup->lines->sum('qty_bundle');

                                $statusMap = [
                                    'draft' => ['label' => 'DRAFT', 'class' => 'secondary'],
                                    'posted' => ['label' => 'POSTED', 'class' => 'primary'],
                                    'closed' => ['label' => 'CLOSED', 'class' => 'success'],
                                ];

                                $cfg = $statusMap[$pickup->status] ?? [
                                    'label' => strtoupper($pickup->status ?? '-'),
                                    'class' => 'secondary',
                                ];
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration + ($pickups->currentPage() - 1) * $pickups->perPage() }}</td>
                                <td>{{ $pickup->code }}</td>
                                <td>{{ $pickup->date?->format('Y-m-d') ?? $pickup->date }}</td>
                                <td>
                                    {{ $pickup->warehouse?->code ?? '-' }}
                                    @if ($pickup->warehouse)
                                        <span class="badge-soft bg-light border text-muted">
                                            {{ $pickup->warehouse->name }}
                                        </span>
                                    @endif
                                </td>
                                <td>
                                    @if ($pickup->operator)
                                        {{ $pickup->operator->code }} — {{ $pickup->operator->name }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    {{ $totalBundles }} bundle /
                                    {{ number_format($totalQty, 2, ',', '.') }} pcs
                                </td>
                                <td>
                                    <span class="badge bg-{{ $cfg['class'] }}">
                                        {{ $cfg['label'] }}
                                    </span>
                                </td>
                                <td>
                                    @if (Route::has('production.sewing_pickups.show'))
                                        <a href="{{ route('production.sewing_pickups.show', $pickup) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            Detail
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted small">
                                    Belum ada Sewing Pickup.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($pickups instanceof \Illuminate\Pagination\AbstractPaginator)
                <div class="mt-2">
                    {{ $pickups->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection
