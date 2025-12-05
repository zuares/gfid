@extends('layouts.app')

@section('title', 'Detail Shipment ' . $shipment->code)

@section('content')
    @php
        $fmt = fn($n) => number_format($n ?? 0, 0, ',', '.');
    @endphp

    <div class="container py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-0">Shipment {{ $shipment->code }}</h4>
                <div class="mt-1 d-flex align-items-center gap-2">
                    {{-- Badge status --}}
                    @if ($shipment->status === 'shipped')
                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                            {{ ucfirst($shipment->status) }}
                        </span>
                    @else
                        <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                            {{ ucfirst($shipment->status) }}
                        </span>
                    @endif

                    {{-- Badge info Invoice terkait --}}
                    @if ($shipment->invoice)
                        <a href="{{ route('sales.invoices.show', $shipment->invoice) }}"
                            class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none">
                            Invoice: {{ $shipment->invoice->code }}
                        </a>
                    @else
                        <span class="badge bg-light text-muted border border-light">
                            Tidak terhubung ke Invoice
                        </span>
                    @endif
                </div>
            </div>

            <div class="d-flex gap-2">
                {{-- Tombol ship (stock out) kalau belum shipped --}}
                @if ($shipment->status !== 'shipped')
                    <form action="{{ route('sales.shipments.ship', $shipment) }}" method="POST"
                        onsubmit="return confirm('Konfirmasi barang sudah dikirim & stok akan berkurang dari gudang?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success">
                            Mark as Shipped (Stock Out)
                        </button>
                    </form>
                @endif

                <a href="{{ route('sales.shipments.index') }}" class="btn btn-sm btn-outline-secondary">
                    &larr; Kembali
                </a>
            </div>
        </div>

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="alert alert-success py-2 px-3 small">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger py-2 px-3 small">
                {{ session('error') }}
            </div>
        @endif

        @if (session('info'))
            <div class="alert alert-info py-2 px-3 small">
                {{ session('info') }}
            </div>
        @endif

        <div class="card mb-3">
            <div class="card-body small">
                <div class="row g-2">
                    <div class="col-md-3">
                        <div><strong>Tanggal</strong></div>
                        <div>{{ id_date($shipment->date) }}</div>
                    </div>
                    <div class="col-md-3">
                        <div><strong>Invoice</strong></div>
                        <div>{{ $shipment->invoice?->code ?? '-' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div><strong>Customer</strong></div>
                        <div>{{ $shipment->customer?->name ?? '-' }}</div>
                    </div>
                    <div class="col-md-3">
                        <div><strong>Gudang</strong></div>
                        <div>{{ $shipment->warehouse?->code ?? '-' }}</div>
                    </div>
                    <div class="col-md-3 mt-2">
                        <div><strong>Metode Kirim</strong></div>
                        <div>{{ $shipment->shipping_method ?? '-' }}</div>
                    </div>
                    <div class="col-md-3 mt-2">
                        <div><strong>Resi</strong></div>
                        <div>{{ $shipment->tracking_no ?? '-' }}</div>
                    </div>
                    <div class="col-md-6 mt-2">
                        <div><strong>Alamat Kirim</strong></div>
                        <div>{{ $shipment->shipping_address ?? '-' }}</div>
                    </div>
                    <div class="col-12 mt-2">
                        <div><strong>Catatan</strong></div>
                        <div>{{ $shipment->remarks ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- DETAIL ITEMS --}}
        <div class="card">
            <div class="card-header">
                Item yang Dikirim
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr class="text-nowrap">
                            <th>Item</th>
                            <th class="text-end">Qty</th>
                            <th>Scan Code</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($shipment->lines as $line)
                            <tr class="text-nowrap">
                                <td>
                                    {{ $line->item?->code ?? '-' }}<br>
                                    <span class="text-muted small">{{ $line->item?->name }}</span>
                                </td>
                                <td class="text-end">{{ $fmt($line->qty) }}</td>
                                <td>{{ $line->scan_code ?? '-' }}</td>
                                <td>{{ $line->remarks ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">
                                    Tidak ada item yang dikirim.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
