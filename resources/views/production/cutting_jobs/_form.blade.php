{{-- resources/views/production/cutting_jobs/partials/_form.blade.php --}}
{{-- UNIVERSAL FORM UNTUK CUTTING JOB (CREATE / EDIT) --}}

@php
    $isEdit = $mode === 'edit';

    // dipakai JS untuk saldo LOT
    $lotQty = isset($lotBalance) ? (float) $lotBalance : (isset($qty) ? (float) $qty : 0);

    // default operator = MRF (kalau create), kalau edit pakai operator bundle pertama
    $defaultOperatorId = $isEdit
        ? optional($job->bundles->first())->operator_id
        : optional($operators->firstWhere('code', 'MRF'))->id;

    // tanggal beli lot (fallback ke created_at)
    $lotPurchaseDate = $lot?->purchased_at ?? ($lot?->purchase_date ?? ($lot?->received_at ?? $lot?->created_at));
    $lotPurchaseDateLabel = $lotPurchaseDate ? $lotPurchaseDate->format('d/m/Y') : '-';
@endphp

<form action="{{ $isEdit ? route('production.cutting_jobs.update', $job) : route('production.cutting_jobs.store') }}"
    method="post" class="cutting-form-safe-bottom js-cutting-form">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    {{-- =========================
         MOBILE: BAR INFO LOT
    ========================== --}}
    <div class="lot-bar-mobile d-md-none mb-2">
        <span class="mono">{{ $lotPurchaseDateLabel }}</span>
        <span class="mx-1">•</span>
        <span class="fw-semibold">{{ $lot?->item?->name ?? '-' }}</span>
        <span class="mx-1">•</span>
        <span class="mono">{{ number_format($lotQty, 2, ',', '.') }} Kg</span>
    </div>

    {{-- =========================
         HIDDEN HEADER
    ========================== --}}
    <input type="hidden" name="warehouse_id" value="{{ $warehouse?->id }}">
    <input type="hidden" name="lot_id" value="{{ $lot?->id }}">
    <input type="hidden" name="lot_balance" value="{{ $lotQty }}">

    {{-- fabric item (item kain) ikut dikirim ke backend --}}
    <input type="hidden" name="fabric_item_id"
        value="{{ old('fabric_item_id', $isEdit ? $job->fabric_item_id : $lot?->item_id ?? null) }}">

    {{-- =========================
         INFORMASI LOT (DESKTOP)
    ========================== --}}
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

    {{-- =========================
         HEADER JOB
    ========================== --}}
    <div class="card p-3 mb-3">
        <h2 class="h6 mb-2">Header Cutting Job</h2>

        <div class="row g-3">
            <div class="col-md-3 col-6">
                <label class="form-label">Tanggal</label>
                <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
                    value="{{ old('date', $isEdit ? $job->date?->format('Y-m-d') : now()->toDateString()) }}">
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
                <textarea name="notes" rows="2" class="form-control">{{ old('notes', $isEdit ? $job->notes : '') }}</textarea>
            </div>
        </div>
    </div>

    {{-- =========================
         OUTPUT BUNDLES
    ========================== --}}
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
                        @php
                            // Bisa berupa array (old()) atau model CuttingJobBundle
                            $bundle = is_array($row) ? (object) $row : $row;

                            $finishedItem = $bundle->finishedItem ?? null;

                            // ID item jadi
                            $finishedItemId = data_get($row, 'finished_item_id') ?? ($bundle->finished_item_id ?? null);

                            // Kode item jadi → ini yang dipakai di halaman show
                            $finishedItemCode = data_get($row, 'finished_item_code') ?? ($finishedItem->code ?? '');

                            // Label tampilan komponen suggest (kode saja, kayak di show)
                            $displayLabel = $finishedItemCode;

                            // item_category_id
                            $itemCategoryId =
                                data_get($row, 'item_category_id') ??
                                ($bundle->item_category_id ?? optional($finishedItem->category ?? null)->id);

                            // nama kategori untuk label tabel
                            $itemCategoryName =
                                data_get($row, 'item_category_name') ??
                                optional($bundle->itemCategory ?? ($finishedItem->category ?? null))->name;
                        @endphp

                        <tr>
                            {{-- hidden id bundle (untuk edit) --}}
                            @if (!empty($bundle->id))
                                <input type="hidden" name="bundles[{{ $i }}][id]"
                                    value="{{ $bundle->id }}">
                            @endif

                            <td data-label="#">
                                <span class="row-index mono"></span>
                            </td>

                            {{-- ITEM JADI: komponen suggest --}}
                            <td data-label="Item Jadi">
                                <x-item-suggest-input :idName="'bundles[' . $i . '][finished_item_id]'" :categoryName="'bundles[' . $i . '][item_category_id]'" :idValue="$finishedItemId"
                                    :categoryValue="$itemCategoryId" :displayValue="$displayLabel" type="finished_good" :autofocus="!$isEdit && $loop->first" />
                            </td>

                            {{-- Qty (pcs) --}}
                            <td data-label="Qty (pcs)">
                                <input type="number" step="1" min="0" inputmode="numeric" pattern="\d*"
                                    name="bundles[{{ $i }}][qty_pcs]"
                                    class="form-control form-control-sm text-end bundle-qty"
                                    value="{{ isset($bundle->qty_pcs) ? (int) $bundle->qty_pcs : '' }}">
                            </td>

                            {{-- Item Category: tampilkan NAMA kategori --}}
                            <td data-label="Item Category" class="d-none d-md-table-cell">
                                <span class="bundle-item-category text-muted small">
                                    {{ $itemCategoryName ?? '-' }}
                                </span>
                            </td>

                            {{-- Used (client-side) --}}
                            <td data-label="Used" class="d-none d-md-table-cell">
                                <span class="bundle-qty-used help">-</span>
                            </td>

                            {{-- tombol hapus --}}
                            <td data-label="" class="d-none d-md-table-cell">
                                <button type="button" class="btn btn-sm btn-link text-danger remove-row">×</button>
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

    {{-- SUBMIT DESKTOP (pakai JS, bukan submit langsung) --}}
    <div class="d-none d-md-flex justify-content-end mb-4">
        <button type="button" class="btn btn-primary js-cutting-submit-trigger">
            {{ $isEdit ? 'Update Cutting Job' : 'Simpan Cutting Job' }}
        </button>
    </div>

    {{-- SUBMIT MOBILE: FAB kecil di kanan atas bottom-nav --}}
    <div class="cutting-submit-mobile-bar d-md-none">
        <button type="button" class="btn btn-primary cutting-submit-btn js-cutting-submit-trigger" title="Simpan">
            <i class="bi bi-check2"></i>
        </button>
    </div>
