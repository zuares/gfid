@extends('layouts.app')

@section('title', 'RTS • Detail Stock Request ' . $stockRequest->code)

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

        .chip-user {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            border-radius: 999px;
            padding: .12rem .55rem;
            font-size: .75rem;
            border: 1px solid rgba(148, 163, 184, 0.6);
        }

        .chip-user-icon {
            width: 18px;
            height: 18px;
            border-radius: 999px;
            background: rgba(59, 130, 246, 0.12);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .7rem;
        }

        .mono {
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

        .row-partial {
            background: rgba(254, 243, 199, 0.40);
        }

        .row-completed {
            background: rgba(240, 253, 244, 0.65);
        }

        .text-soft {
            font-size: .75rem;
            color: rgba(148, 163, 184, 1);
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

        .actions-row {
            margin-top: .9rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .75rem;
            flex-wrap: wrap;
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

        @media (max-width: 768px) {
            .meta-grid {
                grid-template-columns: repeat(1, minmax(0, 1fr));
            }

            .table-wrap {
                border-radius: 10px;
                overflow-x: auto;
            }

            .table {
                min-width: 880px;
            }

            .actions-row {
                flex-direction: column-reverse;
                align-items: stretch;
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

        $totalRequested = (float) $stockRequest->lines->sum('qty_request');
        $totalIssued = (float) $stockRequest->lines->sum('qty_issued');
        $totalOutstanding = max($totalRequested - $totalIssued, 0);
    @endphp

    <div class="page-wrap">
        <div class="card">
            <div class="card-header">
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <div class="section-title">RTS • Detail Stock Request</div>
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
                        <div class="meta-label">Dibuat oleh</div>
                        <div class="meta-value">
                            <span class="chip-user">
                                <span class="chip-user-icon">👤</span>
                                <span>{{ $stockRequest->requestedBy->name ?? '—' }}</span>
                            </span>
                        </div>
                    </div>

                    <div class="meta-item" style="grid-column: span 12 / span 12;">
                        <div class="meta-label">Catatan</div>
                        <div class="meta-value">
                            {{ $stockRequest->notes ?: '—' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                <div class="section-title" style="margin-bottom:.35rem;">Detail Item</div>
                <div class="text-soft" style="margin-bottom:.4rem;">
                    RTS hanya bisa melihat status terkini:
                    <strong>diminta</strong>, <strong>terkirim</strong> dari PRD ke RTS,
                    dan <strong>outstanding</strong> (kalau dokumen masih partial).
                </div>

                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 32px;">#</th>
                                <th style="width: 34%;">Item</th>
                                <th style="width: 14%;">Diminta</th>
                                <th style="width: 14%;">Stok Snapshot PRD</th>
                                <th style="width: 14%;">Terkirim ke RTS</th>
                                <th style="width: 14%;">Outstanding</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($stockRequest->lines as $index => $line)
                                @php
                                    $requested = (float) $line->qty_request;
                                    $snapshot = (float) $line->stock_snapshot_at_request;
                                    $issued = (float) ($line->qty_issued ?? 0);
                                    $outstanding = max($requested - $issued, 0);

                                    $rowClass = '';
                                    if ($issued > 0 && $outstanding == 0) {
                                        $rowClass = 'row-completed';
                                    } elseif ($outstanding > 0 && in_array($status, ['submitted', 'partial'])) {
                                        $rowClass = 'row-partial';
                                    }
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td class="mono">
                                        {{ $index + 1 }}
                                    </td>
                                    <td>
                                        <div class="font-semibold text-sm">
                                            {{ $line->item->code ?? '' }} — {{ $line->item->name ?? '' }}
                                        </div>
                                        @if (!empty($line->notes))
                                            <div class="text-soft">
                                                Catatan: {{ $line->notes }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="mono">
                                        {{ number_format($requested, 0) }} pcs
                                    </td>
                                    <td class="mono">
                                        {{ number_format($snapshot, 0) }} pcs
                                    </td>
                                    <td class="mono">
                                        {{ number_format($issued, 0) }} pcs
                                    </td>
                                    <td>
                                        <div class="mono">
                                            {{ number_format($outstanding, 0) }} pcs
                                        </div>
                                        @if ($outstanding > 0 && in_array($status, ['submitted', 'partial']))
                                            <div class="text-soft">
                                                Menunggu proses dari PRD
                                            </div>
                                        @elseif($outstanding === 0 && $issued > 0)
                                            <div class="text-soft">
                                                Terpenuhi
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Summary --}}
                <div class="summary-row">
                    <div class="summary-box">
                        <div class="meta-label">Total Diminta</div>
                        <div class="meta-value mono">
                            {{ number_format($totalRequested, 0) }} pcs
                        </div>
                    </div>
                    <div class="summary-box">
                        <div class="meta-label">Total Sudah Dikirim ke RTS</div>
                        <div class="meta-value mono">
                            {{ number_format($totalIssued, 0) }} pcs
                        </div>
                    </div>
                    <div class="summary-box">
                        <div class="meta-label">Total Outstanding</div>
                        <div class="meta-value mono">
                            {{ number_format($totalOutstanding, 0) }} pcs
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="actions-row">
                    <div class="text-soft">
                        Status dokumen:
                        <strong>{{ ucfirst($status) }}</strong>.
                        @if ($status === 'submitted')
                            Menunggu diproses oleh Gudang Produksi.
                        @elseif($status === 'partial')
                            Sebagian item sudah dikirim, masih ada outstanding.
                        @elseif($status === 'completed')
                            Semua item sudah dipenuhi oleh Gudang Produksi.
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('rts.stock-requests.index') }}" class="btn-ghost">
                            ← Kembali ke daftar
                        </a>

                        @if (Route::has('prd.stock-requests.edit'))
                            <a href="{{ route('prd.stock-requests.edit', $stockRequest) }}" class="btn-outline">
                                Lihat status di PRD
                            </a>
                        @endif

                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
