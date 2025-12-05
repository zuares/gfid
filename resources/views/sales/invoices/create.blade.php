@extends('layouts.app')

@section('title', 'Buat Sales Invoice')


@push('head')
    <style>
        .card-invoice {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .35);
        }
    </style>
@endpush

@section('content')
    @php
        $fmt = fn($n) => number_format($n ?? 0, 0, ',', '.');
    @endphp

    <div class="container py-3">
        <h4 class="mb-3">Buat Sales Invoice</h4>

        @if ($errors->any())
            <div class="alert alert-danger py-2 px-3 small">
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('sales.invoices.store') }}" method="POST" id="invoice-form">
            @csrf

            {{-- HEADER --}}
            <div class="card card-invoice mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="date" class="form-control form-control-sm"
                                value="{{ old('date', now()->toDateString()) }}" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Customer</label>
                            <select name="customer_id" class="form-select form-select-sm">
                                <option value="">– Tanpa customer –</option>
                                @foreach ($customers as $c)
                                    <option value="{{ $c->id }}" @selected(old('customer_id') == $c->id)>
                                        {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                            {{-- nanti bisa di-upgrade ke quick search --}}
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Store / Channel</label>
                            <select name="store_id" class="form-select form-select-sm">
                                <option value="">– Tanpa Store –</option>
                                @foreach ($stores as $s)
                                    <option value="{{ $s->id }}" @selected(old('store_id') == $s->id)>
                                        {{ $s->code }} — {{ $s->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Warehouse</label>
                            <select name="warehouse_id" class="form-select form-select-sm" required>
                                @foreach ($warehouses as $w)
                                    <option value="{{ $w->id }}" @selected(old('warehouse_id') == $w->id)>
                                        {{ $w->code }} — {{ $w->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">PPN (%)</label>
                            <input type="number" name="tax_percent" class="form-control form-control-sm text-end"
                                value="{{ old('tax_percent', 0) }}" step="0.01" min="0" max="100">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <input type="text" name="remarks" class="form-control form-control-sm"
                                value="{{ old('remarks') }}" placeholder="Catatan invoice (opsional)">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ITEMS --}}
            <div class="card card-invoice mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Items</span>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-row">
                        + Tambah Baris
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 align-middle" id="items-table">
                            <thead>
                                <tr>
                                    <th style="width: 26%">Item</th>
                                    <th style="width: 8%">Qty</th>
                                    <th style="width: 13%">Harga</th>
                                    <th style="width: 13%">Disc</th>
                                    <th style="width: 13%">Subtotal</th>
                                    <th style="width: 13%">HPP / pcs</th>
                                    <th style="width: 13%">Margin</th>
                                    <th style="width: 3%"></th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- row template pertama --}}
                                <tr class="item-row">
                                    <td>
                                        <select name="items[0][item_id]" class="form-select form-select-sm select-item">
                                            @foreach ($items as $i)
                                                <option value="{{ $i->id }}"
                                                    data-hpp="{{ (float) ($i->hpp_unit ?? 0) }}">
                                                    {{ $i->code }} — {{ $i->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" min="1" name="items[0][qty]"
                                            class="form-control form-control-sm text-end input-qty"
                                            value="{{ old('items.0.qty', 1) }}">
                                    </td>
                                    <td>
                                        <input type="number" min="1" step="100" name="items[0][unit_price]"
                                            class="form-control form-control-sm text-end input-price"
                                            value="{{ old('items.0.unit_price', 0) }}" placeholder="Harga jual" required>
                                    </td>
                                    <td>
                                        <input type="number" min="0" step="100" name="items[0][line_discount]"
                                            class="form-control form-control-sm text-end input-disc"
                                            value="{{ old('items.0.line_discount', 0) }}">
                                    </td>
                                    <td class="text-end">
                                        <span class="line-subtotal">0</span>
                                    </td>
                                    <td class="text-end">
                                        <span class="line-hpp">0</span>
                                    </td>
                                    <td class="text-end">
                                        <span class="line-margin">0</span>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">
                                            &times;
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {{-- SUMMARY --}}
                    <div class="p-3 border-top">
                        <div class="row justify-content-end">
                            <div class="col-md-5">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Subtotal</span>
                                    <strong><span id="summary-subtotal">0</span></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1 align-items-center">
                                    <span>Diskon header</span>
                                    <div class="d-flex gap-2 align-items-center">
                                        <input type="number" name="header_discount"
                                            class="form-control form-control-sm text-end" id="input-header-discount"
                                            value="{{ old('header_discount', 0) }}" step="100" min="0"
                                            style="width: 120px;">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>PPN</span>
                                    <span id="summary-tax">0</span>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Perkiraan Margin</span>
                                    <span id="summary-margin">0</span>
                                </div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between">
                                    <span><strong>Grand Total</strong></span>
                                    <strong><span id="summary-grand-total">0</span></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    Simpan Invoice
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            let rowIndex = 1;

            // Map item_id => HPP per unit dari PHP ke JS
            const HPP_MAP = @json(
                $items->mapWithKeys(function ($i) {
                    return [$i->id => (float) ($i->hpp_unit ?? 0)];
                }));

            const formatNumber = (num) => {
                num = Number(num || 0);
                return num.toLocaleString('id-ID', {
                    maximumFractionDigits: 0
                });
            };

            const getRowItemId = (tr) => {
                const sel = tr.querySelector('.select-item');
                return sel ? sel.value : null;
            };

            const recalcRow = (tr) => {
                const qty = parseFloat(tr.querySelector('.input-qty').value || 0);
                const price = parseFloat(tr.querySelector('.input-price').value || 0);
                const disc = parseFloat(tr.querySelector('.input-disc').value || 0);

                // Ambil HPP per unit dari map
                const itemId = getRowItemId(tr);
                let hppUnit = 0;
                if (itemId && HPP_MAP[itemId]) {
                    hppUnit = parseFloat(HPP_MAP[itemId]) || 0;
                } else {
                    // fallback ke data-hpp di option kalau ada
                    const sel = tr.querySelector('.select-item');
                    const opt = sel ? sel.selectedOptions[0] : null;
                    if (opt && opt.dataset.hpp) {
                        hppUnit = parseFloat(opt.dataset.hpp || 0);
                    }
                }

                let subtotal = (qty * price) - disc;
                if (subtotal < 0) subtotal = 0;

                const costTotal = hppUnit * qty;
                const margin = subtotal - costTotal;

                tr.querySelector('.line-subtotal').innerText = formatNumber(subtotal);
                tr.querySelector('.line-hpp').innerText = formatNumber(hppUnit);
                tr.querySelector('.line-margin').innerText = formatNumber(margin);

                return {
                    subtotal,
                    margin
                };
            };

            const recalcAll = () => {
                const rows = document.querySelectorAll('#items-table tbody tr.item-row');
                let subtotal = 0;
                let totalMargin = 0;

                rows.forEach(tr => {
                    const res = recalcRow(tr);
                    subtotal += res.subtotal;
                    totalMargin += res.margin;
                });

                document.getElementById('summary-subtotal').innerText = formatNumber(subtotal);

                const headerDiscountInput = document.getElementById('input-header-discount');
                const headerDiscount = parseFloat(headerDiscountInput.value || 0);
                let effectiveDisc = headerDiscount;
                if (effectiveDisc > subtotal) {
                    effectiveDisc = subtotal;
                    headerDiscountInput.value = effectiveDisc;
                }

                const taxPercentInput = document.querySelector('input[name="tax_percent"]');
                const taxPercent = parseFloat(taxPercentInput.value || 0);
                const dpp = subtotal - effectiveDisc;
                const tax = dpp * taxPercent / 100;
                const grandTotal = dpp + tax;

                document.getElementById('summary-tax').innerText = formatNumber(tax);
                document.getElementById('summary-grand-total').innerText = formatNumber(grandTotal);
                document.getElementById('summary-margin').innerText = formatNumber(totalMargin);
            };

            const tbody = document.querySelector('#items-table tbody');
            const btnAddRow = document.getElementById('btn-add-row');

            btnAddRow.addEventListener('click', () => {
                const trLast = tbody.querySelector('tr.item-row:last-child');
                const clone = trLast.cloneNode(true);

                clone.querySelectorAll('select, input').forEach(el => {
                    if (!el.name) return;

                    el.name = el.name.replace(/\[\d+\]/, '[' + rowIndex + ']');

                    if (el.classList.contains('input-qty')) {
                        el.value = 1;
                    } else if (el.classList.contains('input-price') || el.classList.contains(
                            'input-disc')) {
                        el.value = 0;
                    } else if (el.classList.contains('select-item')) {
                        el.selectedIndex = 0;
                    }
                });

                clone.querySelector('.line-subtotal').innerText = '0';
                clone.querySelector('.line-hpp').innerText = '0';
                clone.querySelector('.line-margin').innerText = '0';

                tbody.appendChild(clone);

                rowIndex++;
                recalcAll();
            });

            // Perubahan qty/harga/discount
            tbody.addEventListener('input', (e) => {
                if (e.target.matches('.input-qty, .input-price, .input-disc')) {
                    recalcAll();
                }
            });

            // Perubahan item (ganti HPP & margin)
            tbody.addEventListener('change', (e) => {
                if (e.target.matches('.select-item')) {
                    recalcAll();
                }
            });

            // Hapus baris
            tbody.addEventListener('click', (e) => {
                if (e.target.closest('.btn-remove-row')) {
                    const rows = tbody.querySelectorAll('tr.item-row');
                    if (rows.length <= 1) {
                        // minimal 1 baris
                        return;
                    }
                    e.target.closest('tr').remove();
                    recalcAll();
                }
            });

            // Header discount & tax
            document.getElementById('input-header-discount').addEventListener('input', recalcAll);
            document.querySelector('input[name="tax_percent"]').addEventListener('input', recalcAll);

            // Validasi harga > 0 sebelum submit
            const form = document.getElementById('invoice-form');
            form.addEventListener('submit', (e) => {
                let hasError = false;
                const priceInputs = form.querySelectorAll('.input-price');
                priceInputs.forEach((inp) => {
                    const val = parseFloat(inp.value || 0);
                    if (val <= 0) {
                        hasError = true;
                        inp.classList.add('is-invalid');
                    } else {
                        inp.classList.remove('is-invalid');
                    }
                });

                if (hasError) {
                    e.preventDefault();
                    alert('Harga jual tiap baris wajib lebih besar dari 0.');
                }
            });

            // initial calc
            recalcAll();
        })();
    </script>
@endpush