</form>

{{-- =========================
     MODAL KONFIRMASI & RINGKASAN
========================= --}}
<div class="cutting-modal-backdrop" id="cutting-confirm-modal" style="display:none;">
    <div class="cutting-modal-panel">
        <div class="cutting-modal-title">Simpan Cutting Job?</div>
        <div class="cutting-modal-body">
            Pastikan item dan qty sudah sesuai sebelum disimpan.
        </div>
        <div class="cutting-modal-actions">
            <button type="button" class="btn btn-sm btn-outline-secondary w-100 me-2" data-action="cancel-confirm">
                Batal
            </button>
            <button type="button" class="btn btn-sm btn-primary w-100" data-action="confirm-save">
                Simpan
            </button>
        </div>
    </div>
</div>

<div class="cutting-modal-backdrop" id="cutting-summary-modal" style="display:none;">
    <div class="cutting-modal-panel">
        <div class="cutting-modal-title">Ringkasan Cutting</div>
        <div class="cutting-modal-body">
            <div class="small text-muted mb-2">
                Berikut ringkasan kain yang dipotong dan hasil bundle:
            </div>

            <ul class="list-unstyled mb-2 small">
                <li><strong>Total Bundle:</strong> <span id="summary-total-bundle">0</span></li>
                <li><strong>Total Cutting (pcs):</strong> <span id="summary-total-qty">0</span></li>
                <li><strong>Estimasi kain terpakai:</strong> <span id="summary-total-used">0,00 Kg</span></li>
            </ul>

            <div class="small fw-semibold mb-1">
                Item & Qty:
            </div>
            <ul class="list-unstyled small" id="summary-items-list">
                {{-- akan diisi via JS --}}
            </ul>
        </div>
        <div class="cutting-modal-actions">
            <button type="button" class="btn btn-sm btn-outline-secondary w-100 me-2"
                data-action="back-from-summary">
                Kembali
            </button>
            <button type="button" class="btn btn-sm btn-primary w-100" data-action="submit-final">
                Simpan Sekarang
            </button>
        </div>
    </div>
