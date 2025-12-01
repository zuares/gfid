@extends('layouts.app')

@section('title', 'Payroll • Generate Sewing')

@push('head')
    <style>
        .page-wrap {
            max-width: 600px;
            margin-inline: auto;
            padding: 1rem .75rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(244, 114, 182, 0.16) 0,
                    rgba(59, 130, 246, 0.12) 32%,
                    #f9fafb 70%);
        }

        .card {
            background: var(--card);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.28);
            box-shadow:
                0 18px 45px rgba(15, 23, 42, 0.15),
                0 0 0 1px rgba(15, 23, 42, 0.02);
        }

        .help-text {
            font-size: .8rem;
            color: var(--muted);
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        <div class="mb-3">
            <a href="{{ route('payroll.sewing.index') }}" class="btn btn-link px-0 small">
                ← Kembali ke daftar periode
            </a>
        </div>

        <div class="card">
            <div class="card-body p-3 p-md-4">
                <h1 class="h5 mb-1">Generate Payroll Sewing</h1>
                <p class="help-text mb-3">
                    Pilih rentang tanggal berdasarkan <strong>tanggal Sewing Return (return_date)</strong>.
                    Sistem akan menjumlahkan semua <strong>qty OK jahitan</strong> di periode ini.
                </p>

                @if ($errors->any())
                    <div class="alert alert-danger py-2 small">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('payroll.sewing.store') }}" method="POST" class="row g-3">
                    @csrf

                    <div class="col-12 col-md-6">
                        <label class="form-label small mb-1">Tanggal awal</label>
                        <input type="date" name="period_start" value="{{ old('period_start', $defaultStart) }}"
                            class="form-control form-control-sm @error('period_start') is-invalid @enderror">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small mb-1">Tanggal akhir</label>
                        <input type="date" name="period_end" value="{{ old('period_end', $defaultEnd) }}"
                            class="form-control form-control-sm @error('period_end') is-invalid @enderror">
                    </div>

                    <div class="col-12">
                        <p class="help-text mb-2">
                            Sistem akan group per <strong>operator × kategori × item</strong>.
                            Tarif diambil dari <strong>Master Piece Rate (module = sewing)</strong> atau
                            <strong>default_piece_rate</strong> di master karyawan.
                        </p>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-magic"></i> Generate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
