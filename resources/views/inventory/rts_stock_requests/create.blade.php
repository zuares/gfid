@extends('layouts.app')

@section('title', 'Stock Request • Gudang Packing (RTS)')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .75rem .75rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.10) 0,
                    rgba(45, 212, 191, 0.08) 26%,
                    #f9fafb 60%);
        }

        .card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.22);
            box-shadow:
                0 8px 24px rgba(15, 23, 42, 0.06),
                0 0 0 1px rgba(15, 23, 42, 0.02);
        }

        .card-header {
            padding: 1rem 1.25rem .75rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.25);
        }

        .card-body {
            padding: .75rem 1.25rem 1rem;
        }

        .section-title {
            font-size: .9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: rgba(100, 116, 139, 1);
        }

        .badge-warehouse {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .15rem .5rem;
            border-radius: 999px;
            font-size: .75rem;
            border: 1px solid rgba(148, 163, 184, 0.55);
            background: color-mix(in srgb, var(--card) 80%, rgba(59, 130, 246, .12));
        }

        .badge-warehouse span.code {
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: .75rem 1.25rem;
            align-items: flex-end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: .25rem;
        }

        .form-group label {
            font-size: .8rem;
            font-weight: 500;
            color: rgba(71, 85, 105, 1);
        }

        .form-control,
        select,
        textarea {
            border-radius: .6rem;
            border: 1px solid rgba(148, 163, 184, 0.6);
            padding: .4rem .6rem;
            font-size: .85rem;
            background: var(--background);
        }

        textarea.form-control {
            min-height: 70px;
            resize: vertical;
        }

        .help-text {
            font-size: .75rem;
            color: rgba(148, 163, 184, 1);
        }

        .text-error {
            color: #ef4444;
            font-size: .75rem;
            margin-top: .1rem;
        }

        .input-error {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 1px rgba(239, 68, 68, .2);
        }

        .table-wrap {
            margin-top: .75rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            overflow: hidden;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: .82rem;
        }

        .table thead {
            background: color-mix(in srgb, var(--card) 80%, rgba(15, 23, 42, 0.06));
        }

        .table th,
        .table td {
            padding: .45rem .6rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.25);
            vertical-align: middle;
        }

        .table th {
            text-align: left;
            font-weight: 600;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: rgba(100, 116, 139, 1);
        }

        .mono {
            font-variant-numeric: tabular-nums;
        }

        .stock-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            border-radius: 999px;
            padding: .15rem .5rem;
            font-size: .75rem;
            border: 1px dashed rgba(148, 163, 184, 0.8);
            color: rgba(15, 23, 42, 0.8);
        }

        .stock-pill .label {
            color: rgba(100, 116, 139, 1);
        }

        .stock-pill .value {
            font-weight: 600;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            border-radius: 999px;
            border: 1px solid transparent;
            font-size: .8rem;
            font-weight: 500;
            padding: .35rem .9rem;
            cursor: pointer;
            background: rgba(37, 99, 235, 1);
            color: white;
        }

        .btn-outline {
            background: transparent;
            border-color: rgba(148, 163, 184, 0.9);
            color: rgba(51, 65, 85, 1);
        }

        .btn-ghost {
            background: transparent;
            border-color: transparent;
            color: rgba(148, 163, 184, 1);
        }

        .btn-icon {
            padding-inline: .5rem;
        }

        .btn[disabled] {
            opacity: .5;
            cursor: not-allowed;
        }

        .actions-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: .75rem;
            gap: .75rem;
            flex-wrap: wrap;
        }

        .actions-row .left,
        .actions-row .right {
            display: flex;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .badge-info {
            border-radius: 999px;
            padding: .15rem .6rem;
            font-size: .75rem;
            background: rgba(59, 130, 246, 0.12);
            color: rgba(30, 64, 175, 1);
        }

        .remove-row-btn {
            color: rgba(148, 163, 184, 1);
        }

        .remove-row-btn:hover {
            color: rgba(248, 113, 113, 1);
        }

        /* Modal stok summary sederhana */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, .35);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 50;
        }

        .modal-backdrop.show {
            display: flex;
        }

        .modal {
            background: var(--card);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, .4);
            box-shadow:
                0 18px 45px rgba(15, 23, 42, 0.35),
                0 0 0 1px rgba(15, 23, 42, 0.05);
            max-width: 520px;
            width: 100%;
            padding: 1rem 1.1rem 1rem;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
            margin-bottom: .4rem;
        }

        .modal-header h3 {
            font-size: .95rem;
            font-weight: 600;
        }

        .modal-body {
            max-height: 360px;
            overflow-y: auto;
            font-size: .8rem;
        }

        .modal-close {
            border-radius: 999px;
            border: 1px solid transparent;
            width: 28px;
            height: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            cursor: pointer;
            background: transparent;
            color: rgba(148, 163, 184, 1);
        }

        .modal-close:hover {
            background: rgba(148, 163, 184, 0.16);
        }

        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary-table th,
        .summary-table td {
            padding: .3rem .3rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.25);
            text-align: left;
        }

        .summary-table th {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: rgba(100, 116, 139, 1);
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }

            .table-wrap {
                border-radius: 10px;
                overflow-x: auto;
            }

            .table {
                min-width: 700px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        <div class="card">
            <div class="card-header">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <div class="section-title">Stock Request • RTS ➝ PRD</div>
                        <div class="mt-1 text-sm text-slate-700 dark:text-slate-200">
                            Permintaan stok dari <strong>Gudang Produksi</strong> ke
                            <strong>Gudang Packing Online (RTS)</strong>.
                        </div>
                    </div>
                    <div class="hidden sm:flex flex-col items-end gap-1">
                        <div class="badge-warehouse">
                            <span class="code">{{ $prdWarehouse->code }}</span>
                            <span>{{ $prdWarehouse->name }}</span>
                        </div>
                        <div style="font-size:.9rem; opacity:.7;">↓</div>
                        <div class="badge-warehouse">
                            <span class="code">{{ $rtsWarehouse->code }}</span>
                            <span>{{ $rtsWarehouse->name }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <form id="rts-stock-request-form" method="POST" action="{{ route('rts.stock-requests.store') }}">
                @csrf

                <div class="card-body">
                    {{-- Header fields --}}
                    <div class="form-grid mb-3">
                        <div class="form-group" style="grid-column: span 3 / span 3;">
                            <label for="date">Tanggal</label>
                            <input type="date" id="date" name="date"
                                class="form-control @error('date') input-error @enderror"
                                value="{{ old('date', now()->toDateString()) }}">
                            @error('date')
                                <div class="text-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group" style="grid-column: span 4 / span 4;">
                            <label>Gudang Asal (Produksi)</label>
                            <input type="text" class="form-control"
                                value="{{ $prdWarehouse->code }} — {{ $prdWarehouse->name }}" readonly>
                            <input type="hidden" name="source_warehouse_id" id="source_warehouse_id"
                                value="{{ $prdWarehouse->id }}">
                        </div>

                        <div class="form-group" style="grid-column: span 4 / span 4;">
                            <label>Gudang Tujuan (RTS / Packing Online)</label>
                            <input type="text" class="form-control"
                                value="{{ $rtsWarehouse->code }} — {{ $rtsWarehouse->name }}" readonly>
                            <input type="hidden" name="destination_warehouse_id" value="{{ $rtsWarehouse->id }}">
                        </div>

                        <div class="form-group" style="grid-column: span 12 / span 12;">
                            <label for="notes">Catatan (opsional)</label>
                            <textarea id="notes" name="notes" class="form-control"
                                placeholder="Contoh: Replenish stok FG untuk flash sale / promo...">{{ old('notes') }}</textarea>
                        </div>
                    </div>

                    {{-- Detail lines --}}
                    <div class="mt-2">
                        <div class="flex items-center justify-between gap-2">
                            <div class="section-title">Detail Item</div>
                            <span class="badge-info">
                                Pilih Finished Goods yang tersedia di Gudang Produksi.
                            </span>
                        </div>

                        <div class="table-wrap mt-2">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th style="width: 32px;">#</th>
                                        <th style="width: 35%;">Item FG</th>
                                        <th style="width: 26%;">Stok Gudang Produksi</th>
                                        <th style="width: 18%;">Qty Request</th>
                                        <th style="width: 80px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="lines-body">
                                    @php
                                        $oldLines = old('lines', [['item_id' => null, 'qty_request' => null]]);
                                    @endphp

                                    @foreach ($oldLines as $i => $oldLine)
                                        <tr class="line-row" data-row-index="{{ $i }}">
                                            <td class="mono align-top">
                                                <span class="row-number">{{ $i + 1 }}</span>
                                            </td>
                                            <td>
                                                <select name="lines[{{ $i }}][item_id]"
                                                    class="form-control item-select" data-row-index="{{ $i }}">
                                                    <option value="">— Pilih item —</option>
                                                    @foreach ($finishedGoodsItems as $item)
                                                        <option value="{{ $item->id }}"
                                                            {{ (string) $item->id === (string) ($oldLine['item_id'] ?? '') ? 'selected' : '' }}>
                                                            {{ $item->code ?? '' }} — {{ $item->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error("lines.$i.item_id")
                                                    <div class="text-error">{{ $message }}</div>
                                                @enderror
                                            </td>
                                            <td>
                                                <div class="flex items-center gap-2">
                                                    <div class="stock-pill">
                                                        <span class="label">Stok PRD:</span>
                                                        <span class="value mono stock-display" data-available="0">-</span>
                                                        <span class="label">pcs</span>
                                                    </div>
                                                    <button type="button" class="btn-ghost btn-icon btn-show-summary"
                                                        title="Lihat stok di semua gudang"
                                                        data-row-index="{{ $i }}">
                                                        🔍
                                                    </button>
                                                </div>
                                                <div class="help-text">
                                                    Stok live saat ini, bukan stok saat request disimpan.
                                                </div>
                                            </td>
                                            <td>
                                                <input type="number" min="0" step="1"
                                                    name="lines[{{ $i }}][qty_request]"
                                                    class="form-control qty-input @error("lines.$i.qty_request") input-error @enderror"
                                                    data-row-index="{{ $i }}"
                                                    value="{{ $oldLine['qty_request'] ?? '' }}">
                                                <div class="text-error qty-warning" style="display:none;"></div>
                                                @error("lines.$i.qty_request")
                                                    <div class="text-error">{{ $message }}</div>
                                                @enderror
                                            </td>
                                            <td class="text-right">
                                                <button type="button" class="btn-ghost remove-row-btn"
                                                    data-row-index="{{ $i }}" title="Hapus baris">
                                                    ✕
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="actions-row">
                            <div class="left">
                                <button type="button" class="btn-outline" id="add-line-btn">
                                    + Tambah baris
                                </button>
                            </div>
                            <div class="right">
                                <button type="submit" class="btn">
                                    Simpan & Kirim ke Gudang Produksi
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- Modal summary stok semua gudang --}}
        <div class="modal-backdrop" id="stock-summary-backdrop">
            <div class="modal">
                <div class="modal-header">
                    <h3>Summary Stok per Gudang</h3>
                    <button type="button" class="modal-close" id="stock-summary-close">×</button>
                </div>
                <div class="modal-body">
                    <div id="stock-summary-content">
                        <div class="help-text">Pilih item terlebih dahulu, lalu klik ikon 🔍 di baris.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const finishedGoods = @json(
                $finishedGoodsItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'label' => trim(($item->code ?? '') . ' — ' . $item->name),
                    ];
                }));

            const sourceWarehouseId = {{ $prdWarehouse->id }};
            const availableUrl = @json(route('api.stock.available'));
            const summaryUrl = @json(route('api.stock.summary'));

            const linesBody = document.getElementById('lines-body');
            const addLineBtn = document.getElementById('add-line-btn');

            let currentIndex = (function() {
                const lastRow = linesBody.querySelector('tr.line-row:last-child');
                return lastRow ? parseInt(lastRow.getAttribute('data-row-index')) + 1 : 0;
            })();

            function createLineRow(index) {
                const tr = document.createElement('tr');
                tr.classList.add('line-row');
                tr.setAttribute('data-row-index', index);

                tr.innerHTML = `
                    <td class="mono align-top">
                        <span class="row-number">${index + 1}</span>
                    </td>
                    <td>
                        <select name="lines[${index}][item_id]"
                                class="form-control item-select"
                                data-row-index="${index}">
                            <option value="">— Pilih item —</option>
                            ${finishedGoods.map(it =>
                                `<option value="${it.id}">${it.label}</option>`
                            ).join('')}
                        </select>
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            <div class="stock-pill">
                                <span class="label">Stok PRD:</span>
                                <span class="value mono stock-display" data-available="0">-</span>
                                <span class="label">pcs</span>
                            </div>
                            <button type="button"
                                    class="btn-ghost btn-icon btn-show-summary"
                                    title="Lihat stok di semua gudang"
                                    data-row-index="${index}">
                                🔍
                            </button>
                        </div>
                        <div class="help-text">
                            Stok live saat ini, bukan stok saat request disimpan.
                        </div>
                    </td>
                    <td>
                        <input type="number"
                               min="0"
                               step="1"
                               name="lines[${index}][qty_request]"
                               class="form-control qty-input"
                               data-row-index="${index}">
                        <div class="text-error qty-warning" style="display:none;"></div>
                    </td>
                    <td class="text-right">
                        <button type="button"
                                class="btn-ghost remove-row-btn"
                                data-row-index="${index}"
                                title="Hapus baris">
                            ✕
                        </button>
                    </td>
                `;

                return tr;
            }

            function renumberRows() {
                const rows = linesBody.querySelectorAll('tr.line-row');
                rows.forEach((row, idx) => {
                    row.querySelector('.row-number').textContent = idx + 1;
                });
            }

            function handleAddLine() {
                const row = createLineRow(currentIndex++);
                linesBody.appendChild(row);
            }

            function findRowByIndex(rowIndex) {
                return linesBody.querySelector(`tr.line-row[data-row-index="${rowIndex}"]`);
            }

            async function fetchAvailableStock(rowIndex) {
                const row = findRowByIndex(rowIndex);
                if (!row) return;

                const select = row.querySelector('.item-select');
                const itemId = select.value;
                const stockSpan = row.querySelector('.stock-display');
                const warningEl = row.querySelector('.qty-warning');
                const qtyInput = row.querySelector('.qty-input');

                if (!itemId) {
                    stockSpan.textContent = '-';
                    stockSpan.dataset.available = '0';
                    if (warningEl) {
                        warningEl.style.display = 'none';
                    }
                    qtyInput && qtyInput.classList.remove('input-error');
                    return;
                }

                stockSpan.textContent = '…';
                stockSpan.dataset.available = '0';
                if (warningEl) {
                    warningEl.style.display = 'none';
                }
                qtyInput && qtyInput.classList.remove('input-error');

                try {
                    const url = new URL(availableUrl, window.location.origin);
                    url.searchParams.set('warehouse_id', String(sourceWarehouseId));
                    url.searchParams.set('item_id', String(itemId));

                    const res = await fetch(url.toString(), {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    if (!res.ok) {
                        throw new Error('Gagal mengambil stok');
                    }

                    const data = await res.json();
                    const available = typeof data.available === 'number' ?
                        data.available :
                        parseFloat(data.available || 0);

                    stockSpan.textContent = isNaN(available) ? '-' : available;
                    stockSpan.dataset.available = String(available);

                    // Re-check qty warning
                    if (qtyInput && qtyInput.value) {
                        validateQtyAgainstStock(qtyInput, available);
                    }

                } catch (e) {
                    stockSpan.textContent = 'ERR';
                    stockSpan.dataset.available = '0';
                    if (warningEl) {
                        warningEl.style.display = 'block';
                        warningEl.textContent = 'Gagal mengambil stok, coba lagi.';
                    }
                }
            }

            function validateQtyAgainstStock(inputEl, available) {
                const row = inputEl.closest('tr.line-row');
                if (!row) return;

                const warningEl = row.querySelector('.qty-warning');
                const value = parseFloat(inputEl.value || '0');
                const avail = typeof available === 'number' ?
                    available :
                    parseFloat(row.querySelector('.stock-display')?.dataset.available || '0');

                if (!value || isNaN(value)) {
                    inputEl.classList.remove('input-error');
                    if (warningEl) warningEl.style.display = 'none';
                    return;
                }

                if (value > avail && avail > 0) {
                    inputEl.classList.add('input-error');
                    if (warningEl) {
                        warningEl.style.display = 'block';
                        warningEl.textContent =
                            `Qty melebihi stok Gudang Produksi (stok sekarang: ${avail}).` +
                            ` Sistem tetap akan cek ulang saat disimpan.`;
                    }
                } else if (value > 0 && avail === 0) {
                    inputEl.classList.add('input-error');
                    if (warningEl) {
                        warningEl.style.display = 'block';
                        warningEl.textContent =
                            'Stok Gudang Produksi terdeteksi 0. Pastikan stok sudah masuk PRD.';
                    }
                } else {
                    inputEl.classList.remove('input-error');
                    if (warningEl) warningEl.style.display = 'none';
                }
            }

            async function showStockSummary(rowIndex) {
                const row = findRowByIndex(rowIndex);
                if (!row) return;

                const select = row.querySelector('.item-select');
                const itemId = select.value;

                if (!itemId) {
                    alert('Pilih item terlebih dahulu.');
                    return;
                }

                const backdrop = document.getElementById('stock-summary-backdrop');
                const content = document.getElementById('stock-summary-content');

                backdrop.classList.add('show');
                content.innerHTML = '<div class="help-text">Mengambil data stok...</div>';

                try {
                    const url = new URL(summaryUrl, window.location.origin);
                    url.searchParams.set('item_id', String(itemId));

                    const res = await fetch(url.toString(), {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    if (!res.ok) {
                        throw new Error('Gagal mengambil summary stok');
                    }

                    const data = await res.json();
                    const item = data.item || {};
                    const warehouses = data.warehouses || [];

                    if (!warehouses.length) {
                        content.innerHTML = `
                            <div class="help-text">
                                Tidak ada data stok yang tercatat untuk item ini.
                            </div>
                        `;
                        return;
                    }

                    const rowsHtml = warehouses.map(w => `
                        <tr>
                            <td class="mono">${w.code ?? ''}</td>
                            <td>${w.name ?? ''}</td>
                            <td class="mono">${w.on_hand ?? 0}</td>
                            <td class="mono">${w.reserved ?? 0}</td>
                            <td class="mono">${w.available ?? 0}</td>
                        </tr>
                    `).join('');

                    content.innerHTML = `
                        <div class="mb-2">
                            <div style="font-size:.78rem; color:rgba(100,116,139,1);">Item</div>
                            <div style="font-size:.88rem; font-weight:600;">
                                ${(item.code ?? '')} ${(item.name ? '— ' + item.name : '')}
                            </div>
                        </div>
                        <table class="summary-table">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Gudang</th>
                                    <th>On Hand</th>
                                    <th>Reserved</th>
                                    <th>Available</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${rowsHtml}
                            </tbody>
                        </table>
                    `;
                } catch (e) {
                    content.innerHTML = `
                        <div class="text-error">
                            Gagal mengambil data summary stok. Coba lagi beberapa saat.
                        </div>
                    `;
                }
            }

            function attachGlobalListeners() {
                // Tambah baris
                addLineBtn?.addEventListener('click', handleAddLine);

                // Delegasi event untuk body table
                linesBody.addEventListener('change', function(e) {
                    const target = e.target;

                    if (target.classList.contains('item-select')) {
                        const rowIndex = target.getAttribute('data-row-index');
                        fetchAvailableStock(rowIndex);
                    }

                    if (target.classList.contains('qty-input')) {
                        validateQtyAgainstStock(target);
                    }
                });

                linesBody.addEventListener('click', function(e) {
                    const target = e.target;

                    if (target.classList.contains('remove-row-btn')) {
                        const rowIndex = target.getAttribute('data-row-index');
                        const row = findRowByIndex(rowIndex);
                        if (row) {
                            if (linesBody.querySelectorAll('tr.line-row').length <= 1) {
                                // jangan sampai semua baris dihapus
                                row.querySelector('.item-select').value = '';
                                row.querySelector('.qty-input').value = '';
                                const stockSpan = row.querySelector('.stock-display');
                                stockSpan.textContent = '-';
                                stockSpan.dataset.available = '0';
                                const warn = row.querySelector('.qty-warning');
                                if (warn) warn.style.display = 'none';
                                return;
                            }
                            row.remove();
                            renumberRows();
                        }
                    }

                    if (target.classList.contains('btn-show-summary')) {
                        const rowIndex = target.getAttribute('data-row-index');
                        showStockSummary(rowIndex);
                    }
                });

                // Modal summary
                const backdrop = document.getElementById('stock-summary-backdrop');
                const closeBtn = document.getElementById('stock-summary-close');

                closeBtn?.addEventListener('click', () => {
                    backdrop.classList.remove('show');
                });

                backdrop?.addEventListener('click', (e) => {
                    if (e.target === backdrop) {
                        backdrop.classList.remove('show');
                    }
                });

                // Auto-fetch stok untuk baris yang sudah punya item (old input)
                const existingSelects = linesBody.querySelectorAll('.item-select');
                existingSelects.forEach(select => {
                    if (select.value) {
                        fetchAvailableStock(select.getAttribute('data-row-index'));
                    }
                });
            }

            attachGlobalListeners();
        })();
    </script>
@endpush
