{{-- resources/views/production/packing_jobs/create.blade.php --}}
@extends('layouts.app')

@php
    /** @var \App\Models\PackingJob|null $job */
    $isEdit = isset($job);
    $pageTitle = $isEdit ? 'Edit Packing Job' : 'Packing Job Baru';
@endphp

@section('title', 'Produksi • ' . $pageTitle)

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding-block: .75rem 1.5rem;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .help {
            color: var(--muted);
            font-size: .84rem;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .page-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
        }

        .page-subtitle {
            font-size: .85rem;
            color: var(--muted);
        }

        .table-wrap {
            overflow-x: auto;
        }

        .bundle-row {
            transition: background-color 150ms ease, box-shadow 150ms ease;
        }

        .bundle-row.is-new {
            background: color-mix(in srgb, var(--primary, #0d6efd) 5%, var(--card) 95%);
        }

        .btn-icon {
            padding-inline: .5rem;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .5rem;
            }

            .page-header {
                align-items: flex-start;
            }

            .page-title {
                font-size: 1rem;
            }

            .page-subtitle {
                font-size: .8rem;
            }

            .table-wrap {
                font-size: .86rem;
            }

            .btn-icon span.label {
                display: none;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="card p-3 mb-3">
            <div class="page-header">
                <div>
                    <h1 class="page-title">{{ $pageTitle }}</h1>
                    <div class="page-subtitle">
                        Packing bersifat <strong>status</strong>, stok tetap berada di gudang produksi
                        <strong>WH-PRD</strong>.
                    </div>
                    @if ($isEdit)
                        <div class="help mt-1">
                            Kode: <span class="mono">{{ $job->code }}</span> • Status:
                            <span class="badge rounded-pill bg-{{ $job->status === 'posted' ? 'success' : 'secondary' }}">
                                {{ $job->status }}
                            </span>
                        </div>
                    @endif
                </div>
                <div class="d-flex flex-column align-items-end gap-2">
                    <a href="{{ route('production.packing_jobs.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-list-ul me-1"></i>
                        <span class="label">Semua Packing Job</span>
                    </a>
                    @if ($isEdit)
                        <a href="{{ route('production.packing_jobs.show', $job->id) }}"
                            class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-eye me-1"></i>
                            <span class="label">Lihat Detail</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- FORM HEADER --}}
        <div class="card p-3 mb-3">
            @php
                $action = $isEdit
                    ? route('production.packing_jobs.update', $job->id)
                    : route('production.packing_jobs.store');
                $method = $isEdit ? 'PUT' : 'POST';

                $oldLines = old('lines', $lines ?? []);
            @endphp

            <form method="post" action="{{ $action }}" id="packing-form">
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="date"
                            class="form-control form-control-sm @error('date') is-invalid @enderror"
                            value="{{ old('date', $date ?? now()->format('Y-m-d')) }}" required>
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="help mt-1">
                            Tanggal dokumen packing.
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Channel</label>
                        <input type="text" name="channel"
                            class="form-control form-control-sm @error('channel') is-invalid @enderror"
                            value="{{ old('channel', $isEdit ? $job->channel : null) }}" list="channel-options"
                            placeholder="Contoh: TOKO / ONLINE">
                        <datalist id="channel-options">
                            <option value="TOKO"></option>
                            <option value="RESELLER"></option>
                            <option value="WHOLESALE"></option>
                            <option value="INTERNAL/SAMPLE"></option>
                            <option value="ONLINE - WA"></option>
                            <option value="ONLINE - IG"></option>
                            <option value="ONLINE - Marketplace"></option>
                            <option value="URGENT"></option>
                        </datalist>
                        @error('channel')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="help mt-1">
                            Rekomendasi: TOKO, RESELLER, WHOLESALE, INTERNAL/SAMPLE, ONLINE - WA/IG/Marketplace, URGENT.
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Referensi</label>
                        <input type="text" name="reference"
                            class="form-control form-control-sm @error('reference') is-invalid @enderror"
                            value="{{ old('reference', $isEdit ? $job->reference : null) }}"
                            placeholder="No. order / catatan referensi">
                        @error('reference')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="help mt-1">
                            Misal: ID order marketplace, nomor nota toko, nama customer, dll.
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" rows="2" class="form-control form-control-sm @error('notes') is-invalid @enderror"
                            placeholder="Catatan tambahan (opsional)">{{ old('notes', $isEdit ? $job->notes : null) }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                {{-- TABEL DETAIL BARIS PACKING --}}
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="fw-semibold">Detail Packing</div>
                        <div class="d-flex gap-2">
                            <select id="stock-picker" class="form-select form-select-sm" style="min-width: 220px;">
                                <option value="">+ Tambah baris dari stok WH-PRD...</option>
                                @foreach ($stocks as $stock)
                                    @php
                                        $item = $stock->item;
                                        $label = $item
                                            ? trim(
                                                ($item->code ?? '') .
                                                    ' — ' .
                                                    ($item->name ?? '') .
                                                    ' ' .
                                                    ($item->color ?? ''),
                                            )
                                            : 'ITEM-' . $stock->item_id;
                                    @endphp
                                    <option value="{{ $stock->item_id }}" data-label="{{ $label }}"
                                        data-balance="{{ (float) $stock->qty }}">
                                        {{ $label }} (stok: {{ number_format($stock->qty) }})
                                    </option>
                                @endforeach
                            </select>

                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-add-row">
                                <i class="bi bi-plus-circle me-1"></i>
                                <span class="label">Tambah Baris</span>
                            </button>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table class="table table-sm align-middle mb-0" id="lines-table">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 1%;">#</th>
                                    <th>Item</th>
                                    <th class="text-end">Saldo WH-PRD</th>
                                    <th class="text-end">Qty Packed</th>
                                    <th>Catatan</th>
                                    <th style="width: 1%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($oldLines as $i => $line)
                                    @php
                                        $nameBase = "lines[$i]";
                                        $itemId = $line['item_id'] ?? null;
                                        $itemLabel = $line['item_label'] ?? '';
                                        // Untuk old() ketika validasi gagal, fg_balance & qty_packed sudah string
                                        $fgBalance = $line['fg_balance'] ?? ($line['qty_fg'] ?? 0);
                                        $qtyPacked = $line['qty_packed'] ?? 0;
                                        $notesLine = $line['notes'] ?? null;
                                    @endphp
                                    <tr class="bundle-row">
                                        <td class="text-muted small align-middle index-col">{{ $i + 1 }}</td>
                                        <td>
                                            <div class="small fw-semibold">
                                                {{ $itemLabel }}
                                            </div>
                                            <input type="hidden" name="{{ $nameBase }}[item_id]"
                                                value="{{ $itemId }}">
                                            <input type="hidden" name="{{ $nameBase }}[item_label]"
                                                value="{{ $itemLabel }}">
                                            <input type="hidden" name="{{ $nameBase }}[fg_balance]"
                                                value="{{ $fgBalance }}">
                                        </td>
                                        <td class="text-end mono">
                                            {{ number_format((float) $fgBalance) }}
                                        </td>
                                        <td class="text-end">
                                            <input type="number" step="0.01" min="0"
                                                class="form-control form-control-sm text-end"
                                                name="{{ $nameBase }}[qty_packed]" value="{{ $qtyPacked }}">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm"
                                                name="{{ $nameBase }}[notes]" value="{{ $notesLine }}"
                                                placeholder="Catatan baris (opsional)">
                                        </td>
                                        <td>
                                            <button type="button"
                                                class="btn btn-sm btn-outline-danger btn-icon btn-remove-row"
                                                title="Hapus baris">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="empty-row">
                                        <td colspan="6" class="text-center text-muted py-3">
                                            Belum ada baris. Pilih item dari stok WH-PRD di atas untuk menambahkan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @error('lines')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                    @foreach ($errors->get('lines.*.qty_packed') as $fieldErrors)
                        @foreach ($fieldErrors as $fieldError)
                            <div class="text-danger small mt-1">{{ $fieldError }}</div>
                        @endforeach
                    @endforeach
                </div>

                {{-- ACTIONS --}}
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="help">
                        Setelah disimpan sebagai <strong>draft</strong> kamu bisa cek lagi, lalu posting
                        (status saja, tidak mengubah stok WH-PRD).
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('production.packing_jobs.index') }}" class="btn btn-sm btn-outline-secondary">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-sm btn-primary">
                            {{ $isEdit ? 'Update Draft' : 'Simpan Draft' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const tableBody = document.querySelector('#lines-table tbody');
            const stockPicker = document.getElementById('stock-picker');
            const addButton = document.getElementById('btn-add-row');

            function getNextIndex() {
                const rows = tableBody.querySelectorAll('tr.bundle-row');
                return rows.length;
            }

            function refreshIndices() {
                tableBody.querySelectorAll('tr.bundle-row').forEach((row, idx) => {
                    const idxCell = row.querySelector('.index-col');
                    if (idxCell) {
                        idxCell.textContent = idx + 1;
                    }
                    // rename input names
                    row.querySelectorAll('input').forEach((input) => {
                        const name = input.getAttribute('name');
                        if (!name) return;
                        const newName = name.replace(/lines\[\d+]/, 'lines[' + idx + ']');
                        input.setAttribute('name', newName);
                    });
                });
            }

            function ensureNotEmptyMessage() {
                const rows = tableBody.querySelectorAll('tr.bundle-row');
                const emptyRow = tableBody.querySelector('tr.empty-row');
                if (rows.length === 0) {
                    if (!emptyRow) {
                        const tr = document.createElement('tr');
                        tr.classList.add('empty-row');
                        tr.innerHTML = `
                            <td colspan="6" class="text-center text-muted py-3">
                                Belum ada baris. Pilih item dari stok WH-PRD di atas untuk menambahkan.
                            </td>
                        `;
                        tableBody.appendChild(tr);
                    }
                } else if (emptyRow) {
                    emptyRow.remove();
                }
            }

            function addRowFromStock(stockOption) {
                if (!stockOption || !stockOption.value) return;

                const itemId = stockOption.value;
                const itemLabel = stockOption.dataset.label || stockOption.textContent.trim();
                const balance = parseFloat(stockOption.dataset.balance || '0') || 0;

                // Cek apakah sudah ada baris dengan item_id sama
                const existing = Array.from(tableBody.querySelectorAll('tr.bundle-row')).find((row) => {
                    const hiddenItem = row.querySelector('input[name*="[item_id]"]');
                    return hiddenItem && hiddenItem.value == itemId;
                });

                if (existing) {
                    existing.classList.add('is-new');
                    setTimeout(() => existing.classList.remove('is-new'), 400);
                    return;
                }

                const index = getNextIndex();
                const nameBase = 'lines[' + index + ']';

                const tr = document.createElement('tr');
                tr.classList.add('bundle-row', 'is-new');
                tr.innerHTML = `
                    <td class="text-muted small align-middle index-col">${index + 1}</td>
                    <td>
                        <div class="small fw-semibold">${itemLabel}</div>
                        <input type="hidden" name="${nameBase}[item_id]" value="${itemId}">
                        <input type="hidden" name="${nameBase}[item_label]" value="${itemLabel}">
                        <input type="hidden" name="${nameBase}[fg_balance]" value="${balance}">
                    </td>
                    <td class="text-end mono">
                        ${balance.toLocaleString('id-ID')}
                    </td>
                    <td class="text-end">
                        <input
                            type="number"
                            step="0.01"
                            min="0"
                            class="form-control form-control-sm text-end"
                            name="${nameBase}[qty_packed]"
                            value="${balance}"
                        >
                    </td>
                    <td>
                        <input
                            type="text"
                            class="form-control form-control-sm"
                            name="${nameBase}[notes]"
                            placeholder="Catatan baris (opsional)"
                        >
                    </td>
                    <td>
                        <button type="button"
                                class="btn btn-sm btn-outline-danger btn-icon btn-remove-row"
                                title="Hapus baris">
                            <i class="bi bi-x"></i>
                        </button>
                    </td>
                `;
                tableBody.appendChild(tr);

                setTimeout(() => tr.classList.remove('is-new'), 400);
                ensureNotEmptyMessage();
            }

            addButton?.addEventListener('click', function() {
                const opt = stockPicker.options[stockPicker.selectedIndex];
                addRowFromStock(opt);
            });

            stockPicker?.addEventListener('change', function() {
                const opt = stockPicker.options[stockPicker.selectedIndex];
                addRowFromStock(opt);
            });

            tableBody.addEventListener('click', function(e) {
                if (e.target.closest('.btn-remove-row')) {
                    const row = e.target.closest('tr');
                    row?.remove();
                    refreshIndices();
                    ensureNotEmptyMessage();
                }
            });

            // init
            ensureNotEmptyMessage();
        })();
    </script>
@endpush
