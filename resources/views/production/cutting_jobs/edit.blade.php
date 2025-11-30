{{-- resources/views/production/cutting_jobs/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Edit Cutting Job ' . $job->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .help {
            color: var(--muted);
            font-size: .85rem;
        }

        .lot-bar-mobile {
            position: sticky;
            top: 56px;
            /* sesuaikan tinggi navbar */
            z-index: 1020;
            background: var(--card, #fff);
            border-bottom: 1px solid var(--line, #e5e7eb);
            padding: .35rem .75rem;
            font-size: .85rem;
            display: flex;
            align-items: center;
            gap: .25rem;
            white-space: nowrap;
            overflow-x: auto;
        }

        @media (max-width: 767.98px) {
            .card .d-flex.flex-column.flex-md-row {
                gap: .5rem;
            }

            .table-wrap {
                overflow-x: auto;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $isEdit = true;

        $lotQty = isset($lotBalance) ? (float) $lotBalance : 0;

        $defaultOperatorId = optional($job->bundles->first())->operator_id;

        $lotPurchaseDate = $lot?->purchased_at ?? ($lot?->purchase_date ?? ($lot?->received_at ?? $lot?->created_at));
        $lotPurchaseDateLabel = $lotPurchaseDate ? $lotPurchaseDate->format('d/m/Y') : '-';
    @endphp

    <div class="page-wrap">
        <h1 class="h5 mb-3">Edit Cutting Job: {{ $job->code }}</h1>

        <form action="{{ route('production.cutting_jobs.update', $job) }}" method="post">
            @csrf
            @method('PUT')

            {{-- MOBILE: BAR INFO LOT --}}
            <div class="lot-bar-mobile d-md-none mb-2">
                <span class="mono">{{ $lotPurchaseDateLabel }}</span>
                <span class="mx-1">•</span>
                <span class="fw-semibold">{{ $lot?->item?->name ?? '-' }}</span>
                <span class="mx-1">•</span>
                <span class="mono">{{ number_format($lotQty, 2, ',', '.') }} Kg</span>
            </div>

            {{-- HIDDEN HEADER --}}
            <input type="hidden" name="warehouse_id" value="{{ $warehouse?->id }}">
            <input type="hidden" name="lot_id" value="{{ $lot?->id }}">
            <input type="hidden" name="lot_balance" value="{{ $lotQty }}">
            <input type="hidden" name="fabric_item_id"
                value="{{ old('fabric_item_id', $job->fabric_item_id ?? ($lot?->item_id ?? null)) }}">

            {{-- INFORMASI LOT (DESKTOP) --}}
            <div class="card p-3 mb-3 d-none d-md-block">
                <h2 class="h6 mb-2">Informasi Lot Kain</h2>
                @error('fabric_item_id')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror

                <div class="row g-3 mb-2">
                    <div class="col-md-4">
                        <div class="help mb-1">Tgl Beli</div>
                        <div class="mono">{{ $lotPurchaseDateLabel }}</div>
                    </div>

                    <div class="col-md-4">
                        <div class="help mb-1">Nama Bahan</div>
                        <div>{{ $lot?->item?->name ?? '-' }}</div>
                    </div>

                    <div class="col-md-4">
                        <div class="help mb-1">Qty</div>
                        <div class="mono">{{ number_format($lotQty, 2, ',', '.') }} Kg</div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4 col-12">
                        <div class="help mb-1">LOT</div>
                        <div class="fw-semibold">{{ $lot?->code ?? '-' }}</div>
                        <div class="small text-muted">{{ $lot?->item?->code ?? '-' }}</div>
                    </div>

                    <div class="col-md-4 col-6">
                        <div class="help mb-1">Gudang</div>
                        <div class="mono">{{ $warehouse?->code }} — {{ $warehouse?->name }}</div>
                    </div>

                    <div class="col-md-4 col-6">
                        <div class="help mb-1">Saldo LOT (perkiraan)</div>
                        <div class="mono">{{ number_format($lotQty, 2, ',', '.') }} Kg</div>
                    </div>
                </div>
            </div>

            {{-- HEADER JOB --}}
            <div class="card p-3 mb-3">
                <h2 class="h6 mb-2">Header Cutting Job</h2>

                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
                            value="{{ old('date', $job->date?->format('Y-m-d') ?? now()->toDateString()) }}">
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3 col-6">
                        <label class="form-label">Operator Cutting</label>
                        @php
                            $currentOperatorId = old('operator_id', $defaultOperatorId);
                        @endphp
                        <select name="operator_id" class="form-select @error('operator_id') is-invalid @enderror">
                            <option value="">Pilih operator cutting…</option>
                            @foreach ($operators as $op)
                                <option value="{{ $op->id }}" @selected($currentOperatorId == $op->id)>
                                    {{ $op->code }} — {{ $op->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('operator_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 d-none d-md-block">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" rows="2" class="form-control">{{ old('notes', $job->notes) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- OUTPUT BUNDLES --}}
            <div class="card p-3 mb-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-2">
                    <div class="mb-2 mb-md-0">
                        <h2 class="h6 mb-0">Output Bundles</h2>
                    </div>

                    <div class="w-100 w-md-auto d-none d-md-block">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="add-row-top">
                            + Tambah baris
                        </button>
                    </div>
                </div>

                <div id="bundle-warning" class="text-danger small mb-2" style="display:none;">
                    ⚠️ Total pemakaian kain > saldo LOT
                </div>

                <div class="table-wrap">
                    <table class="table table-sm align-middle mono">
                        <thead>
                            <tr>
                                <th style="width:40px;">#</th>
                                <th>Item Jadi</th>
                                <th style="width:110px;">Qty (pcs)</th>
                                <th class="d-none d-md-table-cell">Item Category</th>
                                <th style="width:120px;" class="d-none d-md-table-cell">Used</th>
                                <th style="width:40px;" class="d-none d-md-table-cell"></th>
                            </tr>
                        </thead>
                        <tbody id="bundle-rows">
                            @foreach ($rows as $i => $row)
                                <tr>
                                    @if (!empty($row['id']))
                                        <input type="hidden" name="bundles[{{ $i }}][id]"
                                            value="{{ $row['id'] }}">
                                    @endif

                                    <td data-label="#">
                                        <span class="row-index mono"></span>
                                    </td>

                                    {{-- ITEM JADI: SELECT BIASA --}}
                                    <td data-label="Item Jadi">
                                        <select name="bundles[{{ $i }}][finished_item_id]"
                                            class="form-select form-select-sm @error("bundles.$i.finished_item_id") is-invalid @enderror">
                                            <option value="">Pilih item jadi…</option>
                                            @foreach ($items as $it)
                                                <option value="{{ $it->id }}" @selected(old("bundles.$i.finished_item_id", $row['finished_item_id'] ?? null) == $it->id)>
                                                    {{ $it->code }} — {{ $it->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error("bundles.$i.finished_item_id")
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    {{-- Qty (pcs) --}}
                                    <td data-label="Qty (pcs)">
                                        <input type="number" step="1" min="0" inputmode="numeric"
                                            pattern="\d*" name="bundles[{{ $i }}][qty_pcs]"
                                            class="form-control form-control-sm text-end bundle-qty @error("bundles.$i.qty_pcs") is-invalid @enderror"
                                            value="{{ old("bundles.$i.qty_pcs", isset($row['qty_pcs']) ? (int) $row['qty_pcs'] : '') }}">
                                        @error("bundles.$i.qty_pcs")
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    {{-- Item Category (opsional, masih manual teks) --}}
                                    <td data-label="Item Category" class="d-none d-md-table-cell">
                                        <input type="text" name="bundles[{{ $i }}][item_category]"
                                            class="form-control form-control-sm" placeholder="Kategori"
                                            value="{{ old("bundles.$i.item_category", $row['item_category'] ?? '') }}">
                                    </td>

                                    {{-- Used (client-side) --}}
                                    <td data-label="Used" class="d-none d-md-table-cell">
                                        <span class="bundle-qty-used help">-</span>
                                    </td>

                                    {{-- Hapus baris --}}
                                    <td data-label="" class="d-none d-md-table-cell">
                                        <button type="button"
                                            class="btn btn-sm btn-link text-danger remove-row">×</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- tombol bawah khusus mobile --}}
                <div class="mt-2 d-md-none">
                    <button type="button" class="btn btn-outline-secondary w-100 btn-sm" id="add-row-bottom">
                        + Tambah baris
                    </button>
                </div>

                @error('bundles')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            {{-- SUBMIT --}}
            <div class="d-flex justify-content-end mb-4">
                <button class="btn btn-primary">
                    Update Cutting Job
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        const bundleRows = document.getElementById('bundle-rows');
        const addRowBtnTop = document.getElementById('add-row-top');
        const addRowBtnBottom = document.getElementById('add-row-bottom');

        const rowCountSpan = document.getElementById('bundle-row-count');
        const perRowSpan = document.getElementById('bundle-per-row');
        const totalQtySpan = document.getElementById('bundle-total-qty');
        const totalUsedSpan = document.getElementById('bundle-total-used');
        const warningEl = document.getElementById('bundle-warning');

        const lotQty = {{ $lotQty }};

        function attachSelectAllOnFocus(input) {
            input.addEventListener('focus', function() {
                setTimeout(() => this.select(), 0);
            });
            input.addEventListener('mouseup', function(e) {
                e.preventDefault();
            });
        }

        function renumberRows() {
            if (!bundleRows) return;
            const rows = bundleRows.querySelectorAll('tr');
            rows.forEach((tr, idx) => {
                const idxSpan = tr.querySelector('.row-index');
                if (idxSpan) {
                    idxSpan.textContent = idx + 1;
                }
            });
        }

        function recalcAll() {
            if (!bundleRows) return;

            const rows = bundleRows.querySelectorAll('tr');
            const count = rows.length;
            let totalQtyPcs = 0;

            rows.forEach(tr => {
                const qtyInput = tr.querySelector('.bundle-qty');
                if (!qtyInput) return;

                let v = parseInt(qtyInput.value || '0', 10);
                if (isNaN(v) || v < 0) v = 0;
                qtyInput.value = v;

                totalQtyPcs += v;
            });

            const perRow = (count > 0 && lotQty > 0) ? (lotQty / count) : 0;
            const totalUsed = perRow * count;

            if (rowCountSpan) rowCountSpan.textContent = count;
            if (perRowSpan) perRowSpan.textContent = perRow ? perRow.toFixed(2).replace('.', ',') : '';
            if (totalQtySpan) totalQtySpan.textContent = totalQtyPcs.toFixed(2).replace('.', ',');
            if (totalUsedSpan) totalUsedSpan.textContent = totalUsed ? totalUsed.toFixed(2).replace('.', ',') : '';

            if (warningEl) {
                warningEl.style.display = (totalUsed > lotQty + 0.000001) ? 'block' : 'none';
            }

            rows.forEach(tr => {
                const usedSpan = tr.querySelector('.bundle-qty-used');
                if (usedSpan) {
                    usedSpan.textContent = perRow ? perRow.toFixed(2).replace('.', ',') : '-';
                }
            });
        }

        function attachRowListeners(tr) {
            const qtyInput = tr.querySelector('.bundle-qty');
            if (qtyInput) {
                attachSelectAllOnFocus(qtyInput);
                qtyInput.addEventListener('input', recalcAll);
            }
        }

        function addRow() {
            if (!bundleRows) return;

            const rows = bundleRows.querySelectorAll('tr');
            const index = rows.length;
            if (!rows.length) return;

            const templateRow = rows[0];
            const newRow = templateRow.cloneNode(true);

            // Bersihkan nilai lama
            newRow.querySelectorAll('input, select, textarea').forEach(el => {
                const name = el.name || '';

                // id bundle lama tidak dipakai di row baru
                if (name.endsWith('[id]')) {
                    el.remove();
                    return;
                }

                if (el.tagName === 'SELECT') {
                    el.selectedIndex = 0;
                } else if (['hidden', 'text', 'number'].includes(el.type)) {
                    el.value = '';
                }
            });

            // Update name index
            newRow.querySelectorAll('input, select, textarea').forEach(el => {
                if (!el.name) return;
                el.name = el.name.replace(/bundles\[\d+]/, `bundles[${index}]`);
            });

            bundleRows.appendChild(newRow);
            attachRowListeners(newRow);
            renumberRows();
            recalcAll();
        }

        // INIT EXISTING ROWS
        if (bundleRows) {
            Array.from(bundleRows.querySelectorAll('tr')).forEach(tr => {
                attachRowListeners(tr);
            });
            renumberRows();
            recalcAll();
        }

        if (addRowBtnTop) {
            addRowBtnTop.addEventListener('click', addRow);
        }
        if (addRowBtnBottom) {
            addRowBtnBottom.addEventListener('click', addRow);
        }

        // Hapus baris
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-row')) {
                const tr = e.target.closest('tr');
                if (!tr || !bundleRows) return;

                tr.parentNode.removeChild(tr);
                renumberRows();
                recalcAll();
            }
        });
    </script>
@endpush
