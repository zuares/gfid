@extends('layouts.app')

@section('title', 'Edit Periode HPP Produksi')

@push('head')
    <style>
        .page-wrap {
            max-width: 900px;
            margin-inline: auto;
            padding: .75rem .75rem 3rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(129, 140, 248, 0.18) 0,
                    rgba(45, 212, 191, 0.10) 26%,
                    #f9fafb 60%);
        }

        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.28);
            box-shadow:
                0 10px 24px rgba(15, 23, 42, 0.06),
                0 0 0 1px rgba(15, 23, 42, 0.03);
        }

        .card-generate {
            background: linear-gradient(135deg,
                    rgba(22, 163, 74, .10),
                    rgba(16, 185, 129, .08));
            border-radius: 14px;
            border: 1px solid rgba(34, 197, 94, .35);
        }

        .form-label {
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: #64748b;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .2rem .7rem;
            font-size: .75rem;
            border: 1px solid rgba(148, 163, 184, .6);
            background: rgba(15, 23, 42, 0.02);
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">Edit Periode HPP Produksi</h4>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge-soft">
                        {{ $period->code }}
                    </span>
                    <span class="badge-soft">
                        Status: {{ strtoupper($period->status ?? 'draft') }}
                    </span>
                    @if ($period->is_active)
                        <span class="badge-soft text-primary border-primary-subtle">
                            Aktif
                        </span>
                    @endif
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('costing.production_cost_periods.index') }}" class="btn btn-sm btn-outline-secondary">
                    &larr; Kembali
                </a>
                <a href="{{ route('costing.production_cost_periods.show', $period) }}"
                    class="btn btn-sm btn-outline-primary">
                    Lihat Detail
                </a>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success py-2 px-3 small">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger py-2 px-3 small mb-3">
                <strong class="d-block mb-1">Ada error pada form:</strong>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- FORM UTAMA --}}
        <div class="card card-main mb-3">
            <div class="card-body">
                <form action="{{ route('costing.production_cost_periods.update', $period) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Periode</label>
                            <input type="text" name="name"
                                class="form-control form-control-sm @error('name') is-invalid @enderror"
                                value="{{ old('name', $period->name) }}" placeholder="Contoh: HPP Produksi Desember 2025">
                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                            <div class="form-text small">
                                Untuk identifikasi di laporan (di luar kode: {{ $period->code }}).
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" name="date_from"
                                class="form-control form-control-sm @error('date_from') is-invalid @enderror"
                                value="{{ old('date_from', optional($period->date_from)->format('Y-m-d')) }}">
                            @error('date_from')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" name="date_to"
                                class="form-control form-control-sm @error('date_to') is-invalid @enderror"
                                value="{{ old('date_to', optional($period->date_to)->format('Y-m-d')) }}">
                            @error('date_to')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Tanggal Snapshot HPP</label>
                            <input type="date" name="snapshot_date"
                                class="form-control form-control-sm @error('snapshot_date') is-invalid @enderror"
                                value="{{ old('snapshot_date', optional($period->snapshot_date)->format('Y-m-d')) }}">
                            @error('snapshot_date')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                            <div class="form-text small">
                                Biasanya di akhir periode (mis. 31/12/2025).
                            </div>
                        </div>

                        {{-- Mapping Payroll --}}
                        <div class="col-md-4">
                            <label class="form-label">Payroll Cutting</label>
                            <select name="cutting_payroll_period_id"
                                class="form-select form-select-sm @error('cutting_payroll_period_id') is-invalid @enderror">
                                <option value="">– Pilih Periode –</option>
                                @foreach ($cuttingPeriods as $pp)
                                    <option value="{{ $pp->id }}" @selected(old('cutting_payroll_period_id', $period->cutting_payroll_period_id) == $pp->id)>
                                        {{ $pp->name ?? "Cutting {$pp->id}" }}
                                        ({{ optional($pp->date_from)->format('d/m') }} -
                                        {{ optional($pp->date_to)->format('d/m/Y') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('cutting_payroll_period_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Payroll Sewing</label>
                            <select name="sewing_payroll_period_id"
                                class="form-select form-select-sm @error('sewing_payroll_period_id') is-invalid @enderror">
                                <option value="">– Pilih Periode –</option>
                                @foreach ($sewingPeriods as $pp)
                                    <option value="{{ $pp->id }}" @selected(old('sewing_payroll_period_id', $period->sewing_payroll_period_id) == $pp->id)>
                                        {{ $pp->name ?? "Sewing {$pp->id}" }}
                                        ({{ optional($pp->date_from)->format('d/m') }} -
                                        {{ optional($pp->date_to)->format('d/m/Y') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('sewing_payroll_period_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Payroll Finishing</label>
                            <select name="finishing_payroll_period_id"
                                class="form-select form-select-sm @error('finishing_payroll_period_id') is-invalid @enderror">
                                <option value="">– Pilih Periode –</option>
                                @foreach ($finishingPeriods as $pp)
                                    <option value="{{ $pp->id }}" @selected(old('finishing_payroll_period_id', $period->finishing_payroll_period_id) == $pp->id)>
                                        {{ $pp->name ?? "Finishing {$pp->id}" }}
                                        ({{ optional($pp->date_from)->format('d/m') }} -
                                        {{ optional($pp->date_to)->format('d/m/Y') }})
                                    </option>
                                @endforeach
                            </select>
                            @error('finishing_payroll_period_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea name="notes" rows="2" class="form-control form-control-sm @error('notes') is-invalid @enderror"
                                placeholder="Catatan internal...">{{ old('notes', $period->notes) }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <small class="text-muted">
                            Pastikan range tanggal cocok dengan periode payroll yang dipilih.
                        </small>

                        <button type="submit" class="btn btn-primary btn-sm">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- CARD: Generate HPP dari Payroll --}}
        <div class="card card-generate">
            <div class="card-body py-3">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge-soft" style="border-color: rgba(22,163,74,.5);">
                                AUTO COSTING
                            </span>
                            <span class="text-success small fw-semibold">
                                Generate HPP dari payroll untuk periode ini.
                            </span>
                        </div>
                        <div class="small text-muted">
                            Sistem akan:
                            <ol class="mb-0 ps-4">
                                <li>Ambil HPP RM (snapshot aktif) per item</li>
                                <li>Hitung biaya Cutting / Sewing / Finishing per unit</li>
                                <li>Buat snapshot HPP baru dengan referensi periode ini</li>
                                <li>Set sebagai HPP aktif untuk item tersebut</li>
                            </ol>
                        </div>
                    </div>

                    <div class="text-md-end">
                        <form action="{{ route('costing.production_cost_periods.generate', $period) }}" method="POST"
                            onsubmit="return confirm('Generate HPP dari payroll untuk periode ini?\nProses ini akan membuat snapshot HPP baru per item.');">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm">
                                Generate HPP Sekarang
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
