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
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .help {
            color: var(--muted);
            font-size: .85rem;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .15rem .55rem;
            font-size: .7rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .sticky-head thead th {
            position: sticky;
            top: 0;
            background: var(--panel);
            z-index: 1;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        <form action="{{ route('production.sewing_pickups.store') }}" method="post">
            @csrf

            {{-- HEADER --}}
            <div class="card p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <h1 class="h5 mb-1">Sewing Pickup</h1>
                        <div class="help">
                            Input pengambilan bundle dari WIP Cutting oleh operator jahit.
                        </div>
                    </div>

                    <div>
                        <a href="{{ route('production.sewing_pickups.index') }}" class="btn btn-sm btn-outline-secondary">
                            Kembali
                        </a>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <label class="form-label help mb-1">Tanggal</label>
                        <input type="date" name="date" class="form-control form-control-sm mono"
                            value="{{ old('date', now()->format('Y-m-d')) }}">
                        @error('date')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4 col-12">
                        <label class="form-label help mb-1">Operator Jahit</label>
                        <select name="operator_id" class="form-select form-select-sm">
                            <option value="">-- Pilih Operator --</option>
                            @foreach ($operators as $op)
                                <option value="{{ $op->id }}" @selected(old('operator_id') == $op->id)>
                                    {{ $op->code }} — {{ $op->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('operator_id')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3 col-12">
                        <label class="form-label help mb-1">Gudang</label>
                        <select name="warehouse_id" class="form-select form-select-sm">
                            <option value="">-- Pilih Gudang --</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}" @selected(old('warehouse_id') == $wh->id)>
                                    {{ $wh->code }} — {{ $wh->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('warehouse_id')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-2 col-12">
                        <label class="form-label help mb-1">Catatan</label>
                        <input type="text" name="notes" class="form-control form-control-sm"
                            value="{{ old('notes') }}" placeholder="Opsional">
                    </div>
                </div>

                @if ($errors->has('lines') || $errors->has('lines.*.bundle_id') || $errors->has('lines.*.qty_bundle'))
                    <div class="mt-2 text-danger small">
                        Pastikan minimal 1 baris bundle dicentang dan qty diisi dengan benar.
                    </div>
                @endif
            </div>

            {{-- TABEL BUNDLES SIAP DIJAHIT --}}
            <div class="card p-3 mb-3">
                <h2 class="h6 mb-2">Pilih Bundle untuk Dijahit</h2>
                <div class="help mb-2">
                    Centang bundle yang akan diambil, lalu isi qty yang diambil per bundle.
                </div>

                <div class="table-wrap">
                    <table class="table table-sm align-middle mono sticky-head">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="checkAll">
                                </th>
                                <th style="width: 140px;">Bundle</th>
                                <th style="width: 200px;">Item</th>
                                <th style="width: 220px;">Lot</th>
                                <th style="width: 120px;" class="text-end">Qty Siap (pcs)</th>
                                <th style="width: 140px;" class="text-end">Qty Ambil (pcs)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bundles as $idx => $bundle)
                                @php
                                    $inputName = "lines.$idx";
                                    $oldLine = old('lines.' . $idx, []);
                                    $checked = data_get($oldLine, 'bundle_id') == $bundle->id;
                                @endphp
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input bundle-check"
                                            data-row="{{ $idx }}" name="lines[{{ $idx }}][bundle_id]"
                                            value="{{ $bundle->id }}" @checked($checked)>
                                    </td>
                                    <td>
                                        <div>{{ $bundle->bundle_code }}</div>
                                        <div class="small text-muted">
                                            Job: {{ $bundle->cuttingJob?->code ?? '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div>{{ $bundle->finishedItem?->code ?? '-' }}</div>
                                        <div class="small text-muted">
                                            Qty cutting: {{ number_format($bundle->qty_pcs, 2, ',', '.') }} pcs
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $bundle->cuttingJob?->lot?->item?->code ?? '-' }}
                                        </div>
                                        @if ($bundle->cuttingJob?->lot)
                                            <span class="badge-soft bg-light border text-muted">
                                                {{ $bundle->cuttingJob->lot->code }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        {{ number_format($bundle->qty_pcs, 2, ',', '.') }}
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0"
                                            class="form-control form-control-sm text-end qty-input"
                                            name="lines[{{ $idx }}][qty_bundle]" data-row="{{ $idx }}"
                                            value="{{ $oldLine['qty_bundle'] ?? $bundle->qty_pcs }}"
                                            {{ $checked ? '' : 'disabled' }}>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted small">
                                        Tidak ada bundle siap jahit (pastikan QC Cutting sudah OK).
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>

            <div class="d-flex justify-content-between align-items-center mb-5">
                <div class="help">
                    Setelah disimpan, Sewing Pickup akan tercatat dan siap dibuatkan Sewing Return.
                </div>
                <button type="submit" class="btn btn-primary">
                    Simpan Sewing Pickup
                </button>
            </div>
        </form>

    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const checkAll = document.getElementById('checkAll');
                const checks = document.querySelectorAll('.bundle-check');
                const qtyInputs = document.querySelectorAll('.qty-input');

                if (checkAll) {
                    checkAll.addEventListener('change', function() {
                        checks.forEach(chk => {
                            chk.checked = checkAll.checked;
                            const row = chk.dataset.row;
                            const input = document.querySelector('.qty-input[data-row="' + row + '"]');
                            if (input) {
                                input.disabled = !chk.checked;
                                if (chk.checked && !input.value) {
                                    input.value = input.getAttribute('data-default') || '';
                                }
                            }
                        });
                    });
                }

                checks.forEach(chk => {
                    chk.addEventListener('change', function() {
                        const row = chk.dataset.row;
                        const input = document.querySelector('.qty-input[data-row="' + row + '"]');
                        if (input) {
                            input.disabled = !chk.checked;
                        }
                    });
                });
            });
        </script>
    @endpush
@endsection
