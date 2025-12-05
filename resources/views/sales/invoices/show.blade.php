@extends('layouts.app')

@section('title', 'Detail Invoice ' . $invoice->code)

@section('content')
    @php
        $fmt = fn($n, $dec = 0) => number_format($n ?? 0, $dec, ',', '.');
        $shipmentCount = $invoice->shipments?->count() ?? 0;
    @endphp

    <div class="container py-3">

        {{-- HEADER + ACTIONS --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0">Invoice {{ $invoice->code }}</h4>
                <div class="mt-1 d-flex align-items-center gap-2">
                    @if ($invoice->status === 'posted')
                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                            Posted
                        </span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                            Draft
                        </span>
                    @endif

                    {{-- Badge jumlah shipment --}}
                    @if ($shipmentCount > 0)
                        <span class="badge bg-info-subtle text-info border border-info-subtle">
                            {{ $shipmentCount }} Shipment
                        </span>
                    @else
                        <span class="badge bg-light text-muted border border-light">
                            Belum ada Shipment
                        </span>
                    @endif
                </div>
            </div>

            <div class="d-flex gap-2">
                {{-- Kalau sudah posted → bisa buat Shipment --}}
                @if ($invoice->status === 'posted')
                    <a href="{{ route('sales.invoices.shipments.create', $invoice) }}"
                        class="btn btn-sm btn-outline-primary">
                        Buat Shipment
                    </a>
                @endif

                {{-- Kalau belum posted → tampilkan tombol Post Invoice (stock out) --}}
                @if ($invoice->status !== 'posted')
                    <form action="{{ route('sales.invoices.post', $invoice) }}" method="POST"
                        onsubmit="return confirm('Post invoice ini dan keluarkan stok dari gudang?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success">
                            Post Invoice (Stock Out)
                        </button>
                    </form>
                @endif

                <a href="{{ route('sales.invoices.index') }}" class="btn btn-sm btn-outline-secondary">
                    &larr; Kembali
                </a>
            </div>
        </div>

        {{-- FLASH MESSAGES --}}
        @if (session('success'))
            <div class="alert alert-success py-2 px-3 small">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger py-2 px-3 small">{{ session('error') }}</div>
        @endif
        @if (session('info'))
            <div class="alert alert-info py-2 px-3 small">{{ session('info') }}</div>
        @endif

        {{-- HEADER INFO --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-2 small">
                    <div class="col-md-4">
                        <div><strong>Tanggal</strong></div>
                        <div>{{ id_date($invoice->date) }}</div>
                    </div>
                    <div class="col-md-4">
                        <div><strong>Customer</strong></div>
                        <div>{{ $invoice->customer?->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-4">
                        <div><strong>Gudang</strong></div>
                        <div>{{ $invoice->warehouse?->code ?? '-' }} — {{ $invoice->warehouse?->name }}</div>
                    </div>
                    <div class="col-md-12 mt-2">
                        <div><strong>Catatan</strong></div>
                        <div>{{ $invoice->remarks ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ITEMS + MARGIN --}}
        <div class="card mb-3">
            <div class="card-header">
                Items & Perhitungan Margin
            </div>

            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle text-nowrap">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Harga</th>
                            <th class="text-end">Disc</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">HPP/unit</th>
                            <th class="text-end">Margin/unit</th>
                            <th class="text-end">Margin total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalMargin = 0; @endphp
                        @forelse ($invoice->lines as $line)
                            @php $totalMargin += $line->margin_total; @endphp
                            <tr>
                                <td>
                                    {{ $line->item?->code ?? '-' }}<br>
                                    <small class="text-muted">{{ $line->item?->name ?? '' }}</small>
                                </td>
                                <td class="text-end">{{ $fmt($line->qty) }}</td>
                                <td class="text-end">{{ $fmt($line->unit_price) }}</td>
                                <td class="text-end">{{ $fmt($line->line_discount) }}</td>
                                <td class="text-end">{{ $fmt($line->line_total) }}</td>
                                <td class="text-end">{{ $fmt($line->hpp_unit_snapshot) }}</td>
                                <td class="text-end">{{ $fmt($line->margin_unit) }}</td>
                                <td class="text-end fw-semibold">{{ $fmt($line->margin_total) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-3">
                                    Tidak ada item.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- SUMMARY --}}
            <div class="card-footer">
                <div class="row justify-content-end">
                    <div class="col-md-5">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Subtotal</span>
                            <span>{{ $fmt($invoice->subtotal) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Diskon</span>
                            <span>{{ $fmt($invoice->discount_total) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>PPN ({{ $invoice->tax_percent }}%)</span>
                            <span>{{ $fmt($invoice->tax_amount) }}</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between mb-1">
                            <strong>Grand Total</strong>
                            <strong>{{ $fmt($invoice->grand_total) }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span>Total Margin</span>
                            <span class="fw-semibold">{{ $fmt($totalMargin) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- LIST SHIPMENT TERKAIT --}}
        <div class="card">
            <div class="card-header">
                Shipment Terkait Invoice Ini
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle text-nowrap">
                    <thead>
                        <tr>
                            <th>Kode Shipment</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Metode Kirim</th>
                            <th>No. Resi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoice->shipments as $shp)
                            <tr>
                                <td>
                                    <a href="{{ route('sales.shipments.show', $shp) }}">
                                        {{ $shp->code }}
                                    </a>
                                </td>
                                <td>{{ id_date($shp->date) }}</td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                        {{ ucfirst($shp->status) }}
                                    </span>
                                </td>
                                <td>{{ $shp->shipping_method ?? '-' }}</td>
                                <td>{{ $shp->tracking_no ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">
                                    Belum ada shipment yang terkait invoice ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
