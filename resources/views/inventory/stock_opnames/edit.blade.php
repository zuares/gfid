@extends('layouts.app')

@section('title', 'Counting Stock Opname • ' . $opname->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .75rem .75rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.14) 0,
                    rgba(45, 212, 191, 0.10) 26%,
                    #f9fafb 60%);
        }

        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.30);
            box-shadow:
                0 10px 26px rgba(15, 23, 42, 0.06),
                0 0 0 1px rgba(15, 23, 42, 0.03);
        }

        .badge-status {
            font-size: .7rem;
            padding: .18rem .5rem;
            border-radius: 999px;
            font-weight: 600;
        }

        .badge-status--draft {
            background: rgba(148, 163, 184, 0.2);
            color: #475569;
        }

        .badge-status--counting {
            background: rgba(59, 130, 246, 0.16);
            color: #1d4ed8;
        }

        .badge-status--reviewed {
            background: rgba(234, 179, 8, 0.18);
            color: #854d0e;
        }

        .badge-status--finalized {
            background: rgba(22, 163, 74, 0.18);
            color: #15803d;
        }

        .pill-label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #94a3b8;
        }

        .text-mono {
            font-variant-numeric: tabular-nums;
        }

        .table-wrap {
            margin-top: .75rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .24);
            overflow: hidden;
        }

        .table thead th {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: rgba(100, 116, 139, 1);
            background: rgba(15, 23, 42, 0.02);
        }

        .diff-plus {
            color: #16a34a;
        }

        .diff-minus {
            color: #dc2626;
        }

        .autosave-indicator {
            font-size: .75rem;
            color: #64748b;
        }

        .autosave-indicator span {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
        }

        .autosave-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: #22c55e;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .5rem;
            }

            .table thead {
                display: none;
            }

            .table tbody tr {
                display: block;
                border-bottom: 1px solid rgba(148, 163, 184, .25);
                padding: .35rem .75rem;
            }

            .table tbody tr:last-child {
                border-bottom: none;
            }

            .table tbody td {
                display: flex;
                justify-content: space-between;
                gap: .75rem;
                padding: .15rem 0;
                border-top: none;
                font-size: .85rem;
            }

            .table tbody td::before {
                content: attr(data-label);
                font-weight: 500;
                color: #64748b;
            }
        }
    </style>
@endpush