</div>

@push('head')
    <style>
        .lot-bar-mobile {
            position: sticky;
            top: 56px;
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

            /* beri ruang di bawah konten supaya tidak ketutup tombol + bottom-nav */
            .cutting-form-safe-bottom {
                padding-bottom: 110px;
            }

            .cutting-submit-mobile-bar {
                position: fixed;
                right: .9rem;
                /* 62px = tinggi mobile-bottom-nav, + 10px jarak */
                bottom: calc(62px + 10px);
                z-index: 1030;
            }

            .cutting-submit-btn {
                width: 52px;
                height: 52px;
                border-radius: 999px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                box-shadow:
                    0 8px 18px rgba(0, 0, 0, 0.25),
                    0 0 0 1px rgba(15, 23, 42, 0.06);
                padding: 0;
            }

            .cutting-submit-btn i {
                font-size: 1.3rem;
            }
        }

        /* =====================
               CUSTOM MODAL MINIMALIS
            ====================== */
        .cutting-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            z-index: 2000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.25rem;
        }

        .cutting-modal-panel {
            width: 100%;
            max-width: 420px;
            background: var(--card, #fff);
            border-radius: 16px;
            border: 1px solid var(--line, #e5e7eb);
            box-shadow:
                0 20px 45px rgba(15, 23, 42, 0.35),
                0 0 0 1px rgba(15, 23, 42, 0.05);
            padding: 1rem 1.1rem .9rem;
        }

        .cutting-modal-title {
            font-size: .95rem;
            font-weight: 600;
            margin-bottom: .35rem;
        }

        .cutting-modal-body {
            font-size: .85rem;
            margin-bottom: .75rem;
        }

        .cutting-modal-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .5rem;
        }
    </style>
@endpush

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

            // Bersihkan nilai & id lama
            newRow.querySelectorAll('input').forEach(input => {
                const name = input.name || '';

                // id bundle lama tidak dipakai di row baru
                if (name.endsWith('[id]')) {
                    input.remove();
                    return;
                }

                if (['hidden', 'text', 'number'].includes(input.type)) {
                    input.value = '';
                }
            });

            const catLabel = newRow.querySelector('.bundle-item-category');
            if (catLabel) catLabel.textContent = '-';

            // Update name agar index-nya sesuai
            newRow.querySelectorAll('input, select, textarea').forEach(el => {
                if (!el.name) return;
                el.name = el.name.replace(/bundles\[\d+]/, `bundles[${index}]`);
            });

            // reset state komponen item-suggest di row baru
            const wraps = newRow.querySelectorAll('.item-suggest-wrap');
            wraps.forEach(wrap => {
                wrap.removeAttribute('data-suggest-inited');
            });

            bundleRows.appendChild(newRow);
            attachRowListeners(newRow);
            renumberRows();
            recalcAll();

            // re-init komponen suggest
            if (window.initItemSuggestInputs) {
                window.initItemSuggestInputs();
            }

            // fokus ke input Item Jadi di baris baru
            const newSuggestInput = newRow.querySelector('.js-item-suggest-input');
            if (newSuggestInput) {
                newSuggestInput.focus();
                newSuggestInput.select();
            }
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

        // =========================
        // LOGIKA MODAL & SUBMIT
        // =========================
        const formEl = document.querySelector('.js-cutting-form');
        const submitTriggers = document.querySelectorAll('.js-cutting-submit-trigger');
        const confirmModal = document.getElementById('cutting-confirm-modal');
        const summaryModal = document.getElementById('cutting-summary-modal');

        const summaryTotalBundleEl = document.getElementById('summary-total-bundle');
        const summaryTotalQtyEl = document.getElementById('summary-total-qty');
        const summaryTotalUsedEl = document.getElementById('summary-total-used');
        const summaryItemsListEl = document.getElementById('summary-items-list');

        function openModal(modal) {
            if (!modal) return;
            modal.style.display = 'flex';
        }

        function closeModal(modal) {
            if (!modal) return;
            modal.style.display = 'none';
        }

        function buildSummary() {
            if (!bundleRows) return;

            const rows = bundleRows.querySelectorAll('tr');
            const count = rows.length;

            let totalQtyPcs = 0;
            const items = [];

            rows.forEach(tr => {
                const qtyInput = tr.querySelector('.bundle-qty');
                const suggestInput = tr.querySelector('.js-item-suggest-input');

                if (!qtyInput) return;

                let qty = parseInt(qtyInput.value || '0', 10);
                if (isNaN(qty) || qty <= 0) return;

                const label = (suggestInput && suggestInput.value) ? suggestInput.value : '(belum pilih item)';
                totalQtyPcs += qty;

                items.push({
                    label,
                    qty
                });
            });

            const perRow = (count > 0 && lotQty > 0) ? (lotQty / count) : 0;
            const totalUsed = perRow * count;

            summaryTotalBundleEl.textContent = count;
            summaryTotalQtyEl.textContent = totalQtyPcs.toLocaleString('id-ID');
            summaryTotalUsedEl.textContent = totalUsed ?
                totalUsed.toFixed(2).replace('.', ',') + ' Kg' :
                '0,00 Kg';

            summaryItemsListEl.innerHTML = '';

            if (!items.length) {
                const li = document.createElement('li');
                li.className = 'text-muted';
                li.textContent = 'Belum ada item dengan qty > 0.';
                summaryItemsListEl.appendChild(li);
            } else {
                items.forEach(it => {
                    const li = document.createElement('li');
                    li.textContent = `${it.label} : ${it.qty.toLocaleString('id-ID')} pcs`;
                    summaryItemsListEl.appendChild(li);
                });
            }
        }

        // klik tombol submit (desktop & mobile) → buka modal konfirmasi
        submitTriggers.forEach(btn => {
            btn.addEventListener('click', function() {
                openModal(confirmModal);
            });
        });

        // event handler di confirm modal
        if (confirmModal) {
            confirmModal.addEventListener('click', function(e) {
                const action = e.target.getAttribute('data-action');
                if (!action) return;

                if (action === 'cancel-confirm') {
                    closeModal(confirmModal);
                } else if (action === 'confirm-save') {
                    closeModal(confirmModal);
                    // bangun ringkasan + buka modal summary
                    buildSummary();
                    openModal(summaryModal);
                }
            });
        }

        // event handler di summary modal
        if (summaryModal) {
            summaryModal.addEventListener('click', function(e) {
                const action = e.target.getAttribute('data-action');
                if (!action) return;

                if (action === 'back-from-summary') {
                    closeModal(summaryModal);
                } else if (action === 'submit-final') {
                    // disable semua tombol submit biar tidak double
                    submitTriggers.forEach(btn => btn.disabled = true);
                    const finalButtons = summaryModal.querySelectorAll('button');
                    finalButtons.forEach(btn => btn.disabled = true);

                    if (formEl) {
                        formEl.submit();
                    }
                }
            });
        }
    </script>
@endpush
