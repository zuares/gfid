{{-- resources/views/production/packing_jobs/ready_items.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Ready Items (WH-PRD)')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding-block: .75rem 2rem;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .table-wrap {
            overflow-x: auto;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .5rem;
            }

            .table-wrap {
                font-size: .84rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="card p-3 mb-3">
            <h1 class="fs-5 fw-semibold mb-1">Ready Items • WH-PRD</h1>
            <div class="text-muted small">
                Lihat stok yang siap di-packing, total yg sudah packing, dan sisa available.
            </div>
        </div>

        {{-- SEARCH --}}
        <div class="card p-3 mb-3">
            <form method="get" class="row g-2">
                <div class="col-md-6">
                    <input type="text" name="q" class="form-control form-control-sm"
                        placeholder="Cari kode / nama / warna item..." value="{{ request('q') }}">
                </div>

                <div class="col-md-2 d-grid">
                    <button class="btn btn-sm btn-primary">Cari</button>
                </div>

                <div class="col-md-2 d-grid">
                    <a href="{{ route('production.packing_jobs.ready_items') }}"
                        class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>

        {{-- TABLE --}}
        <div class="card p-3">
            <div class="table-wrap">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th class="text-end">Stok WH-PRD</th>
                            <th class="text-end">Sudah Packing</th>
                            <th class="text-end">Available</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($stocks as $i => $stock)
                            @php
                                $item = $stock->item;
                                $stok = (float) $stock->qty;
                                $packed = (float) ($packedQtyByItem[$stock->item_id] ?? 0);
                                $available = max(0, $stok - $packed);

                                $label = $item
                                    ? trim(
                                        ($item->code ?? '') . ' — ' . ($item->name ?? '') . ' ' . ($item->color ?? ''),
                                    )
                                    : 'ITEM-' . $stock->item_id;
                            @endphp

                            <tr>
                                <td class="text-muted small mono">{{ $stocks->firstItem() + $i }}</td>

                                <td>
                                    <div class="fw-semibold small">{{ $label }}</div>
                                </td>

                                <td class="text-end mono">
                                    {{ number_format($stok) }}
                                </td>

                                <td class="text-end mono text-primary">
                                    {{ number_format($packed) }}
                                </td>

                                <td class="text-end mono text-success fw-semibold">
                                    {{ number_format($available) }}
                                </td>

                                <td class="text-end">
                                    @if ($available > 0)
                                        <form method="get" action="{{ route('production.packing_jobs.create') }}">
                                            <input type="hidden" name="item_ids[]" value="{{ $stock->item_id }}">
                                            <button class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-plus-circle me-1"></i> Tambah
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-muted small">Habis</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach

                        @if ($stocks->isEmpty())
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">
                                    Tidak ada item dalam stok WH-PRD.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div class="mt-3">
                {{ $stocks->links() }}
            </div>
        </div>

    </div>
@endsection
