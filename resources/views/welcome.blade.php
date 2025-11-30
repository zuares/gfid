<h1>
    Laravel Application is Running!
</h1>
@extends('layouts.app')

@section('title', 'UI Demo • Komponen')

@push('head')
    <style>
        .demo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- ============================
         HEADER
    ============================= --}}
        <div class="mb-4">
            <h3 class="fw-bold">UI Demo (Light & Dark Friendly)</h3>
            <p class="text-muted">Contoh semua komponen di satu halaman.</p>
        </div>

        <div>
            <h5>Belajar Komponen Blade di Laravel</h1>

                <x-alert message="Ini halaman home" type="danger" />
        </div>
        {{-- ============================
         BUTTON SECTION
    ============================= --}}
        <div class="card mb-4">
            <div class="card-header fw-semibold">
                Buttons
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">

                    <button class="btn btn-primary">Primary</button>
                    <button class="btn btn-outline-primary">Outline</button>
                    <button class="btn btn-soft">Soft</button>

                    <button class="btn btn-danger">Danger</button>
                    <button class="btn btn-outline-danger">Outline Danger</button>

                    <button class="btn btn-success">Success</button>
                    <button class="btn btn-outline-success">Outline Success</button>

                </div>
            </div>
        </div>

        {{-- ============================
         CARD SECTION
    ============================= --}}
        <div class="card mb-4">
            <div class="card-header fw-semibold">
                Card & Typography
            </div>
            <div class="card-body">
                <p>Contoh paragraf normal. Warna text ini otomatis menyesuaikan tema.</p>

                <p class="muted">Ini warna muted (biasa dipakai untuk informasi kecil).</p>

                <div class="tag-soft mb-2">
                    <span>Tag Soft</span>
                </div>

                <p class="help">Help text / keterangan kecil</p>
            </div>
        </div>

        {{-- ============================
         FORM INPUTS
    ============================= --}}
        <div class="card mb-4">
            <div class="card-header fw-semibold">Form Elements</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Item</label>
                        <input type="text" class="form-control" placeholder="Contoh: K7BLK">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Qty</label>
                        <input type="number" class="form-control" value="10">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Harga</label>
                        <input type="number" class="form-control" value="15000">
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Catatan</label>
                        <textarea class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================
         TABLE
    ============================= --}}
        <div class="card mb-4">
            <div class="card-header fw-semibold">
                Table Example
            </div>

            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode</th>
                            <th>Nama</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Harga</th>
                            <th class="text-end">Total</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td>1</td>
                            <td>K7BLK</td>
                            <td>Jogger 7XL Black</td>
                            <td class="text-end">20</td>
                            <td class="text-end mono">15.000</td>
                            <td class="text-end mono">300.000</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary">Edit</button>
                            </td>
                        </tr>

                        <tr>
                            <td>2</td>
                            <td>LJRNVY</td>
                            <td>Jogger Long Navy</td>
                            <td class="text-end">15</td>
                            <td class="text-end mono">17.500</td>
                            <td class="text-end mono">262.500</td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary">Edit</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ============================
         ALERT SECTION
    ============================= --}}
        <div class="card mb-4">
            <div class="card-header fw-semibold">
                Alerts
            </div>

            <div class="card-body d-flex flex-column gap-2">
                <div class="alert alert-success">
                    Berhasil menambahkan data!
                </div>

                <div class="alert alert-danger">
                    Gagal memproses transaksi.
                </div>

                <div class="alert alert-warning">
                    Stok hampir habis.
                </div>
            </div>
        </div>

        {{-- ============================
         BADGE / CHIP SECTION
    ============================= --}}
        <div class="card mb-4">
            <div class="card-header fw-semibold">
                Badge / Chip Colors
            </div>

            <div class="card-body d-flex flex-wrap gap-2">

                <div class="tag-soft">Default / Accent</div>

                <div class="tag-soft" style="background: var(--success-soft); color: var(--success);">
                    Success
                </div>

                <div class="tag-soft" style="background: var(--danger-soft); color: var(--danger);">
                    Danger
                </div>

                <div class="tag-soft" style="background: var(--accent-soft); color: var(--accent);">
                    Info
                </div>
            </div>
        </div>

    </div>
@endsection
