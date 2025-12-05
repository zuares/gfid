@extends('layouts.app')

@section('title', 'Buat Shipment dari Invoice ' . $invoice->code)

@section('content')
    @php
        $fmt = fn($n) => number_format($n ?? 0, 0, ',', '.');
    @endphp

    <div class="container py-3">
        <h4 class="mb-3">Buat Shipment dari Invoice {{ $invoice->code }}</h4>

        @if ($errors->any())
            <div class="alert alert-danger py-2 px-3 small">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('sales.shipments.store') }}" method="POST">
            @csrf

            <input type="hidden" name="sales_invoice_id" value="{{ $invoice->id }}">
            <input type="hidden" name="customer_id" value="{{ $invoice->customer_id }}">
            <input type="hidden" name="warehouse_id" value="{{ $invoice->warehouse_id }}">

            <div class="card mb-3">
                <div class="card-body small">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label">Tanggal Shipment</label>
                            <input type="date" name="date" class="form-control form-control-sm"
                                value="{{ now()->toDateString() }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Metode Kirim</label>
                            <input type="text" name="shipping_method" class="form-control form-control-sm"
                                placeholder="JNE, J&T, Kurir, dll">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">No. Resi</label>
                            <input type="text" name="tracking_no" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Alamat Kirim</label>
                            <textarea name="shipping_address" rows="2" class="form-control form-control-sm"
                                placeholder="Alamat kirim (boleh dikosongkan, akan pakai alamat default customer kalau nanti ada)">
{{ old('shipping_address') }}</textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Catatan</label>
                            <input type="text" name="remarks" class="form-control form-control-sm"
                                placeholder="Catatan shipment (opsional)">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ITEMS --}}
            <div class="card">
                <div class="card-header">
                    Item yang akan dikirim
                </div>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead>
                            <tr class="text-nowrap">
                                <th>Item</th>
                                <th class="text-end">Qty Invoice</th>
                                <th class="text-end">Qty Kirim</th>
                                <th>Scan Code (opsional)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoice->lines as $idx => $line)
                                <tr class="text-nowrap">
                                    <td>
                                        {{ $line->item?->code ?? '-' }}<br>
                                        <span class="text-muted small">{{ $line->item?->name }}</span>
                                    </td>
                                    <td class="text-end">{{ $fmt($line->qty) }}</td>
                                    <td class="text-end">
                                        <input type="hidden" name="items[{{ $idx }}][item_id]"
                                            value="{{ $line->item_id }}">
                                        <input type="hidden" name="items[{{ $idx }}][sales_invoice_line_id]"
                                            value="{{ $line->id }}">
                                        <input type="number" min="0" max="{{ $line->qty }}"
                                            name="items[{{ $idx }}][qty]"
                                            class="form-control form-control-sm text-end" value="{{ $line->qty }}">
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $idx }}][scan_code]"
                                            class="form-control form-control-sm" placeholder="Scan / ketik kode (opsional)">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="card-footer d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary btn-sm">
                        Simpan Shipment
                    </button>
                </div>
            </div>

        </form>
    </div>
@endsection
