{{-- resources/views/inventory/stocks/lots.blade.php --}}
@extends('layouts.app')

@section('title', 'Inventory • Stok per LOT')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .badge-link {
            font-size: .75rem;
            padding: .2rem .45rem;
        }
    </style>

    {{-- Flatpickr CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
@endpush

@section('content')
    <div class="page-wrap py-3 py-md-4 container py-3">

        {{-- Header + Tabs --}}
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
            <div>
                <h5 class="mb-1">
                    🎫 Stok per LOT
                </h5>
                <div class="text-muted small">
                    Daftar LOT dengan saldo &gt; 0 per gudang (dari <code>inventory_mutations</code>).
                </div>
            </div>

            <ul class="nav nav-pills small">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('inventory.stocks.items') }}">
                        📦 Item
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="{{ route('inventory.stocks.lots') }}">
                        🎫 LOT
                    </a>
                </li>
            </ul>
        </div>

        {{-- Filter Card --}}
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end filter-row">
                    <div class="col-6 col-md-3">
                        <label for="warehouse_id" class="form-label">Gudang</label>
                        <select name="warehouse_id" id="warehouse_id" class="form-select form-select-sm">
                            <option value="">Semua Gudang</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}" @selected(($filters['warehouse_id'] ?? null) == $wh->id)>
                                    {{ $wh->code }} — {{ $wh->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-3">
                        <label for="item_id" class="form-label">Item</label>
                        <select name="item_id" id="item_id" class="form-select form-select-sm">
                            <option value="">Semua Item</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}" @selected(($filters['item_id'] ?? null) == $item->id)>
                                    {{ $item->code }} — {{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-3">
                        <label for="lot_search" class="form-label">Cari LOT</label>
                        <input type="text" name="lot_search" id="lot_search" value="{{ $filters['lot_search'] ?? '' }}"
                            class="form-control form-control-sm" placeholder="LOT-2025-...">
                    </div>

                    <div class="col-6 col-md-3">
                        <label for="item_search" class="form-label">Cari Item (kode/nama)</label>
                        <input type="text" name="item_search" id="item_search"
                            value="{{ $filters['item_search'] ?? '' }}" class="form-control form-control-sm"
                            placeholder="FLC / J7 / RIB...">
                    </div>

                    <div class="col-12 d-flex justify-content-md-end gap-2 mt-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>
                        <a href="{{ route('inventory.stocks.lots') }}" class="btn btn-outline-secondary btn-sm">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- Summary --}}
        <div class="mb-2 small text-muted">
            Menampilkan {{ $lotStocks->count() }} LOT dengan saldo &gt; 0.
        </div>

        {{-- Table Card --}}
        <div class="card">
            <div class="card-body p-0">
                <div class="table-wrap">
                    <table class="table table-sm table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr class="text-nowrap">
                                <th style="width: 1%">#</th>
                                <th>Gudang</th>
                                <th>Kode LOT</th>
                                <th>Kode Item</th>
                                <th>Nama Item</th>
                                <th class="text-end">Saldo LOT</th>
                                <th style="width: 1%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lotStocks as $i => $row)
                                @php
                                    $lot = $row->lot;
                                    $item = $lot?->item;
                                    $wh = $row->warehouse;
                                @endphp
                                <tr>
                                    <td class="text-muted small">
                                        {{ $i + 1 }}
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill bg-light text-dark">
                                            {{ $wh?->code ?? '-' }}
                                        </span>
                                        <div class="small text-muted">
                                            {{ $wh?->name }}
                                        </div>
                                    </td>
                                    <td class="mono">
                                        @if ($lot && $item && $wh)
                                            {{-- Link ke Stock Card per LOT --}}
                                            <a href="{{ route('inventory.stock_card.index', [
                                                'item_id' => $item->id,
                                                'warehouse_id' => $wh->id,
                                                'lot_id' => $lot->id,
                                            ]) }}"
                                                class="link-underline link-underline-opacity-0">
                                                {{ $lot->code }}
                                            </a>
                                        @else
                                            {{ $lot?->code ?? '-' }}
                                        @endif
                                    </td>
                                    <td class="mono">
                                        @if ($item && $wh)
                                            {{-- Link ke Stock Card per item + gudang (tanpa filter LOT) --}}
                                            <a href="{{ route('inventory.stock_card.index', [
                                                'item_id' => $item->id,
                                                'warehouse_id' => $wh->id,
                                            ]) }}"
                                                class="link-underline link-underline-opacity-0">
                                                {{ $item->code }}
                                            </a>
                                        @else
                                            {{ $item?->code ?? '-' }}
                                        @endif
                                    </td>
                                    <td>
                                        {{ $item?->name ?? '-' }}
                                    </td>
                                    <td class="text-end mono">
                                        {{ number_format($row->qty_balance, 2) }}
                                    </td>
                                    <td class="text-end">
                                        @if ($lot && $item && $wh)
                                            <a href="{{ route('inventory.stock_card.index', [
                                                'item_id' => $item->id,
                                                'warehouse_id' => $wh->id,
                                                'lot_id' => $lot->id,
                                            ]) }}"
                                                class="btn btn-outline-secondary btn-sm py-0 px-2"
                                                title="Lihat Kartu Stok LOT">
                                                <i class="bi bi-journal-text"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        Tidak ada LOT dengan saldo &gt; 0 yang cocok dengan filter.
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