@section('content')
    @php
        // Kalau sudah reviewed/finalized, input dibuat readonly
        $isReadonly = in_array($opname->status, ['reviewed', 'finalized']);
    @endphp

    <div class="page-wrap">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <a href="{{ route('inventory.stock_opnames.show', $opname) }}" class="btn btn-sm btn-link px-0 mb-1">
                    ← Kembali ke detail
                </a>
                <h1 class="h5 mb-1">
                    Counting Stock Opname • {{ $opname->code }}
                </h1>
                <p class="text-muted mb-0" style="font-size: .86rem;">
                    @if ($isReadonly)
                        Dokumen ini sudah {{ $opname->status === 'finalized' ? 'difinalkan' : 'direview' }}.
                        Data ditampilkan hanya untuk review.
                    @else
                        Input hasil hitung fisik per item. Draft akan otomatis tersimpan di browser.
                    @endif
                </p>
            </div>
            <div class="text-end">
                @php
                    $statusClass = match ($opname->status) {
                        'draft' => 'badge-status badge-status--draft',
                        'counting' => 'badge-status badge-status--counting',
                        'reviewed' => 'badge-status badge-status--reviewed',
                        'finalized' => 'badge-status badge-status--finalized',
                        default => 'badge-status badge-status--draft',
                    };
                @endphp

                <div class="mb-1">
                    <span class="{{ $statusClass }}">{{ ucfirst($opname->status) }}</span>
                </div>
                @unless ($isReadonly)
                    <div class="autosave-indicator">
                        <span id="autosave-status">
                            <span class="autosave-dot"></span>
                            Draft aktif (local)
                        </span>
                    </div>
                @endunless
            </div>
        </div>

        <form action="{{ route('inventory.stock_opnames.update', $opname) }}" method="POST" id="opname-form">
            @csrf
            @method('PUT')

            <div class="card card-main mb-3">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="pill-label mb-1">Kode Dokumen</div>
                            <div class="fw-semibold text-mono">
                                {{ $opname->code }}
                            </div>

                            <div class="pill-label mt-3 mb-1">Tanggal Opname</div>
                            <div>
                                {{ $opname->date?->format('d M Y') ?? '-' }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="pill-label mb-1">Gudang</div>
                            <div class="fw-semibold">
                                {{ $opname->warehouse?->code ?? '-' }}
                            </div>
                            <div class="text-muted" style="font-size: .86rem;">
                                {{ $opname->warehouse?->name }}
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="pill-label mb-1">Catatan (opsional)</div>
                            <textarea name="notes" class="form-control form-control-sm" rows="3"
                                placeholder="Catatan tambahan untuk sesi opname ini (misal: shift, area rak, dsb)"
                                @if ($isReadonly) readonly @endif>{{ old('notes', $opname->notes) }}</textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            @unless ($isReadonly)
                                <button type="submit" class="btn btn-sm btn-primary">
                                    Simpan Perubahan
                                </button>

                                @if (in_array($opname->status, ['draft', 'counting']))
                                    <button type="submit" name="mark_reviewed" value="1"
                                        class="btn btn-sm btn-outline-primary ms-1" id="btn-mark-reviewed">
                                        Simpan &amp; Tandai Selesai Counting
                                    </button>
                                @endif
                            @endunless
                        </div>
                        <div class="d-flex gap-2">
                            @unless ($isReadonly)
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-clear-draft">
                                    Hapus Draft Browser
                                </button>
                            @endunless
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-main">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div>
                            <h2 class="h6 mb-0">
                                Input Qty Fisik Per Item
                            </h2>
                            <div class="text-muted" style="font-size: .78rem;">
                                Gunakan titik untuk desimal (contoh: <span class="text-mono">12.50</span>).
                                Saat kolom Qty Fisik diklik, nilai lama akan otomatis terblok supaya mudah diganti.
                            </div>
                        </div>
                        @php
                            $totalLines = $opname->lines->count();
                            $countedLines = $opname->lines->whereNotNull('physical_qty')->count();
                        @endphp
                        <div class="text-muted" style="font-size: .8rem;">
                            <span id="counted-summary">
                                {{ $countedLines }} / {{ $totalLines }} item sudah diisi
                            </span>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table class="table table-sm mb-0 align-middle" id="lines-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">#</th>
                                    <th>Item</th>
                                    <th class="text-end">Qty Sistem</th>
                                    <th class="text-end" style="width: 140px;">Qty Fisik</th>
                                    <th class="text-end">Selisih</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($opname->lines as $index => $line)
                                    @php
                                        // pakai accessor di model: getDifferenceAttribute()
                                        $diff = $line->difference;
                                        $hasPhysical = !is_null($line->physical_qty);
                                        $diffDisplay =
                                            $diff > 0 ? '+' . number_format($diff, 2) : number_format($diff, 2);
                                        $diffClass = $diff > 0 ? 'diff-plus' : ($diff < 0 ? 'diff-minus' : '');
                                        $inputNamePrefix = "lines[{$line->id}]";
                                    @endphp
                                    <tr data-line-id="{{ $line->id }}">
                                        <td data-label="#">
                                            {{ $index + 1 }}
                                        </td>
                                        <td data-label="Item">
                                            <div class="fw-semibold">
                                                {{ $line->item?->code ?? '-' }}
                                            </div>
                                            <div class="text-muted" style="font-size: .82rem;">
                                                {{ $line->item?->name ?? '' }}
                                            </div>
                                        </td>
                                        <td data-label="Qty sistem" class="text-end text-mono">
                                            <span class="system-qty" data-system-qty="{{ $line->system_qty }}">
                                                {{ number_format($line->system_qty, 2) }}
                                            </span>
                                            <input type="hidden" name="{{ $inputNamePrefix }}[system_qty]"
                                                value="{{ $line->system_qty }}">
                                        </td>
                                        <td data-label="Qty fisik" class="text-end">
                                            <input type="number" name="{{ $inputNamePrefix }}[physical_qty]"
                                                class="form-control form-control-sm text-end physical-input" step="0.01"
                                                min="0" autocomplete="off" data-line-id="{{ $line->id }}"
                                                value="{{ old($inputNamePrefix . '.physical_qty', $line->physical_qty) }}"
                                                @if ($isReadonly) readonly @endif>
                                        </td>
                                        <td data-label="Selisih" class="text-end text-mono">
                                            <span class="diff-display {{ $diffClass }}">
                                                @if ($hasPhysical)
                                                    {{ $diffDisplay }}
                                                @else
                                                    -
                                                @endif
                                            </span>
                                            <input type="hidden" name="{{ $inputNamePrefix }}[difference_qty]"
                                                class="diff-input" value="{{ $hasPhysical ? $diff : '' }}">
                                        </td>
                                        <td data-label="Catatan">
                                            <input type="text" name="{{ $inputNamePrefix }}[notes]"
                                                class="form-control form-control-sm note-input"
                                                value="{{ old($inputNamePrefix . '.notes', $line->notes) }}"
                                                @if ($isReadonly) readonly @endif>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @unless ($isReadonly)
                        <div class="mt-2 text-muted" style="font-size: .78rem;">
                            Draft Qty Fisik disimpan otomatis di browser (localStorage) setiap kali Anda mengubah nilai.
                            Jangan lupa klik <strong>Simpan Perubahan</strong> untuk commit ke server.
                        </div>
                    @endunless
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const isReadonly = @json($isReadonly);
            const opnameId = @json($opname->id);
            const STORAGE_KEY = 'stock_opname_draft_' + opnameId;
            const form = document.getElementById('opname-form');
            const autosaveStatusEl = document.getElementById('autosave-status');
            const clearDraftBtn = document.getElementById('btn-clear-draft');
            const countedSummaryEl = document.getElementById('counted-summary');
            const markReviewedBtn = document.getElementById('btn-mark-reviewed');

            if (isReadonly) {
                // Mode readonly: tidak perlu autosave / draft
                return;
            }

            function loadDraft() {
                try {
                    const raw = localStorage.getItem(STORAGE_KEY);
                    if (!raw) return;

                    const draft = JSON.parse(raw);
                    if (!draft || typeof draft !== 'object') return;

                    const inputs = document.querySelectorAll('.physical-input');
                    inputs.forEach((input) => {
                        const lineId = input.dataset.lineId;
                        if (draft[lineId] && draft[lineId].physical_qty !== undefined) {
                            input.value = draft[lineId].physical_qty;
                        }
                    });

                    const noteInputs = document.querySelectorAll('.note-input');
                    noteInputs.forEach((input) => {
                        const name = input.getAttribute('name');
                        const match = name.match(/lines\[(\d+)]\[notes]/);
                        if (!match) return;
                        const lineId = match[1];
                        if (draft[lineId] && draft[lineId].notes !== undefined) {
                            input.value = draft[lineId].notes;
                        }
                    });

                } catch (e) {
                    console.warn('Gagal load draft opname:', e);
                }
            }

            function recalcLine(lineId) {
                const row = document.querySelector('tr[data-line-id="' + lineId + '"]');
                if (!row) return;

                const systemSpan = row.querySelector('.system-qty');
                const systemQty = parseFloat(systemSpan?.dataset.systemQty ?? '0') || 0;
                const physicalInput = row.querySelector('.physical-input');
                const diffDisplay = row.querySelector('.diff-display');
                const diffInput = row.querySelector('.diff-input');

                const physical = parseFloat(physicalInput.value);
                if (isNaN(physical)) {
                    diffDisplay.textContent = '-';
                    diffDisplay.classList.remove('diff-plus', 'diff-minus');
                    diffInput.value = '';
                    return;
                }

                const diff = physical - systemQty;
                diffInput.value = diff.toFixed(2);

                let displayText = diff.toFixed(2);
                if (diff > 0) {
                    displayText = '+' + displayText;
                }

                diffDisplay.textContent = displayText;
                diffDisplay.classList.remove('diff-plus', 'diff-minus');
                if (diff > 0) {
                    diffDisplay.classList.add('diff-plus');
                } else if (diff < 0) {
                    diffDisplay.classList.add('diff-minus');
                }
            }

            function updateCountedSummary() {
                const inputs = document.querySelectorAll('.physical-input');
                let total = 0;
                let filled = 0;

                inputs.forEach((input) => {
                    total++;
                    if (input.value !== '' && !isNaN(parseFloat(input.value))) {
                        filled++;
                    }
                });

                if (countedSummaryEl) {
                    countedSummaryEl.textContent = filled + ' / ' + total + ' item sudah diisi';
                }
            }

            function saveDraft() {
                try {
                    const draft = {};
                    const physicalInputs = document.querySelectorAll('.physical-input');
                    physicalInputs.forEach((input) => {
                        const lineId = input.dataset.lineId;
                        if (!draft[lineId]) draft[lineId] = {};
                        draft[lineId].physical_qty = input.value;
                    });

                    const noteInputs = document.querySelectorAll('.note-input');
                    noteInputs.forEach((input) => {
                        const name = input.getAttribute('name');
                        const match = name.match(/lines\[(\d+)]\[notes]/);
                        if (!match) return;
                        const lineId = match[1];
                        if (!draft[lineId]) draft[lineId] = {};
                        draft[lineId].notes = input.value;
                    });

                    localStorage.setItem(STORAGE_KEY, JSON.stringify(draft));

                    if (autosaveStatusEl) {
                        autosaveStatusEl.innerHTML =
                            '<span><span class="autosave-dot"></span>Draft tersimpan (local)</span>';
                    }
                } catch (e) {
                    console.warn('Gagal simpan draft opname:', e);
                    if (autosaveStatusEl) {
                        autosaveStatusEl.textContent = 'Draft tidak dapat disimpan (localStorage error)';
                    }
                }
            }

            function setupListeners() {
                const physicalInputs = document.querySelectorAll('.physical-input');
                physicalInputs.forEach((input) => {
                    // auto-select isi saat fokus, supaya gampang overwrite
                    input.addEventListener('focus', function() {
                        // delay sedikit biar aman di mobile/iOS
                        const el = this;
                        setTimeout(function() {
                            el.select();
                        }, 10);
                    });

                    input.addEventListener('input', function() {
                        const lineId = this.dataset.lineId;
                        recalcLine(lineId);
                        updateCountedSummary();
                        saveDraft();
                    });
                });

                const noteInputs = document.querySelectorAll('.note-input');
                noteInputs.forEach((input) => {
                    input.addEventListener('input', function() {
                        saveDraft();
                    });
                });

                if (clearDraftBtn) {
                    clearDraftBtn.addEventListener('click', function() {
                        if (!confirm('Hapus draft yang tersimpan di browser untuk sesi opname ini?')) {
                            return;
                        }
                        localStorage.removeItem(STORAGE_KEY);

                        const physicalInputs = document.querySelectorAll('.physical-input');
                        physicalInputs.forEach((input) => {
                            input.value = '';
                        });

                        const diffDisplays = document.querySelectorAll('.diff-display');
                        const diffInputs = document.querySelectorAll('.diff-input');
                        diffDisplays.forEach((el) => {
                            el.textContent = '-';
                            el.classList.remove('diff-plus', 'diff-minus');
                        });
                        diffInputs.forEach((el) => {
                            el.value = '';
                        });

                        updateCountedSummary();

                        if (autosaveStatusEl) {
                            autosaveStatusEl.textContent = 'Draft dihapus dari browser';
                        }
                    });
                }

                // Kalau user klik "Simpan & Tandai Selesai Counting" → hapus draft lokal
                if (markReviewedBtn) {
                    markReviewedBtn.addEventListener('click', function() {
                        localStorage.removeItem(STORAGE_KEY);
                    });
                }

                // Submit biasa: biarkan draft tetap ada (jaga-jaga)
            }

            // Init
            loadDraft();
            document.querySelectorAll('.physical-input').forEach((input) => {
                if (input.value !== '') {
                    recalcLine(input.dataset.lineId);
                }
            });
            updateCountedSummary();
            setupListeners();
        })();
    </script>
@endpush
