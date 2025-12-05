@extends('layouts.app')

@section('title', 'Shipments')

@section('content')
    @php
        $fmt = fn($n) => number_format($n ?? 0, 0, ',', '.');
    @endphp

    <div class="container py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Shipments</h4>
            <a href="{{ route('sales.shipments.create') }}" class="btn btn-sm btn-primary">
                + Shipment Manual
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success py-2 px-3 small">
                {{ session('success') }}
            </div>
        @endif

        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr class="text-nowrap">
                            <th>Kode</th>
                            <th>Tanggal</th>
                            <th>Invoice</th>
                            <th>Customer</th>
                            <th>Gudang</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($shipments as $shp)
                            <tr class="text-nowrap">
                                <td>
                                    <a href="{{ route('sales.shipments.show', $shp) }}">
                                        {{ $shp->code }}
                                    </a>
                                </td>
                                <td>{{ id_date($shp->date) }}</td>
                                <td>{{ $shp->invoice?->code ?? '-' }}</td>
                                <td>{{ $shp->customer?->name ?? '-' }}</td>
                                <td>{{ $shp->warehouse?->code ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                        {{ ucfirst($shp->status) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">
                                    Belum ada shipment.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($shipments->hasPages())
                <div class="card-footer">
                    {{ $shipments->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
