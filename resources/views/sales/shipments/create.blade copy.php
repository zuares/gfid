@extends('layouts.app')

@section('title', 'Buat Shipment Baru')

@push('head')
    <style>
        .page-wrap {
            max-width: 1150px;
            margin-inline: auto;
            padding: .75rem .75rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.12) 0,
                    rgba(45, 212, 191, 0.10) 26%,
                    #f9fafb 60%);
        }

        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            box-shadow:
                0 10px 30px rgba(15, 23, 42, 0.10),
                0 0 0 1px rgba(148, 163, 184, 0.08);
        }

        .meta-label {
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #6b7280;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <div class="meta-label mb-1">
                    BUAT SHIPMENT BARU
                </div>
                <h1 class="h4 mb-1">
                    Shipment Baru
                </h1>
                <div class="small text-muted">
                    Isi informasi dasar lalu sistem akan mengarahkan ke halaman scan.
                </div>
            </div>

            <div class="text-end">
                <a href="{{ route('sales.shipments.index') }}" class="btn btn-sm btn-outline-secondary">
                    &larr; Kembali ke list
                </a>
            </div>
        </div>

        {{-- Error --}}
        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-1">Terjadi kesalahan:</div>
                <ul class="mb-0 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Jika datang dari Invoice --}}
        @if (!empty($invoice))
            <div class="card mb-3">
                <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <div class="meta-label mb-1">SHIPMENT UNTUK INVOICE</div>
                        <div class="fw-semibold">
                            {{ $invoice->invoice_no ?? 'INV-' . $invoice->id }}
                        </div>
                        <div class="small text-muted">
                            Store:
                            {{ $invoice->store->code ?? '-' }} — {{ $invoice->store->name ?? '-' }}<br>
                            Tanggal Invoice:
                            {{ optional($invoice->date)->format('d M Y') ?? '-' }}
                        </div>
                    </div>

                    <div class="text-end">
                        <div class="small text-muted mb-1">Status Invoice</div>
                        <span
                            class="badge rounded-pill
                            @if ($invoice->status === 'posted') bg-success-subtle text-success
                            @elseif($invoice->status === 'draft') bg-secondary-subtle text-secondary
                            @else bg-warning-subtle text-warning @endif">
                            {{ strtoupper($invoice->status) }}
                        </span>
                    </div>
                </div>
            </div>
        @endif

        {{-- Form utama --}}
        <div class="card card-main">
            <div class="card-body">
                <form action="{{ route('sales.shipments.store') }}" method="POST">
                    @csrf

                    {{-- kalau dari invoice, kirim sales_invoice_id --}}
                    @if (!empty($invoice))
                        <input type="hidden" name="sales_invoice_id" value="{{ $invoice->id }}">
                    @endif

                    <div class="row g-3">
                        {{-- Tanggal --}}
                        <div class="col-md-4">
                            <label class="meta-label mb-1 d-block">Tanggal</label>
                            <input type="date" name="date" class="form-control form-control-sm"
                                value="{{ old('date', now()->toDateString()) }}" required>
                        </div>

                        {{-- Gudang (WH-RTS) --}}
                        <div class="col-md-4">
                            <label class="meta-label mb-1 d-block">Gudang</label>

                            <select class="form-select form-select-sm" disabled>
                                @if ($whRts)
                                    <option selected>
                                        {{ $whRts->code }} — {{ $whRts->name }}
                                    </option>
                                @else
                                    <option selected>Gudang WH-RTS tidak ditemukan</option>
                                @endif
                            </select>

                            {{-- dikirim ke server via hidden (kalau suatu saat perlu) --}}
                            @if ($whRts)
                                <input type="hidden" name="warehouse_id" value="{{ $whRts->id }}">
                            @endif

                            <div class="small text-muted mt-1">
                                Shipment selalu keluar dari gudang WH-RTS.
                            </div>
                        </div>

                        {{-- Store / Channel --}}
                        <div class="col-md-4">
                            <label class="meta-label mb-1 d-block">Channel / Store</label>
                            <select name="store_id" class="form-select form-select-sm">
                                <option value="">– Pilih Store –</option>
                                @foreach ($stores as $store)
                                    <option value="{{ $store->id }}" @selected(old('store_id', !empty($invoice) ? $invoice->store_id : null) == $store->id)>
                                        {{ $store->code }} — {{ $store->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="small text-muted mt-1">
                                @if (!empty($invoice))
                                    Otomatis dipilih sesuai store di invoice, bisa diubah jika perlu.
                                @else
                                    Opsional, pilih store/akun marketplace terkait.
                                @endif
                            </div>
                        </div>

                        {{-- Catatan --}}
                        <div class="col-12">
                            <label class="meta-label mb-1 d-block">Catatan</label>
                            <textarea name="notes" rows="3" class="form-control form-control-sm" placeholder="Catatan tambahan (opsional)">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    <div class="mt-4 d-flex justify-content-end gap-2">
                        <a href="{{ route('sales.shipments.index') }}" class="btn btn-outline-secondary btn-sm">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-primary btn-sm">
                            Simpan &amp; Mulai Scan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
