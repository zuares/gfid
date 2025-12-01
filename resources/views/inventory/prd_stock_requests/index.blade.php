@extends('layouts.app')

@section('title', 'PRD • Permintaan dari RTS')

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
                0 8px 24px rgba(15, 23, 42, 0.06),
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

        .subtitle {
            font-size: .8rem;
            color: rgba(100, 116, 139, 1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .6rem;
            margin-top: .75rem;
        }

        .stat-card {
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.35);
            padding: .4rem .7rem;
            background: color-mix(in srgb, var(--card) 82%, rgba(129, 140, 248, 0.06));
        }

        .stat-label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: rgba(100, 116, 139, 1);
        }

        .stat-value {
            margin-top: .1rem;
            font-size: 1rem;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .stat-sub {
            font-size: .75rem;
            color: rgba(148, 163, 184, 1);
        }

        .filters-row {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem .75rem;
            align-items: flex-end;
            margin-top: .75rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: .2rem;
        }

        .form-group label {
            font-size: .75rem;
            font-weight: 500;
            color: rgba(71, 85, 105, 1);
        }

        .form-control,
        select {
            border-radius: .6rem;
            border: 1px solid rgba(148, 163, 184, 0.7);
            padding: .32rem .55rem;
            font-size: .8rem;
            background: var(--background);
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

        .btn-pill {
            border-radius: 999px;
            padding-inline: .75rem;
            padding-block: .28rem;
            font-size: .76rem;
        }

        .btn-sm {
            padding: .25rem .7rem;
            font-size: .78rem;
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
            white-space: nowrap;
            text-align: right;
        }

        .mono {
            font-variant-numeric: tabular-nums;
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

        .chip {
            display: inline-flex;
            align-items: center;
            gap: .2rem;
            border-radius: 999px;
            padding: .12rem .5rem;
            font-size: .72rem;
            border: 1px solid rgba(148, 163, 184, 0.7);
            color: rgba(71, 85, 105, 1);
        }

        .chip-soft {
            background: rgba(248, 250, 252, 1);
        }

        .period-label {
            font-size: .75rem;
            color: rgba(148, 163, 184, 1);
        }

        .top-actions {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: .75rem;
            flex-wrap: wrap;
            margin-top: .75rem;
        }

        .quick-filters {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
            font-size: .78rem;
        }

        .quick-filters span {
            color: rgba(148, 163, 184, 1);
        }

        .pagination-wrap {
            margin-top: .75rem;
        }

        .row-outstanding {
            background: rgba(254, 243, 199, 0.45);
        }

        .row-completed-soft {
            background: rgba(240, 253, 244, 0.9);
        }

        .request-meta {
            font-size: .72rem;
            color: rgba(148, 163, 184, 1);
            margin-top: .1rem;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .table-wrap {
                border-radius: 10px;
                overflow-x: auto;
            }

            .table {
                min-width: 860px;
            }

            .top-actions {
                flex-direction: column;
                align-items: stretch;
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
                        <div class="section-title">PRD • Permintaan dari RTS</div>
                        <div class="mt-1 subtitle">
                            Permintaan stok dari
                            <strong>Gudang Packing Online (RTS)</strong>
                            yang harus dipenuhi oleh <strong>Gudang Produksi (PRD)</strong>.
                        </div>
                    </div>
                    <div class="hidden sm:flex flex-col items-end gap-1">
                        <div class="period-label">
                            Periode:
                            @switch($period)
                                @case('today')
                                    Hari ini
                                @break

                                @case('week')
                                    Minggu ini
                                @break

                                @case('month')
                                    Bulan ini
                                @break

                                @case('all')
                                    Semua waktu
                                @break

                                @default
                                    {{ ucfirst($period) }}
                            @endswitch
                        </div>
                        <div class="quick-filters">
                            <span>Filter cepat:</span>
                            <span class="chip chip-soft">
                                Pending: {{ $stats['pending'] ?? 0 }}
                            </span>
                            <span class="chip chip-soft">
                                Completed: {{ $stats['completed'] ?? 0 }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Stats --}}
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Total Dokumen</div>
                        <div class="stat-value">{{ $stats['total'] ?? 0 }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Belum Selesai (Pending)</div>
                        <div class="stat-value">{{ $stats['pending'] ?? 0 }}</div>
                        <div class="stat-sub">
                            Submitted + Partial
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Sudah Selesai</div>
                        <div class="stat-value">{{ $stats['completed'] ?? 0 }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Outstanding Qty</div>
                        <div class="stat-value">{{ number_format($outstandingQty ?? 0, 0) }}</div>
                        <div class="stat-sub">pcs belum terkirim ke RTS</div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                {{-- Filter --}}
                <div class="top-actions">
                    <form method="GET" action="{{ route('prd.stock-requests.index') }}" class="filters-row">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="form-control" onchange="this.form.submit()">
                                <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>Semua</option>
                                <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="submitted" {{ $statusFilter === 'submitted' ? 'selected' : '' }}>Submitted
                                </option>
                                <option value="partial" {{ $statusFilter === 'partial' ? 'selected' : '' }}>Partial
                                </option>
                                <option value="completed" {{ $statusFilter === 'completed' ? 'selected' : '' }}>Completed
                                </option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="period">Periode</label>
                            <select id="period" name="period" class="form-control" onchange="this.form.submit()">
                                <option value="today" {{ $period === 'today' ? 'selected' : '' }}>Hari ini</option>
                                <option value="week" {{ $period === 'week' ? 'selected' : '' }}>Minggu ini</option>
                                <option value="month" {{ $period === 'month' ? 'selected' : '' }}>Bulan ini</option>
                                <option value="all" {{ $period === 'all' ? 'selected' : '' }}>Semua waktu</option>
                            </select>
                        </div>
                    </form>

                    <div class="hidden sm:flex items-center gap-2">
                        {{-- Kalau nanti mau tambah tombol export / quick link bisa taruh di sini --}}
                    </div>
                </div>

                {{-- Tabel daftar dokumen --}}
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 70px;">Tanggal</th>
                                <th style="width: 140px;">No. Dokumen</th>
                                <th>RTS Tujuan</th>
                                <th style="width: 210px;">Qty (Req / Issued)</th>
                                <th style="width: 120px;">Status</th>
                                <th style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($stockRequests as $req)
                                @php
                                    $reqQty = (float) ($req->total_requested_qty ?? 0);
                                    $issuedQty = (float) ($req->total_issued_qty ?? 0);
                                    $outstanding = max($reqQty - $issuedQty, 0);

                                    $status = $req->status;
                                    $statusClass = match ($status) {
                                        'submitted' => 'badge-submitted',
                                        'partial' => 'badge-partial',
                                        'completed' => 'badge-completed',
                                        'draft' => 'badge-draft',
                                        default => 'badge-draft',
                                    };

                                    $isProcessable = in_array($status, ['submitted', 'partial']);
                                    $rowClass = '';
                                    if ($outstanding > 0 && $isProcessable) {
                                        $rowClass = 'row-outstanding';
                                    } elseif ($status === 'completed') {
                                        $rowClass = 'row-completed-soft';
                                    }

                                    $requestedBy = $req->requestedBy->name ?? null;
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td class="mono">
                                        {{ \Illuminate\Support\Carbon::parse($req->date)->format('d/m') }}
                                    </td>
                                    <td class="mono">
                                        {{ $req->code }}
                                        @if ($requestedBy)
                                            <div class="request-meta">
                                                oleh: {{ $requestedBy }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div style="font-size:.8rem;">
                                            <span class="chip">
                                                {{ $req->destinationWarehouse->code ?? '-' }}
                                            </span>
                                        </div>
                                        <div class="request-meta">
                                            {{ $req->destinationWarehouse->name ?? '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="mono">
                                            {{ number_format($reqQty, 0) }} / {{ number_format($issuedQty, 0) }} pcs
                                        </div>
                                        @if ($outstanding > 0)
                                            <div class="stat-sub">
                                                Outstanding: {{ number_format($outstanding, 0) }} pcs
                                            </div>
                                        @else
                                            <div class="stat-sub">
                                                Sudah terpenuhi
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge-status {{ $statusClass }}">
                                            {{ ucfirst($status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($isProcessable)
                                            <a href="{{ route('prd.stock-requests.edit', $req) }}" class="btn btn-sm">
                                                Proses di PRD
                                            </a>
                                        @else
                                            <a href="{{ route('prd.stock-requests.edit', $req) }}"
                                                class="btn-outline btn-sm">
                                                Audit Detail PRD
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6"
                                        style="text-align:center; padding:.9rem .6rem; font-size:.8rem; color:rgba(148,163,184,1);">
                                        Belum ada permintaan RTS pada periode &amp; filter ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="pagination-wrap">
                    {{ $stockRequests->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
