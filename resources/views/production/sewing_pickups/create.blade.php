@extends('layouts.app')

@section('title', 'Produksi • Sewing Pickup')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .help {
            color: var(--muted);
            font-size: .85rem;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .15rem .5rem;
            font-size: .7rem;
        }

        .table-wrap {
            overflow-x: auto;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h5 mb-1">Sewing Pickup</h1>
                    <div class="help">
                        Form untuk mencatat bundle hasil cutting yang diambil operator jahit.
                    </div>
                </div>

                <a href="{{ route('production.sewing_pickups.bundles_ready') }}" class="btn btn-sm btn-outline-secondary">
                    Lihat Bundles Ready
                </a>
            </div>
        </div>

        <form action="{{ route('production.sewing_pickups.store') }}" method="post">
            @csrf

            {{-- HEADER FORM --}}
            <div class="card p-3 mb-3">
                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <div class="help mb-1">Tanggal</div>
                        <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
                            value="{{ old('date', now()->format('Y-m-d')) }}">
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="help mb-1">Gudang Sewing</div>
                        <select name="warehouse_id" class="form-select @error('warehouse_id') is-invalid @enderror">
                            <option value="">-- Pilih Gudang --</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}"
                                    {{ (int) old('warehouse_id') === $wh->id ? 'selected' : '' }}>
                                    {{ $wh->code }} — {{ $wh->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('warehouse_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3 col-12">
                        <div class="help mb-1">Operator Jahit</div>
                        <select name="operator_id" class="form-select @error('operator_id') is-invalid @enderror">
                            <option value="">-- Pilih Operator --</option>
                            @foreach ($operators as $op)
                                <option value="{{ $op->id }}"
                                    {{ (int) old('operator_id') === $op->id ? 'selected' : '' }}>
                                    {{ $op->code }} — {{ $op->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('operator_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3 col-12">
                        <div class="help mb-1">Catatan</div>
                        <input type="text" name="notes" class="form-control @error('notes') is-invalid @enderror"
                            value="{{ old('notes') }}">
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- TABEL PILIH BUNDLE --}}
            <div class="card p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 mb-0">Pilih Bundles yang Diambil</h2>
                    <div class="help">
                        Isi kolom <strong>Qty Pickup</strong> untuk bundle yang mau diambil. Baris dengan qty 0 akan
                        diabaikan.
                    </div>
                </div>

                @error('lines')
                    <div class="alert alert-danger py-1 small mb-2">
                        {{ $message }}
                    </div>
                @enderror

                <div class="table-wrap">
                    <table class="table table-sm align-middle mono">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="width: 150px;">Bundle Code</th>
                                <th style="width: 160px;">Item Jadi</th>
                                <th style="width: 140px;">Lot</th>
                                <th style="width: 110px;">Qty Cutting</th>
                                <th style="width: 110px;">Qty QC OK</th>
                                <th style="width: 130px;">Qty Pickup</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $oldLines = old('lines', []);
                                $preselectedBundleId = request('bundle_id');
                            @endphp

                            @forelse ($bundles as $idx => $b)
                                @php
                                    $qc = $b->qcResults->where('stage', 'cutting')->sortByDesc('qc_date')->first();

                                    $oldLine = $oldLines[$idx] ?? null;
                                    $defaultQtyPickup = $oldLine['qty_bundle'] ?? null;

                                    // Kalau datang dari "Pick" di halaman ready, auto isi qty = qty_ok
                                    if ($defaultQtyPickup === null && $preselectedBundleId == $b->id) {
                                        $defaultQtyPickup = $qc?->qty_ok ?? $b->qty_pcs;
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>

                                    {{-- hidden bundle_id --}}
                                    <input type="hidden" name="lines[{{ $idx }}][bundle_id]"
                                        value="{{ $b->id }}">

                                    <td>{{ $b->bundle_code }}</td>

                                    <td>
                                        {{ $b->finishedItem?->code ?? '-' }}
                                    </td>

                                    <td>
                                        {{ $b->cuttingJob?->lot?->item?->code ?? '-' }}
                                        @if ($b->cuttingJob?->lot)
                                            <span class="badge-soft bg-light border text-muted">
                                                {{ $b->cuttingJob->lot->code }}
                                            </span>
                                        @endif
                                    </td>

                                    <td>{{ number_format($b->qty_pcs, 2, ',', '.') }}</td>

                                    <td>{{ number_format($qc?->qty_ok ?? 0, 2, ',', '.') }}</td>

                                    <td>
                                        <input type="number" step="0.01" min="0"
                                            name="lines[{{ $idx }}][qty_bundle]"
                                            class="form-control form-control-sm @error("lines.$idx.qty_bundle") is-invalid @enderror"
                                            value="{{ $defaultQtyPickup ?? '0' }}">
                                        @error("lines.$idx.qty_bundle")
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted small">
                                        Belum ada bundle hasil QC Cutting yang siap dijahit.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>

            {{-- SUBMIT --}}
            <div class="d-flex justify-content-between align-items-center mb-5">
                <a href="{{ url()->previous() }}" class="btn btn-sm btn-outline-secondary">
                    Batal
                </a>

                <button type="submit" class="btn btn-sm btn-primary">
                    Simpan Sewing Pickup
                </button>
            </div>
        </form>

    </div>
@endsection
