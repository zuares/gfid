@extends('layouts.app')

@section('title', 'RTS • Stock Request')

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
            background: color-mix(in srgb, var(--card) 82%, rgba(59, 130, 246, 0.08));
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

        .btn-sm {
            padding: .25rem .7rem;
            font-size: .78rem;
        }

        .btn+.btn {
            margin-left: .4rem;
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

        .pagination-wrap {
            margin-top: .75rem;
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
                min-width: 820px;
            }

            .top-actions {
                flex-direction: column-reverse;
                align-items: stretch;
            }

            .top-actions-right {
                display: flex;
                justify-content: flex-start;
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
                        <div class="section-title">RTS • Stock Request</div>
                        <div class="mt-1 text-sm text-slate-700 dark:text-slate-200">
                            Daftar permintaan stok dari
                            <strong>Gudang Produksi (PRD)</strong> ke
                            <strong>Gudang Packing Online (RTS)</strong>.
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
                        <a href="{{ route('rts.stock-requests.create') }}" class="btn btn-sm">
                            + Buat Stock Request
                        </a>
                    </div>
                </div>

                {{-- Stats --}}
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Total Dokumen</div>
                        <div class="stat-value">{{ $stats['total'] ?? 0 }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Pending (Submitted + Partial)</div>
                        <div class="stat-value">{{ $stats['pending'] ?? 0 }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Completed</div>
                        <div class="stat-value">{{ $stats['completed'] ?? 0 }}</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Outstanding Qty</div>
                        <div class="stat-value">{{ number_format($outstandingQty ?? 0, 0) }}</div>
                        <div class="stat-sub">pcs belum terkirim dari PRD</div>
                    </div>
                </div>
            </div>

            <div class="card-body">
                {{-- Filter + tombol buat baru (mobile) --}}
                <div class="top-actions">
                    <form method="GET" action="{{ route('rts.stock-requests.index') }}" class="filters-row">
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

                        @if (request()->has('page'))
                            {{-- keep page in query when filter berubah? biasanya reset ke page 1, jadi ga perlu --}}
                        @endif
                    </form>

                    <div class="top-actions-right sm:hidden">
                        <a href="{{ route('rts.stock-requests.create') }}" class="btn btn-sm">
                            + Buat Stock Request
                        </a>
                    </div>
                </div>

                {{-- Tabel daftar dokumen --}}
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">Tanggal</th>
                                <th style="width: 130px;">No. Dokumen</th>
                                <th>Gudang</th>
                                <th style="width: 140px;">Qty (Req / Issued)</th>
                                <th style="width: 110px;">Status</th>
                                <th style="width: 110px;">Aksi</th>
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
                                @endphp
                                <tr>
                                    <td class="mono">
                                        {{ \Illuminate\Support\Carbon::parse($req->date)->format('d/m') }}
                                    </td>
                                    <td class="mono">
                                        <a href="{{ route('rts.stock-requests.show', $req) }}"
                                            class="text-blue-600 hover:underline">
                                            {{ $req->code }}
                                        </a>
                                    </td>
                                    <td>
                                        <div style="font-size:.8rem;">
                                            <span class="chip">
                                                {{ $req->sourceWarehouse->code ?? '-' }}
                                                <span style="opacity:.75;">→</span>
                                                {{ $req->destinationWarehouse->code ?? '-' }}
                                            </span>
                                        </div>
                                        <div style="font-size:.75rem; color:rgba(148,163,184,1); margin-top:.1rem;">
                                            {{ $req->sourceWarehouse->name ?? '-' }}
                                            → {{ $req->destinationWarehouse->name ?? '-' }}
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
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge-status {{ $statusClass }}">
                                            {{ ucfirst($status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('rts.stock-requests.show', $req) }}" class="btn-ghost btn-sm">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6"
                                        style="text-align:center; padding:.9rem .6rem; font-size:.8rem; color:rgba(148,163,184,1);">
                                        Belum ada Stock Request pada periode & filter ini.
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
