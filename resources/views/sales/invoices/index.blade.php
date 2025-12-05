@extends('layouts.app')

@section('title', 'Sales Invoices')

@section('content')
    <div class="container py-3">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Sales Invoices</h4>
            <a href="{{ route('sales.invoices.create') }}" class="btn btn-primary btn-sm">
                + Invoice Baru
            </a>
        </div>

        {{-- Alerts --}}
        @if (session('success'))
            <div class="alert alert-success py-2 px-3 small">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger py-2 px-3 small">{{ session('error') }}</div>
        @endif

        @if (session('info'))
            <div class="alert alert-info py-2 px-3 small">{{ session('info') }}</div>
        @endif

        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Tanggal</th>
                            <th>Customer</th>
                            <th>Gudang</th>
                            <th>Status</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoices as $inv)
                            <tr>
                                <td>
                                    <a href="{{ route('sales.invoices.show', $inv) }}">
                                        {{ $inv->code }}
                                    </a>
                                </td>
                                <td>{{ id_date($inv->date) }}</td>
                                <td>{{ $inv->customer?->name ?? '-' }}</td>
                                <td>{{ $inv->warehouse?->code ?? '-' }}</td>

                                <td>
                                    @if ($inv->status === 'posted')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            Posted
                                        </span>
                                    @else
                                        <span
                                            class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                            Draft
                                        </span>
                                    @endif
                                </td>

                                <td class="text-end">
                                    {{ number_format($inv->grand_total, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">
                                    Belum ada invoice.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($invoices->hasPages())
                <div class="card-footer">
                    {{ $invoices->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection
