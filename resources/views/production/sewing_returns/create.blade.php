@extends('layouts.app')

@section('title', 'Produksi • Sewing Return')

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
            font-size: .84rem;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .14rem .5rem;
            font-size: .7rem;
            background: color-mix(in srgb, var(--card) 70%, var(--line) 30%);
            color: var(--muted);
        }

        .table-wrap {
            overflow-x: auto;
        }

        /* ====== HEADER ====== */
        .header-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
        }

        /* ====== ROW STATE (bundle) ====== */
        .return-row {
            transition:
                background-color .16s ease,
                box-shadow .16s ease,
                border-color .16s ease;
        }

        .return-row td {
            border-top-color: rgba(148, 163, 184, 0.25) !important;
        }

        /* belum diisi */
        .row-empty {
            box-shadow:
                inset 3px 0 0 color-mix(in srgb, var(--line) 80%, transparent 20%);
            background: var(--card);
        }

        /* sudah diisi */
        .row-filled {
            background: color-mix(in srgb,
                    var(--card) 82%,
                    rgba(34, 197, 94, 0.20) 18%);
            box-shadow:
                inset 3px 0 0 rgba(34, 197, 94, 0.9),
                0 0 0 1px color-mix(in srgb, var(--line) 60%, rgba(34, 197, 94, .45) 40%);
        }

        .qty-remaining-pill {
            border-radius: 999px;
            padding: .06rem .55rem;
            font-size: .78rem;
            font-weight: 600;
            background: color-mix(in srgb,
                    var(--card) 65%,
                    rgba(34, 197, 94, 0.22) 35%);
            color: rgb(22, 163, 74);
            border: 1px solid color-mix(in srgb,
                    var(--line) 40%,
                    rgba(34, 197, 94, 0.7) 60%);
        }

        .summary-chip {
            border-radius: 999px;
            padding: .15rem .6rem;
            font-size: .76rem;
            background: color-mix(in srgb, var(--card) 80%, var(--line) 20%);
            color: var(--muted);
        }

        .summary-selected {
            font-size: .78rem;
            color: var(--muted);
        }

        .qty-input {
            font-weight: 500;
            transition: font-weight .12s ease, box-shadow .12s ease, border-color .12s ease, background-color .12s ease;
        }

        .qty-input-active {
            font-weight: 700;
            border-color: rgba(34, 197, 94, .75);
            box-shadow: 0 0 0 1px rgba(34, 197, 94, .5);
            background: color-mix(in srgb, var(--card) 85%, rgba(34, 197, 94, .12) 15%);
        }

        .btn-quick {
            font-size: .72rem;
            padding-inline: .5rem;
            padding-block: .18rem;
            border-radius: 999px;
        }

        .btn-quick.btn-outline-success {
            border-color: color-mix(in srgb, var(--line) 40%, rgba(34, 197, 94, .9) 60%);
        }

        .btn-quick.btn-outline-danger {
            border-color: color-mix(in srgb, var(--line) 40%, rgba(220, 38, 38, .9) 60%);
        }

        .notes-input {
            font-size: .78rem;
        }

        .cell-label {
            font-size: .7rem;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: .12rem;
        }

        /* ============ MOBILE (<= 767.98px) ============ */
        @media (max-width: 767.98px) {
            .card {
                border-radius: 12px;
            }

            .page-wrap {
                padding-bottom: 6rem;
            }

            .header-row {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-header-secondary {
                width: 100%;
                justify-content: center;
                border-radius: 999px;
                padding-block: .45rem;
                font-size: .82rem;
            }

            .table-sewing-return {
                border-collapse: separate;
                border-spacing: 0 6px;
            }

            .table-sewing-return thead {
                display: none;
            }

            .table-sewing-return tbody tr {
                display: block;
                border-radius: 11px;
                border: 1px solid var(--line);
                padding: .52rem .6rem .6rem;
                margin-bottom: .5rem;
                background: var(--card);
            }

            .table-sewing-return tbody tr:last-child {
                margin-bottom: 0;
            }

            .table-sewing-return td {
                display: block;
                border: none !important;
                padding: .12rem 0;
            }

            .td-desktop-only {
                display: none !important;
            }

            .mobile-row-top {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: .75rem;
                margin-bottom: .2rem;
            }

            .mobile-top-left {
                font-size: .84rem;
                display: flex;
                flex-direction: column;
                gap: .1rem;
            }

            .mobile-top-line {
                display: flex;
                align-items: center;
                gap: .32rem;
            }

            .mobile-top-left .row-index {
                font-size: .74rem;
                color: var(--muted);
            }

            .mobile-top-left .item-code {
                font-weight: 700;
            }

            .mobile-top-right {
                text-align: right;
                font-size: .78rem;
            }

            .mobile-top-right .qty-remaining-label {
                font-size: .7rem;
                text-transform: uppercase;
                color: var(--muted);
            }

            .mobile-meta {
                font-size: .75rem;
                color: var(--muted);
                margin-bottom: .18rem;
            }

            .cell-qty-row {
                display: flex;
                gap: .35rem;
            }

            .cell-qty-row .form-control {
                flex: 1;
            }

            .cell-actions {
                display: flex;
                flex-wrap: wrap;
                gap: .3rem;
                margin-top: .25rem;
            }

            .cell-actions .btn-quick {
                flex: 1;
                min-width: 110px;
                text-align: center;
            }

            /* FOOTER: floating kanan bawah */
            .form-footer {
                position: fixed;
                right: .9rem;
                bottom: 4.2rem;
                left: auto;
                z-index: 30;

                display: inline-flex !important;
                flex-direction: row-reverse;
                align-items: center !important;
                gap: .45rem;

                margin: 0;
                padding: 0;

                background: transparent;
                border: none;
            }

            .form-footer .btn {
                width: auto;
                border-radius: 999px;
                padding-inline: .9rem;
                padding-block: .35rem;
                box-shadow:
                    0 10px 20px rgba(15, 23, 42, .25),
                    0 3px 8px rgba(15, 23, 42, .2);
            }

            .form-footer .btn-primary {
                font-weight: 600;
                background: linear-gradient(135deg,
                        #0d6efd 0%,
                        #22c55e 55%,
                        #15803d 100%);
                border: none;
                display: inline-flex;
                align-items: center;
                gap: .35rem;
            }

            .form-footer .btn-outline-secondary {
                font-size: .78rem;
                padding-inline: .7rem;
                padding-block: .3rem;
                background: color-mix(in srgb, var(--card) 80%, #f8fafc 20%);
                border-color: color-mix(in srgb, var(--line) 70%, rgba(148, 163, 184, .9) 30%);
                display: inline-flex;
                align-items: center;
                gap: .25rem;
            }
        }

        /* ============ DESKTOP (>= 768px) ============ */
        @media (min-width: 768px) {
            .td-mobile-only {
                display: none !important;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap py-3 py-md-4">

        {{-- HEADER --}}
        <div class="card p-3 mb-3">
            <div class="header-row">
                <div>
                    <h1 class="h5 mb-1">Sewing Return</h1>
                    <div class="help">
                        Setor hasil jahit (OK / Reject) per bundle dari Sewing Pickup.
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('production.sewing_returns.index') }}"
                        class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1 btn-header-secondary">
                        <i class="bi bi-arrow-left"></i>
                        <span>Daftar Return</span>
                    </a>
                    <a href="{{ route('production.sewing_pickups.index') }}"
                        class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1 btn-header-secondary">
                        <i class="bi bi-basket"></i>
                        <span>Sewing Pickup</span>
                    </a>
                </div>
            </div>
        </div>

        @php
            $selectedPickupId = old('pickup_id', optional($currentPickup)->id);
            $defaultDate = old(
                'date',
                optional($currentPickup->date ?? null)?->format('Y-m-d') ?? now()->format('Y-m-d'),
            );
        @endphp

        <form id="sewing-return-form" action="{{ route('production.sewing_returns.store') }}" method="post">
            @csrf

            {{-- HEADER FORM --}}
            <div class="card p-3 mb-3">
                <div class="row g-3">
                    {{-- Pickup (flow: pilih tanggal pickup) --}}
                    <div class="col-md-6 col-12">
                        <div class="help mb-1">Pickup</div>
                        <select name="pickup_id" class="form-select form-select-sm @error('pickup_id') is-invalid @enderror"
                            onchange="if(this.value){ window.location='{{ route('production.sewing_returns.create') }}?pickup_id=' + this.value; }">
                            <option value="">Pilih tanggal pickup...</option>
                            @foreach ($pickups as $p)
                                @php
                                    /** @var \Carbon\Carbon|null $dateObj */
                                    $dateObj = $p->date ? \Carbon\Carbon::parse($p->date) : null;
                                    $dayLabel = $dateObj ? $dateObj->translatedFormat('D') : '';
                                    $dateLabel = $dateObj ? $dateObj->format('d/m') : $p->date;
                                    $opName = $p->operator?->name ?? '-';
                                    $isSelected = $selectedPickupId == $p->id;
                                @endphp
                                <option value="{{ $p->id }}" {{ $isSelected ? 'selected' : '' }}>
                                    {{ $dayLabel }} • {{ $dateLabel }} • {{ $opName }}
                                </option>
                            @endforeach
                        </select>
                        @error('pickup_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @if (!$currentPickup)
                            <div class="small text-muted mt-1">
                                Pilih tanggal pickup + operator jahit yang akan disetor hasilnya.
                            </div>
                        @endif
                    </div>

                    {{-- Tanggal Return --}}
                    <div class="col-md-3 col-6">
                        <div class="help mb-1">Tanggal Return</div>
                        <input type="date" name="date"
                            class="form-control form-control-sm @error('date') is-invalid @enderror"
                            value="{{ $defaultDate }}">
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Catatan header --}}
                    <div class="col-md-3 col-6">
                        <input type="text" name="notes"
                            class="form-control form-control-sm @error('notes') is-invalid @enderror"
                            value="{{ old('notes') }}" placeholder="Catatan umum (opsional)">
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- BUNDLES --}}
            <div class="card p-3 mb-3">
                @php
                    $totalBundles = $lines->count();
                    $totalRemaining = $lines->sum(fn($l) => (float) ($l->remaining_qty ?? 0));
                @endphp

                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <div>
                        <h2 class="h6 mb-0">Bundles dari Pickup</h2>
                        <div class="help">
                            Isi Qty OK / Reject per bundle (maks = qty sisa).
                        </div>
                    </div>

                    @if ($totalBundles > 0)
                        <div class="d-flex flex-column align-items-md-end gap-1">
                            <div class="d-flex flex-wrap gap-2">
                                <span class="summary-chip mono">
                                    {{ number_format($totalBundles, 0, ',', '.') }} bundle
                                </span>
                                <span class="summary-chip mono">
                                    {{ number_format($totalRemaining, 2, ',', '.') }} pcs sisa
                                </span>
                            </div>
                            <div class="summary-selected mono" id="summary-selected-wrapper">
                                <span id="summary-selected-bundles">0</span> bundle diisi /
                                <span id="summary-selected-ok">0,00</span> OK /
                                <span id="summary-selected-reject">0,00</span> Reject
                            </div>
                        </div>
                    @endif
                </div>

                @error('results')
                    <div class="alert alert-danger py-1 small mb-2">
                        {{ $message }}
                    </div>
                @enderror

                <div class="table-wrap">
                    <table class="table table-sm align-middle mono table-sewing-return mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px;" class="text-center">#</th>
                                <th style="width: 170px;">Bundle / Item Jadi</th>
                                <th style="width: 150px;">Lot</th>
                                <th style="width: 100px;" class="text-end">Sisa</th>
                                <th style="width: 110px;" class="text-end">Qty OK</th>
                                <th style="width: 110px;" class="text-end">Qty Reject</th>
                                <th style="width: 180px;">Catatan</th>
                                <th style="width: 150px;" class="text-center">Quick</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lines as $idx => $line)
                                @php
                                    $bundle = $line->bundle;
                                    $lot = $bundle?->cuttingJob?->lot;
                                    $remaining = (float) ($line->remaining_qty ?? 0);

                                    $oldResult = old('results.' . $idx, []);
                                    $defaultOk = $oldResult['qty_ok'] ?? null;
                                    $defaultReject = $oldResult['qty_reject'] ?? null;
                                    $defaultNotes = $oldResult['notes'] ?? null;
                                @endphp

                                <tr class="return-row row-empty" data-row-index="{{ $idx }}"
                                    data-remaining="{{ $remaining }}" data-bundle-code="{{ $bundle?->bundle_code }}"
                                    data-item-code="{{ $bundle?->finishedItem?->code }}"
                                    data-item-name="{{ $bundle?->finishedItem?->name }}">
                                    <input type="hidden" name="results[{{ $idx }}][line_id]"
                                        value="{{ $line->id }}">

                                    {{-- INDEX / MOBILE TOP --}}
                                    <td>
                                        {{-- mobile top --}}
                                        <div class="mobile-row-top td-mobile-only">
                                            <div class="mobile-top-left">
                                                <div class="mobile-top-line">
                                                    <span class="row-index">#{{ $loop->iteration }}</span>
                                                    <span class="item-code">
                                                        {{ $bundle?->finishedItem?->code ?? '-' }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="mobile-top-right">
                                                <div class="qty-remaining-label">Sisa</div>
                                                <div>
                                                    <span class="qty-remaining-pill">
                                                        {{ number_format($remaining, 2, ',', '.') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- desktop index --}}
                                        <div class="d-none d-md-block text-center">
                                            <span class="small text-muted">{{ $loop->iteration }}</span>
                                        </div>
                                    </td>

                                    {{-- BUNDLE / ITEM --}}
                                    <td>
                                        <div class="d-none d-md-block">
                                            <div class="fw-bold">
                                                {{ $bundle?->finishedItem?->code ?? '-' }}
                                            </div>
                                            <div class="small text-muted">
                                                {{ $bundle?->finishedItem?->name ?? '' }}
                                            </div>
                                            <div class="small text-muted">
                                                Bundle:
                                                <span class="mono">{{ $bundle?->bundle_code ?? '-' }}</span>
                                            </div>
                                        </div>

                                        <div class="mobile-meta td-mobile-only">
                                            <span class="mono">{{ $bundle?->bundle_code ?? '-' }}</span>
                                            @if ($lot)
                                                • <span class="mono">{{ $lot->code }}</span>
                                            @endif
                                        </div>
                                    </td>

                                    {{-- LOT (desktop only) --}}
                                    <td class="d-none d-md-table-cell td-desktop-only">
                                        @if ($lot)
                                            {{ $lot->item?->code ?? '-' }}
                                            <span class="badge-soft ms-1">
                                                {{ $lot->code }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>

                                    {{-- SISA (desktop only) --}}
                                    <td class="text-end d-none d-md-table-cell td-desktop-only">
                                        <span class="qty-remaining-pill">
                                            {{ number_format($remaining, 2, ',', '.') }}
                                        </span>
                                    </td>

                                    {{-- QTY OK --}}
                                    <td class="text-end">
                                        <input type="number" step="0.01" min="0" inputmode="decimal"
                                            name="results[{{ $idx }}][qty_ok]"
                                            class="form-control form-control-sm text-end qty-input qty-ok-input @error("results.$idx.qty_ok") is-invalid @enderror"
                                            value="{{ $defaultOk ?? '' }}" placeholder="OK">
                                        @error("results.$idx.qty_ok")
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    {{-- QTY REJECT --}}
                                    <td class="text-end">
                                        <input type="number" step="0.01" min="0" inputmode="decimal"
                                            name="results[{{ $idx }}][qty_reject]"
                                            class="form-control form-control-sm text-end qty-input qty-reject-input @error("results.$idx.qty_reject") is-invalid @enderror"
                                            value="{{ $defaultReject ?? '' }}" placeholder="RJ">
                                        @error("results.$idx.qty_reject")
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    {{-- CATATAN --}}
                                    <td>
                                        <input type="text" name="results[{{ $idx }}][notes]"
                                            class="form-control form-control-sm notes-input @error("results.$idx.notes") is-invalid @enderror"
                                            value="{{ $defaultNotes ?? '' }}" placeholder="Catatan (opsional)">
                                        @error("results.$idx.notes")
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    {{-- QUICK BUTTONS --}}
                                    <td class="text-center">
                                        <div class="cell-actions">
                                            <button type="button" class="btn btn-outline-success btn-quick btn-full-ok">
                                                Full OK
                                            </button>
                                            <button type="button"
                                                class="btn btn-outline-danger btn-quick btn-full-reject">
                                                Full RJ
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted small py-3">
                                        Tidak ada bundle dengan qty sisa di Sewing Pickup ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- SUBMIT --}}
            <div class="d-flex justify-content-between align-items-center mb-5 form-footer">
                <a href="{{ route('production.sewing_returns.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i>
                    <span class="d-none d-sm-inline">Batal</span>
                </a>

                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-check2-circle"></i>
                    <span class="text-light">Simpan Return</span>
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.return-row');
            const summaryBundles = document.getElementById('summary-selected-bundles');
            const summaryOk = document.getElementById('summary-selected-ok');
            const summaryReject = document.getElementById('summary-selected-reject');

            let nf;
            try {
                nf = new Intl.NumberFormat('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            } catch (e) {
                nf = {
                    format: function(num) {
                        return (num || 0).toFixed(2);
                    }
                };
            }

            function updateRowVisual(row) {
                const okInput = row.querySelector('.qty-ok-input');
                const rejectInput = row.querySelector('.qty-reject-input');

                const ok = okInput ? parseFloat(okInput.value || '0') : 0;
                const reject = rejectInput ? parseFloat(rejectInput.value || '0') : 0;
                const isFilled = (ok + reject) > 0;

                if (isFilled) {
                    row.classList.add('row-filled');
                    row.classList.remove('row-empty');
                } else {
                    row.classList.remove('row-filled');
                    row.classList.add('row-empty');
                }
            }

            function updateSummary() {
                if (!summaryBundles || !summaryOk || !summaryReject) return;

                let count = 0;
                let totalOk = 0;
                let totalReject = 0;

                rows.forEach(function(row) {
                    const okInput = row.querySelector('.qty-ok-input');
                    const rejectInput = row.querySelector('.qty-reject-input');

                    const ok = okInput ? parseFloat(okInput.value || '0') : 0;
                    const reject = rejectInput ? parseFloat(rejectInput.value || '0') : 0;

                    if ((ok + reject) > 0) {
                        count++;
                        totalOk += ok;
                        totalReject += reject;
                    }
                });

                summaryBundles.textContent = count.toString();
                summaryOk.textContent = nf.format(totalOk);
                summaryReject.textContent = nf.format(totalReject);
            }

            rows.forEach(function(row) {
                const remaining = parseFloat(row.dataset.remaining || '0');
                const okInput = row.querySelector('.qty-ok-input');
                const rejectInput = row.querySelector('.qty-reject-input');
                const btnFullOk = row.querySelector('.btn-full-ok');
                const btnFullReject = row.querySelector('.btn-full-reject');

                function getPair() {
                    let ok = okInput ? parseFloat(okInput.value || '0') : 0;
                    let reject = rejectInput ? parseFloat(rejectInput.value || '0') : 0;
                    return {
                        ok,
                        reject
                    };
                }

                function applyPair(ok, reject) {
                    if (okInput) okInput.value = ok > 0 ? ok : '';
                    if (rejectInput) rejectInput.value = reject > 0 ? reject : '';
                }

                function clampAndApply() {
                    let {
                        ok,
                        reject
                    } = getPair();

                    if (ok < 0) ok = 0;
                    if (reject < 0) reject = 0;

                    if (ok + reject > remaining) {
                        const total = ok + reject;

                        if (reject > 0) {
                            const excess = total - remaining;
                            reject = Math.max(0, reject - excess);
                        }

                        if (ok + reject > remaining) {
                            ok = Math.min(ok, remaining);
                            reject = Math.max(0, remaining - ok);
                        }
                    }

                    applyPair(ok, reject);
                }

                // Full OK
                if (btnFullOk) {
                    btnFullOk.addEventListener('click', function(e) {
                        e.preventDefault();
                        applyPair(remaining > 0 ? remaining : 0, 0);
                        updateRowVisual(row);
                        updateSummary();
                    });
                }

                // Full Reject
                if (btnFullReject) {
                    btnFullReject.addEventListener('click', function(e) {
                        e.preventDefault();
                        applyPair(0, remaining > 0 ? remaining : 0);
                        updateRowVisual(row);
                        updateSummary();
                    });
                }

                // Input events
                if (okInput) {
                    okInput.addEventListener('focus', function() {
                        this.select();
                        this.classList.add('qty-input-active');
                    });
                    okInput.addEventListener('blur', function() {
                        this.classList.remove('qty-input-active');
                        clampAndApply();
                        updateRowVisual(row);
                        updateSummary();
                    });
                    okInput.addEventListener('input', function() {
                        updateRowVisual(row);
                        updateSummary();
                    });
                }

                if (rejectInput) {
                    rejectInput.addEventListener('focus', function() {
                        this.select();
                        this.classList.add('qty-input-active');
                    });
                    rejectInput.addEventListener('blur', function() {
                        this.classList.remove('qty-input-active');
                        clampAndApply();
                        updateRowVisual(row);
                        updateSummary();
                    });
                    rejectInput.addEventListener('input', function() {
                        updateRowVisual(row);
                        updateSummary();
                    });
                }

                // init
                updateRowVisual(row);
            });

            updateSummary();
        });
    </script>
@endpush
