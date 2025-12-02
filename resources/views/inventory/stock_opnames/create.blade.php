{{-- resources/views/inventory/stock_opnames/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Stock Opname Baru')

@push('head')
    <style>
        .page-wrap {
            max-width: 700px;
            margin-inline: auto;
            padding: .75rem .75rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.10) 0,
                    rgba(45, 212, 191, 0.08) 28%,
                    #f8fafc 60%);
        }

        .card-gfid {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .25);
            box-shadow:
                0 10px 26px rgba(15, 23, 42, .06),
                0 0 0 1px rgba(15, 23, 42, .03);
        }

        .card-gfid .card-header {
            padding: 1rem 1rem .5rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.25);
            background: transparent;
        }

        .card-gfid .card-body {
            padding: .9rem 1rem 1.1rem;
        }

        .page-title {
            font-size: 1.05rem;
            font-weight: 600;
            letter-spacing: .03em;
        }

        .page-subtitle {
            font-size: .82rem;
            color: rgba(148, 163, 184, 1);
        }

        .field-label {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: rgba(148, 163, 184, 1);
            margin-bottom: .15rem;
        }

        .field-label span.required {
            color: rgba(248, 113, 113, 1);
        }

        .field-hint {
            font-size: .74rem;
            color: rgba(148, 163, 184, 1);
            margin-top: .1rem;
        }

        .error-text {
            font-size: .74rem;
            color: rgba(248, 113, 113, 1);
            margin-top: .1rem;
        }

        .btn-primary-gfid {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .4rem .95rem;
            border-radius: 999px;
            border: none;
            background: linear-gradient(135deg,
                    rgba(59, 130, 246, 1),
                    rgba(56, 189, 248, 1));
            color: #0b1120 !important;
            font-size: .84rem;
            font-weight: 600;
            text-decoration: none;
        }

        .btn-ghost-gfid {
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            padding: .38rem .8rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.4);
            background: rgba(15, 23, 42, 0.02);
            color: inherit;
            font-size: .8rem;
            text-decoration: none;
        }

        .mono {
            font-variant-numeric: tabular-nums;
        }

        @media (max-width: 768px) {
            .page-wrap {
                padding-inline: .6rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        <div class="card card-gfid">
            <div class="card-header">
                <div class="d-flex align-items-start justify-content-between gap-2">
                    <div>
                        <div class="page-title">Stock Opname Baru</div>
                        <div class="page-subtitle">
                            Buat sesi perhitungan stok untuk satu gudang.
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('inventory.stock_opnames.index') }}" class="btn-ghost-gfid">
                            ← Kembali
                        </a>
                    </div>
                </div>
            </div>

            <form action="{{ route('inventory.stock_opnames.store') }}" method="POST" class="card-body">
                @csrf

                <div class="row g-3">
                    {{-- Gudang --}}
                    <div class="col-12">
                        <div class="field-label">
                            Gudang <span class="required">*</span>
                        </div>
                        <select name="warehouse_id" class="form-select form-select-sm" required>
                            <option value="">Pilih gudang…</option>
                            @foreach ($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}"
                                    {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                    {{ $warehouse->code }} — {{ $warehouse->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('warehouse_id')
                            <div class="error-text">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Tanggal --}}
                    <div class="col-12 col-md-6">
                        <div class="field-label">
                            Tanggal Opname <span class="required">*</span>
                        </div>
                        <input type="date" name="date" class="form-control form-control-sm"
                            value="{{ old('date', now()->toDateString()) }}" required>
                        @error('date')
                            <div class="error-text">{{ $message }}</div>
                        @enderror
                        <div class="field-hint">
                            Biasanya diisi tanggal fisik opname dilakukan.
                        </div>
                    </div>

                    {{-- Catatan --}}
                    <div class="col-12">
                        <div class="field-label">Catatan</div>
                        <textarea name="notes" class="form-control form-control-sm" rows="3"
                            placeholder="Contoh: Opname akhir bulan gudang WH-PRD.">{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="error-text">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Opsi generate --}}
                    <div class="col-12">
                        <div class="field-label">Pengisian item</div>
                        <div class="form-check">
                            <input type="checkbox" name="auto_generate_lines" value="1" id="auto_generate_lines"
                                class="form-check-input" {{ old('auto_generate_lines', true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="auto_generate_lines" style="font-size: .8rem;">
                                Generate daftar item otomatis dari stok sistem gudang ini.
                            </label>
                        </div>
                        <div class="field-hint">
                            Stok sistem akan diambil sebagai <span class="mono">Qty Sistem</span> dengan 2 angka desimal
                            (contoh: <span class="mono">12.50</span>). Di halaman counting, kamu bisa isi
                            <span class="mono">Qty Fisik</span> dengan format angka yang sama.
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                    <div>
                        <small class="text-muted">
                            Setelah sesi dibuat, masuk ke halaman <strong>Input Fisik</strong> untuk mengisi hasil hitung di
                            gudang. Nilai stok akan ditampilkan dengan <strong>2 digit desimal</strong> supaya rapi dan
                            konsisten.
                        </small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('inventory.stock_opnames.index') }}" class="btn-ghost-gfid">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-sm btn-primary-gfid">
                            Buat Sesi Opname
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
