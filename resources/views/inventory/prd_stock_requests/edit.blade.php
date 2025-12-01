@extends('layouts.app')

@section('title', 'PRD • Proses Permintaan RTS ' . $stockRequest->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .75rem .75rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(129, 140, 248, 0.16) 0,
                    rgba(45, 212, 191, 0.08) 26%,
                    #f9fafb 60%);
        }

        .card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.22);
            box-shadow:
                0 10px 28px rgba(15, 23, 42, 0.08),
                0 0 0 1px rgba(15, 23, 42, 0.02);
        }

        .card-header {
            padding: .9rem 1.25rem .75rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.28);
        }

        .card-body {
            padding: .75rem 1.25rem 1rem;
        }

        .section-title {
            font-size: .9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: rgba(100, 116, 139, 1);
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            gap: .5rem 1rem;
            margin-top: .6rem;
        }

        .meta-item {
            font-size: .8rem;
            color: rgba(71, 85, 105, 1);
        }

        .meta-label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: rgba(148, 163, 184, 1);
        }

        .meta-value {
            margin-top: .15rem;
            font-weight: 500;
        }

        .badge-status {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .12rem .6rem;
            border-radius: 999px;
            font-size: .72rem;
            font-weight: 500;
        }

        .badge-submitted {
            background: rgba(59, 130, 246, 0.12);
            color: rgba(30, 64, 175, 1);
        }

        .badge-partial {
            background: rgba(234, 179, 8, 0.14);
            color: rgba(133, 77, 14, 1);
        }

        .badge-completed {
            background: rgba(22, 163, 74, 0.14);
            color: rgba(21, 128, 61, 1);
        }

        .badge-draft {
            background: rgba(148, 163, 184, 0.18);
            color: rgba(71, 85, 105, 1);
        }

        .chip-warehouse {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            padding: .15rem .6rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.7);
            font-size: .75rem;
        }

        .chip-warehouse span.code {
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .table-wrap {
            margin-top: .9rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            overflow: hidden;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: .8rem;
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
            font-size: .76rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: rgba(100, 116, 139, 1);
        }

        .table td:last-child {
            text-align: right;
        }

        .mono {
            font-variant-numeric: tabular-nums;
        }

        .form-control {
            border-radius: .6rem;
            border: 1px solid rgba(148, 163, 184, 0.7);
            padding: .32rem .55rem;
            font-size: .8rem;
            background: var(--background);
        }

        .input-error {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 1px rgba(239, 68, 68, .16);
        }

        .text-error {
            color: #ef4444;
            font-size: .73rem;
            margin-top: .1rem;
        }

        .text-soft {
            font-size: .75rem;
            color: rgba(148, 163, 184, 1);
        }

        .row-soft {
            background: rgba(240, 253, 244, 0.9);
        }

        .row-outstanding {
            background: rgba(254, 243, 199, 0.45);
        }

        .qty-warning {
            font-size: .72rem;
            color: #b45309;
            margin-top: .1rem;
        }

        .summary-row {
            margin-top: .75rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            gap: .5rem;
            font-size: .8rem;
        }

        .summary-box {
            padding: .4rem .7rem;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.4);
            background: color-mix(in srgb, var(--card) 90%, rgba(59, 130, 246, 0.05));
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .3rem;
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

        .btn-sm {
            padding: .25rem .7rem;
            font-size: .76rem;
        }

        .btn[disabled] {
            opacity: .5;
            cursor: not-allowed;
        }

        .actions-row {
            margin-top: .9rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
        }

        /* ALERT BOXES */
        .alert {
            padding: .75rem 1rem;
            border-radius: 12px;
            font-size: .82rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: flex-start;
            gap: .6rem;
        }

        .alert-icon {
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            font-size: .7rem;
            font-weight: 700;
        }

        .alert-success {
            background: rgba(22, 163, 74, 0.12);
            border: 1px solid rgba(22, 163, 74, 0.20);
            color: rgba(21, 128, 61, 1);
        }

        .alert-success .alert-icon {
            background: rgba(22, 163, 74, 0.25);
        }

        .alert-warning {
            background: rgba(234, 179, 8, 0.12);
            border: 1px solid rgba(234, 179, 8, 0.35);
            color: rgba(133, 77, 14, 1);
        }

        .alert-warning .alert-icon {
            background: rgba(234, 179, 8, 0.28);
        }

        /* MINI TIMELINE */
        .timeline {
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-top: .75rem;
            font-size: .75rem;
        }

        .timeline-step {
            display: flex;
            align-items: center;
            gap: .3rem;
        }

        .timeline-circle {
            width: 16px;
            height: 16px;
            border-radius: 999px;
            border: 2px solid rgba(148, 163, 184, 0.8);
            background: var(--card);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: .6rem;
        }

        .timeline-label {
            color: rgba(148, 163, 184, 1);
        }

        .timeline-step-active .timeline-circle {
            border-color: rgba(59, 130, 246, 1);
            background: rgba(59, 130, 246, 0.12);
            color: rgba(37, 99, 235, 1);
        }

        .timeline-step-done .timeline-circle {
            border-color: rgba(22, 163, 74, 1);
            background: rgba(22, 163, 74, 0.16);
            color: rgba(22, 163, 74, 1);
        }

        .timeline-step-active .timeline-label {
            color: rgba(51, 65, 85, 1);
            font-weight: 600;
        }

        .timeline-step-done .timeline-label {
            color: rgba(21, 128, 61, 1);
            font-weight: 600;
        }

        .timeline-connector {
            flex: 1;
            height: 2px;
            background: linear-gradient(to right,
                    rgba(148, 163, 184, 0.6),
                    rgba(148, 163, 184, 0.2));
        }

        /* BADGE LINE STATUS */
        .badge-line-status {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            gap: .25rem;
            border-radius: 999px;
            padding: .12rem .55rem;
            font-size: .72rem;
            font-weight: 500;
            margin-top: .15rem;
        }

        .badge-line-completed {
            background: rgba(22, 163, 74, 0.16);
            color: rgba(21, 128, 61, 1);
        }

        .badge-line-outstanding {
            background: rgba(234, 179, 8, 0.14);
            color: rgba(133, 77, 14, 1);
        }

        /* FILTER TOGGLE */
        .filter-bar {
            margin-bottom: .5rem;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .filter-toggle {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .78rem;
            color: rgba(71, 85, 105, 1);
            cursor: pointer;
        }

        .filter-toggle input[type="checkbox"] {
            width: 14px;
            height: 14px;
            border-radius: 4px;
            border: 1px solid rgba(148, 163, 184, 0.9);
            accent-color: #f59e0b;
            cursor: pointer;
        }

        /* HISTORY PANEL */
        .history-panel {
            margin-top: 1.25rem;
            padding-top: .85rem;
            border-top: 1px dashed rgba(148, 163, 184, 0.6);
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            gap: .5rem;
        }

        .history-title {
            font-size: .85rem;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: rgba(100, 116, 139, 1);
        }

        .history-pill {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            border-radius: 999px;
            padding: .12rem .55rem;
            font-size: .72rem;
            background: rgba(15, 23, 42, 0.03);
            color: rgba(100, 116, 139, 1);
            border: 1px solid rgba(148, 163, 184, 0.45);
        }

        .history-table-wrap {
            margin-top: .55rem;
            border-radius: 10px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            overflow: hidden;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            font-size: .78rem;
        }

        .history-table thead {
            background: color-mix(in srgb, var(--card) 80%, rgba(15, 23, 42, 0.05));
        }

        .history-table th,
        .history-table td {
            padding: .4rem .55rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.25);
        }

        .history-table th {
            text-align: left;
            font-weight: 600;
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: rgba(100, 116, 139, 1);
        }

        .history-note {
            max-width: 260px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        @media (max-width: 768px) {
            .meta-grid {
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }

            .table-wrap {
                border-radius: 10px;
                overflow-x: auto;
            }

            .table {
                min-width: 720px;
            }

            /* compact mode: hide some columns di mobile */
            .col-snapshot,
            .col-stock-now {
                display: none;
            }

            .actions-row {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .history-table {
                min-width: 640px;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $status = $stockRequest->status;
        $statusClass = match ($status) {
            'submitted' => 'badge-submitted',
            'partial' => 'badge-partial',
            'completed' => 'badge-completed',
            'draft' => 'badge-draft',
            default => 'badge-draft',
        };
        $isCompleted = $status === 'completed';
        $isPartial = $status === 'partial';

        $timelineStep = match ($status) {
            'submitted' => 1,
            'partial' => 2,
            'completed' => 3,
            default => 1,
        };
    @endphp

    <div class="page-wrap">
        <div class="card">
            <div class="card-header">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <div class="section-title">PRD • Proses Permintaan RTS</div>
                        <div class="mt-1 text-sm text-slate-700 dark:text-slate-200">
                            No. Dokumen:
                            <span class="font-semibold mono">{{ $stockRequest->code }}</span>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        <span class="badge-status {{ $statusClass }}">
                            Status: {{ ucfirst($status) }}
                        </span>
                        <div class="text-soft">
                            Tanggal: {{ \Illuminate\Support\Carbon::parse($stockRequest->date)->format('d M Y') }}
                        </div>
                    </div>
                </div>

                {{-- Mini Timeline --}}
                <div class="timeline">
                    <div
                        class="timeline-step {{ $timelineStep === 1 ? 'timeline-step-active' : ($timelineStep > 1 ? 'timeline-step-done' : '') }}">
                        <div class="timeline-circle">
                            @if ($timelineStep > 1)
                                ✓
                            @else
                                1
                            @endif
                        </div>
                        <div class="timeline-label">Requested</div>
                    </div>

                    <div class="timeline-connector"></div>

                    <div
                        class="timeline-step {{ $timelineStep === 2 ? 'timeline-step-active' : ($timelineStep > 2 ? 'timeline-step-done' : '') }}">
                        <div class="timeline-circle">
                            @if ($timelineStep > 2)
                                ✓
                            @else
                                2
                            @endif
                        </div>
                        <div class="timeline-label">Partial</div>
                    </div>

                    <div class="timeline-connector"></div>

                    <div class="timeline-step {{ $timelineStep === 3 ? 'timeline-step-active' : '' }}">
                        <div class="timeline-circle">
                            3
                        </div>
                        <div class="timeline-label">Completed</div>
                    </div>
                </div>

                {{-- Meta --}}
                <div class="meta-grid">
                    <div class="meta-item" style="grid-column: span 4 / span 4;">
                        <div class="meta-label">Gudang Asal (PRD)</div>
                        <div class="meta-value">
                            <span class="chip-warehouse">
                                <span class="code">{{ $stockRequest->sourceWarehouse->code ?? '-' }}</span>
                                <span>{{ $stockRequest->sourceWarehouse->name ?? '-' }}</span>
                            </span>
                        </div>
                    </div>
                    <div class="meta-item" style="grid-column: span 4 / span 4;">
                        <div class="meta-label">Gudang Tujuan (RTS)</div>
                        <div class="meta-value">
                            <span class="chip-warehouse">
                                <span class="code">{{ $stockRequest->destinationWarehouse->code ?? '-' }}</span>
                                <span>{{ $stockRequest->destinationWarehouse->name ?? '-' }}</span>
                            </span>
                        </div>
                    </div>
                    <div class="meta-item" style="grid-column: span 4 / span 4;">
                        <div class="meta-label">Catatan Request</div>
                        <div class="meta-value">
                            {{ $stockRequest->notes ?: '—' }}
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('prd.stock-requests.update', $stockRequest) }}">
                @csrf

                <div class="card-body">
                    {{-- ALERTS --}}
                    @if ($isCompleted)
                        <div class="alert alert-success">
                            <div class="alert-icon">✓</div>
                            <div>
                                <strong>Dokumen sudah <u>COMPLETED</u>.</strong>
                                <br>
                                Semua item sudah dikirim ke RTS. Tidak bisa melakukan perubahan Qty Kirim lagi.
                                Halaman ini hanya untuk melihat detail final.
                            </div>
                        </div>
                    @elseif ($isPartial)
                        <div class="alert alert-warning">
                            <div class="alert-icon">!</div>
                            <div>
                                <strong>Dokumen dalam status <u>PARTIAL</u>.</strong>
                                <br>
                                Sebagian item sudah dikirim ke RTS, masih ada item yang outstanding.
                                Silakan isi Qty Kirim untuk melanjutkan pengiriman sisa kebutuhan.
                            </div>
                        </div>
                    @endif

                    <div class="section-title" style="margin-bottom:.35rem;">Detail Item</div>
                    <div class="text-soft" style="margin-bottom:.4rem;">
                        Isi kolom <strong>Qty Kirim</strong> untuk mengeluarkan stok dari PRD menuju RTS.
                        Sistem akan otomatis membuat movement PRD → RTS.
                    </div>

                    {{-- Filter & shortcut --}}
                    <div class="filter-bar">
                        <label class="filter-toggle">
                            <input type="checkbox" id="filter-outstanding">
                            <span>Tampilkan hanya baris <strong>outstanding</strong></span>
                        </label>

                        @unless ($isCompleted)
                            <button type="button" class="btn-outline btn-sm" id="auto-fill-qty">
                                Set Qty Kirim Otomatis
                            </button>
                        @endunless
                    </div>

                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width: 32px;">#</th>
                                    <th style="width: 30%;">Item</th>
                                    <th style="width: 12%;">Diminta</th>
                                    <th class="col-snapshot" style="width: 14%;">Stok Snapshot</th>
                                    <th class="col-stock-now" style="width: 14%;">Stok Sekarang</th>
                                    <th style="width: 14%;">Qty Kirim</th>
                                    <th style="width: 16%;">Outstanding / Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($stockRequest->lines as $index => $line)
                                    @php
                                        $lineId = $line->id;
                                        $requested = (float) $line->qty_request;
                                        $snapshot = (float) $line->stock_snapshot_at_request;
                                        $live = (float) ($liveStocks[$lineId] ?? 0);
                                        $issued = (float) ($line->qty_issued ?? 0);
                                        $defaultIssued = $defaultQtyIssued[$lineId] ?? 0;
                                        $oldIssued = old("lines.$lineId.qty_issued");

                                        // VALUE INPUT:
                                        // - kalau ada old() -> pakai old
                                        // - kalau completed -> pakai issued (readonly)
                                        // - kalau partial -> pakai defaultIssued
                                        // - kalau submitted pertama kali -> kosong (biar tombol auto-fill kepakai)
                                        if ($oldIssued !== null) {
                                            $valueIssued = $oldIssued;
                                        } elseif ($isCompleted) {
                                            $valueIssued = $issued;
                                        } elseif ($isPartial) {
                                            $valueIssued = $defaultIssued;
                                        } else {
                                            $valueIssued = '';
                                        }

                                        $outstanding = max($requested - $issued, 0);

                                        $rowClass = '';
                                        if ($outstanding > 0 && in_array($status, ['submitted', 'partial'])) {
                                            $rowClass = 'row-outstanding';
                                        } elseif ($issued > 0 && $outstanding == 0) {
                                            $rowClass = 'row-soft';
                                        }

                                        $lineCompleted = $issued > 0 && $outstanding <= 0;
                                    @endphp
                                    <tr class="{{ $rowClass }}" data-line-id="{{ $lineId }}"
                                        data-requested="{{ $requested }}" data-live="{{ $live }}"
                                        data-outstanding="{{ $outstanding }}">
                                        <td class="mono">
                                            {{ $index + 1 }}
                                        </td>
                                        <td>
                                            <div class="font-semibold text-sm">
                                                {{ $line->item->code ?? '' }} — {{ $line->item->name ?? '' }}
                                            </div>
                                            <div class="text-soft">
                                                Line ID: {{ $lineId }}
                                            </div>
                                        </td>
                                        <td>
                                            <div class="mono">
                                                {{ number_format($requested, 0) }} pcs
                                            </div>
                                        </td>
                                        <td class="col-snapshot">
                                            <div class="mono">
                                                {{ number_format($snapshot, 0) }} pcs
                                            </div>
                                            <div class="text-soft">
                                                Stok saat request dibuat
                                            </div>
                                        </td>
                                        <td class="col-stock-now">
                                            <div class="mono live-stock-display">
                                                {{ number_format($live, 0) }} pcs
                                            </div>
                                            <div class="text-soft">
                                                Stok live PRD
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" min="0" step="1"
                                                name="lines[{{ $lineId }}][qty_issued]"
                                                class="form-control qty-issued-input @error("lines.$lineId.qty_issued") input-error @enderror"
                                                value="{{ $valueIssued }}" {{ $isCompleted ? 'readonly' : '' }}>
                                            @error("lines.$lineId.qty_issued")
                                                <div class="text-error">{{ $message }}</div>
                                            @enderror
                                            <div class="qty-warning" style="display:none;"></div>
                                        </td>
                                        <td>
                                            <div class="mono">
                                                {{ number_format($outstanding, 0) }} pcs
                                            </div>
                                            <div class="text-soft">
                                                Sisa setelah kirim
                                            </div>

                                            @if ($lineCompleted)
                                                <div class="badge-line-status badge-line-completed">
                                                    ✓ Completed
                                                </div>
                                            @elseif ($requested > 0)
                                                <div class="badge-line-status badge-line-outstanding">
                                                    • Outstanding
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @php
                        $totalRequested = (float) $stockRequest->lines->sum('qty_request');
                        $totalIssued = (float) $stockRequest->lines->sum('qty_issued');
                        $totalOutstanding = max($totalRequested - $totalIssued, 0);
                    @endphp

                    <div class="summary-row">
                        <div class="summary-box">
                            <div class="meta-label">Total Diminta</div>
                            <div class="meta-value mono">
                                {{ number_format($totalRequested, 0) }} pcs
                            </div>
                        </div>
                        <div class="summary-box">
                            <div class="meta-label">Total Sudah Dikirim</div>
                            <div class="meta-value mono">
                                {{ number_format($totalIssued, 0) }} pcs
                            </div>
                        </div>
                        <div class="summary-box">
                            <div class="meta-label">Outstanding Setelah Proses Terakhir</div>
                            <div class="meta-value mono">
                                {{ number_format($totalOutstanding, 0) }} pcs
                            </div>
                        </div>
                    </div>

                    <div class="actions-row">
                        <div class="text-soft">
                            Catatan:
                            <br>
                            • Qty kirim tidak boleh melebihi stok PRD saat ini.
                            <br>
                            • Dokumen akan menjadi <strong>partial</strong> jika ada item yang belum terpenuhi penuh.
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('prd.stock-requests.index') }}" class="btn-ghost">
                                ← Kembali ke daftar
                            </a>

                            @if (!$isCompleted)
                                <button type="submit" class="btn">
                                    Proses & Kirim ke RTS
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- PANEL HISTORI MOVEMENT --}}
                    @php
                        $history = $movementHistory ?? collect();
                    @endphp

                    <div class="history-panel">
                        <div class="history-header">
                            <div class="history-title">
                                Histori Movement Stok (Audit)
                            </div>
                            <div class="history-pill">
                                {{ $history->count() }} movement
                            </div>
                        </div>

                        @if ($history->isEmpty())
                            <div class="text-soft" style="margin-top:.35rem;">
                                Belum ada movement yang tercatat untuk dokumen ini.
                                Stok PRD → RTS baru akan muncul di sini setelah kamu melakukan proses pengiriman.
                            </div>
                        @else
                            <div class="text-soft" style="margin-top:.25rem;">
                                Riwayat pergerakan stok yang tercatat dengan referensi dokumen ini (PRD ↔ RTS).
                            </div>

                            <div class="history-table-wrap">
                                <table class="history-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 40px;">#</th>
                                            <th style="width: 18%;">Tanggal & Jam</th>
                                            <th style="width: 14%;">Gudang</th>
                                            <th style="width: 24%;">Item</th>
                                            <th style="width: 10%;">Arah</th>
                                            <th style="width: 12%;">Qty</th>
                                            <th>Catatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($history as $idx => $mv)
                                            @php
                                                $ts = $mv->date ?? ($mv->created_at ?? null);
                                                $warehouse = $mv->warehouse ?? null;
                                                $item = $mv->item ?? null;
                                                $dir = strtolower($mv->direction ?? '');
                                                $qty = (float) ($mv->qty_change ?? 0);
                                            @endphp
                                            <tr>
                                                <td class="mono">{{ $idx + 1 }}</td>
                                                <td class="mono">
                                                    @if ($ts)
                                                        {{ \Illuminate\Support\Carbon::parse($ts)->format('d M Y H:i') }}
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($warehouse)
                                                        <div class="mono">
                                                            {{ $warehouse->code ?? '-' }}
                                                        </div>
                                                        <div class="text-soft">
                                                            {{ $warehouse->name ?? '' }}
                                                        </div>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($item)
                                                        <div class="mono">
                                                            {{ $item->code ?? '' }}
                                                        </div>
                                                        <div class="text-soft">
                                                            {{ $item->name ?? '' }}
                                                        </div>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>
                                                    @if ($dir === 'out')
                                                        <span class="badge-line-status badge-line-outstanding">
                                                            OUT
                                                        </span>
                                                    @elseif ($dir === 'in')
                                                        <span class="badge-line-status badge-line-completed">
                                                            IN
                                                        </span>
                                                    @else
                                                        <span class="text-soft">-</span>
                                                    @endif
                                                </td>
                                                <td class="mono">
                                                    {{ number_format(abs($qty), 0) }} pcs
                                                </td>
                                                <td>
                                                    <span class="history-note">
                                                        {{ $mv->notes ?: '—' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                    {{-- END PANEL HISTORI --}}
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const rows = document.querySelectorAll('tr[data-line-id]');
            const filterCheckbox = document.getElementById('filter-outstanding');
            const autoFillBtn = document.getElementById('auto-fill-qty');

            function validateRow(row) {
                const requested = parseFloat(row.dataset.requested || '0');
                const live = parseFloat(row.dataset.live || '0');
                const input = row.querySelector('.qty-issued-input');
                const warningEl = row.querySelector('.qty-warning');

                if (!input || !warningEl) return;

                const val = parseFloat(input.value || '0');

                if (!val || val <= 0) {
                    input.classList.remove('input-error');
                    warningEl.style.display = 'none';
                    warningEl.textContent = '';
                    return;
                }

                let msgs = [];

                if (val > requested) {
                    msgs.push('Qty kirim melebihi qty diminta (' + requested + ' pcs).');
                }

                if (val > live) {
                    msgs.push('Qty kirim melebihi stok live PRD (' + live + ' pcs).');
                }

                if (msgs.length) {
                    input.classList.add('input-error');
                    warningEl.style.display = 'block';
                    warningEl.textContent = msgs.join(' ');
                } else {
                    input.classList.remove('input-error');
                    warningEl.style.display = 'none';
                    warningEl.textContent = '';
                }
            }

            function applyOutstandingFilter() {
                if (!filterCheckbox) return;
                const onlyOutstanding = filterCheckbox.checked;

                rows.forEach(row => {
                    const out = parseFloat(row.dataset.outstanding || '0');

                    if (onlyOutstanding && out <= 0) {
                        row.style.display = 'none';
                    } else {
                        row.style.display = '';
                    }
                });
            }

            function autoFillAll() {
                rows.forEach(row => {
                    const out = parseFloat(row.dataset.outstanding || '0');
                    const live = parseFloat(row.dataset.live || '0');
                    const input = row.querySelector('.qty-issued-input');

                    if (!input) return;

                    // Kalau tidak outstanding, atau stok live 0 → kosongkan
                    if (out <= 0 || live <= 0) {
                        input.value = '';
                        validateRow(row);
                        return;
                    }

                    const qty = Math.min(out, live);

                    input.value = qty;
                    validateRow(row);
                });
            }

            rows.forEach(row => {
                const input = row.querySelector('.qty-issued-input');
                if (!input) return;

                input.addEventListener('input', () => validateRow(row));
                validateRow(row);
            });

            if (filterCheckbox) {
                filterCheckbox.addEventListener('change', applyOutstandingFilter);
                applyOutstandingFilter();
            }

            if (autoFillBtn) {
                autoFillBtn.addEventListener('click', autoFillAll);
            }
        })();
    </script>
@endpush
