@extends('layouts.app')

@section('title', 'Daftar Purchase Order')

@push('head')
    <style>
        .page-wrap {
            max-width: 1080px;
            margin-inline: auto;
            padding-bottom: 3rem;
        }

        .card-filter {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid var(--line);
            padding: .85rem .95rem;
            margin-bottom: .85rem;
        }

        .card-table {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid var(--line);
            overflow: hidden;
        }

        .table thead th {
            border-bottom-width: 1px;
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
        }

        .table tbody td {
            vertical-align: middle;
        }

        .badge-status {
            border-radius: 999px;
            font-size: .7rem;
            padding: .1rem .6rem;
            border: 1px solid transparent;
        }

        .badge-draft {
            background: rgba(148, 163, 184, .12);
            color: #64748b;
            border-color: rgba(148, 163, 184, .5);
        }

        .badge-approved {
            background: rgba(22, 163, 74, .12);
            color: #15803d;
            border-color: rgba(22, 163, 74, .6);
        }

        .badge-cancelled {
            background: rgba(220, 38, 38, .08);
            color: #b91c1c;
            border-color: rgba(220, 38, 38, .6);
        }

        .badge-grn {
            border-radius: 999px;
            font-size: .65rem;
            padding: .05rem .45rem;
            margin-left: .25rem;
            background: rgba(59, 130, 246, .08);
            color: #1d4ed8;
            border: 1px solid rgba(59, 130, 246, .5);
        }

        .row-draft {
            background: rgba(248, 250, 252, 0.9);
        }

        .mini-summary {
            font-size: .8rem;
        }

        .mini-summary strong {
            font-size: .9rem;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        /* Soft badges untuk summary (desktop) */
        .summary-pills {
            margin-top: .35rem;
        }

        .summary-pill {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .25rem .7rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.45);
            background: rgba(148, 163, 184, 0.06);
            font-size: .78rem;
        }

        .summary-pill-label {
            color: var(--muted);
        }

        .summary-pill-value {
            font-weight: 600;
        }

        /* ===== MOBILE STYLES ===== */
        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .75rem;
            }

            .card-filter {
                padding: .75rem .8rem;
            }

            .card-po-mobile {
                background: var(--card);
                border-radius: 12px;
                border: 1px solid var(--line);
                padding: .75rem .85rem;
                margin-bottom: .6rem;
            }

            .card-po-mobile h6 {
                font-size: .9rem;
                margin-bottom: .25rem;
            }

            .card-po-mobile .meta {
                font-size: .75rem;
                color: var(--muted);
            }

            .card-po-mobile .meta span+span::before {
                content: "•";
                margin-inline: .35rem;
                opacity: .65;
            }

            .card-po-mobile .amount {
                font-size: .9rem;
            }

            .card-po-mobile .actions a {
                font-size: .75rem;
                padding-inline: .45rem;
                padding-block: .2rem;
            }

            .mini-summary {
                font-size: .75rem;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $user = auth()->user();
        $statusOptions = [
            '' => 'Semua Status',
            'draft' => 'Draft',
            'approved' => 'Approved',
            'cancelled' => 'Cancelled',
        ];
    @endphp

    <div class="page-wrap py-3">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h2 class="mb-0">Purchase Orders</h2>
                <div class="text-muted small">
                    Kelola permintaan pembelian bahan sebelum masuk ke GRN.
                </div>
            </div>
            <div>
                @if ($user && in_array($user->role, ['owner', 'admin']))
                    <a href="{{ route('purchasing.purchase_orders.create') }}" class="btn btn-primary btn-sm">
                        + PO Baru
                    </a>
                @endif
            </div>
        </div>

        {{-- FLASH MESSAGE --}}
        @if (session('success'))
            <div class="alert alert-success py-2 small">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger py-2 small">
                {{ session('error') }}
            </div>
        @endif

        {{-- FILTERS + MINI SUMMARY --}}
        <div class="card-filter mb-3">
            <form method="GET" action="{{ route('purchasing.purchase_orders.index') }}">
                <div class="row g-2 align-items-end">
                    {{-- Supplier (baris 1 mobile, full width) --}}
                    <div class="col-md-3 col-12">
                        <label class="form-label small">Supplier</label>
                        <select name="supplier_id" class="form-select form-select-sm">
                            <option value="">Semua Supplier</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" @selected(request('supplier_id') == $supplier->id)>
                                    {{ $supplier->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Status (baris 2 mobile, full width) --}}
                    <div class="col-md-2 col-12">
                        <label class="form-label small">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(request('status') === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- From Date (baris 3 mobile, kiri) --}}
                    <div class="col-md-2 col-6">
                        <label class="form-label small">Dari Tanggal</label>
                        <input type="date" name="from_date" value="{{ request('from_date') }}"
                            class="form-control form-control-sm" />
                    </div>

                    {{-- To Date (baris 3 mobile, kanan) --}}
                    <div class="col-md-2 col-6">
                        <label class="form-label small">Sampai Tanggal</label>
                        <input type="date" name="to_date" value="{{ request('to_date') }}"
                            class="form-control form-control-sm" />
                    </div>

                    {{-- Tombol --}}
                    <div class="col-md-3 col-12">
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3 mt-md-0">
                            <button type="submit" class="btn btn-sm btn-primary">
                                Terapkan Filter
                            </button>
                            <a href="{{ route('purchasing.purchase_orders.index') }}"
                                class="btn btn-sm btn-outline-secondary">
                                Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>

            {{-- MINI SUMMARY --}}
            @if (isset($summary))
                <div class="mini-summary mt-2">

                    {{-- DESKTOP: soft badges (tanpa Total Grand Total) --}}
                    <div class="summary-pills d-none d-md-flex flex-wrap gap-2">
                        <div class="summary-pill">
                            <span class="summary-pill-label">Total PO:</span>
                            <span class="summary-pill-value mono">{{ $summary->total_orders }}</span>
                        </div>

                        <div class="summary-pill">
                            <span class="summary-pill-label">Draft / Approved / Cancelled:</span>
                            <span class="summary-pill-value mono">
                                {{ $summary->draft_count }} / {{ $summary->approved_count }} /
                                {{ $summary->cancelled_count ?? 0 }}
                            </span>
                        </div>

                        <div class="summary-pill">
                            <span class="summary-pill-label">PO terakhir:</span>
                            <span class="summary-pill-value mono">
                                {{ $summary->last_date ? id_date($summary->last_date) : '-' }}
                            </span>
                        </div>
                    </div>

                    {{-- MOBILE: satu baris per info (tanpa Total Grand Total) --}}
                    <div class="d-md-none text-muted">
                        <div class="d-flex justify-content-between">
                            <span>Total PO:</span>
                            <span class="mono"><strong>{{ $summary->total_orders }}</strong></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Draft / Approved / Cancelled:</span>
                            <span class="mono">
                                <strong>{{ $summary->draft_count }} / {{ $summary->approved_count }} /
                                    {{ $summary->cancelled_count ?? 0 }}</strong>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>PO terakhir:</span>
                            <span class="mono">
                                <strong>{{ $summary->last_date ? id_date($summary->last_date) : '-' }}</strong>
                            </span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- DESKTOP TABLE (md ke atas) --}}
        <div class="card-table d-none d-md-block">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 4%;">#</th>
                            <th style="width: 14%;">Tanggal</th>
                            <th style="width: 16%;">Kode PO</th>
                            <th>Supplier</th>
                            <th style="width: 14%;" class="text-end">Grand Total</th>
                            <th style="width: 14%;">Status</th>
                            <th style="width: 16%;">Approved by</th>
                            <th style="width: 12%;" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            @php
                                $badgeClass = match ($order->status) {
                                    'approved' => 'badge-status badge-approved',
                                    'cancelled' => 'badge-status badge-cancelled',
                                    default => 'badge-status badge-draft',
                                };

                                $rowClass = $order->status === 'draft' ? 'row-draft' : '';

                                $grnCount = $order->purchaseReceipts?->count() ?? 0;
                            @endphp

                            <tr class="{{ $rowClass }}">
                                <td class="text-center">
                                    {{ $loop->iteration + ($orders->currentPage() - 1) * $orders->perPage() }}
                                </td>

                                <td class="mono">
                                    {{ id_date($order->date) }}
                                </td>

                                <td class="mono">
                                    <a href="{{ route('purchasing.purchase_orders.show', $order->id) }}"
                                        class="text-decoration-none">
                                        {{ $order->code }}
                                    </a>
                                </td>

                                <td>
                                    {{ optional($order->supplier)->name ?? '—' }}
                                </td>

                                <td class="text-end mono">
                                    {{ rupiah($order->grand_total) }}
                                </td>

                                <td>
                                    <span class="{{ $badgeClass }}">
                                        {{ strtoupper($order->status) }}
                                    </span>

                                    @if ($grnCount > 0)
                                        <span class="badge-grn" title="{{ $grnCount }} GRN untuk PO ini">
                                            GRN x{{ $grnCount }}
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    <span class="small">
                                        {{ optional($order->approvedBy)->name ?? '—' }}
                                    </span>
                                </td>

                                <td class="text-end">
                                    <a href="{{ route('purchasing.purchase_orders.show', $order->id) }}"
                                        class="btn btn-xs btn-outline-secondary btn-sm">
                                        Detail
                                    </a>

                                    @if ($order->status === 'draft')
                                        <a href="{{ route('purchasing.purchase_orders.edit', $order->id) }}"
                                            class="btn btn-xs btn-outline-primary btn-sm">
                                            Edit
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-3">
                                    Belum ada Purchase Order.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div class="px-3 py-2">
                {{ $orders->links() }}
            </div>
        </div>

        {{-- MOBILE LIST (di bawah md) --}}
        <div class="d-md-none">
            @forelse ($orders as $order)
                @php
                    $badgeClass = match ($order->status) {
                        'approved' => 'badge-status badge-approved',
                        'cancelled' => 'badge-status badge-cancelled',
                        default => 'badge-status badge-draft',
                    };

                    $grnCount = $order->purchaseReceipts?->count() ?? 0;
                @endphp

                <div class="card-po-mobile">
                    {{-- Kode + Status --}}
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div>
                            <h6 class="mb-0 mono">
                                <a href="{{ route('purchasing.purchase_orders.show', $order->id) }}"
                                    class="text-decoration-none">
                                    {{ $order->code }}
                                </a>
                            </h6>
                            <div class="meta mt-1">
                                <span class="mono">{{ id_date($order->date) }}</span>
                                @if ($order->supplier)
                                    <span>{{ $order->supplier->name }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="{{ $badgeClass }}">
                                {{ strtoupper($order->status) }}
                            </span>
                            @if ($grnCount > 0)
                                <div class="mt-1">
                                    <span class="badge-grn" title="{{ $grnCount }} GRN untuk PO ini">
                                        GRN x{{ $grnCount }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Grand total + approved by --}}
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <div>
                            <div class="text-muted meta mb-1">
                                Grand Total
                            </div>
                            <div class="amount mono">
                                {{ rupiah($order->grand_total) }}
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="text-muted meta mb-1">
                                Approved by
                            </div>
                            <div class="small">
                                {{ optional($order->approvedBy)->name ?? '—' }}
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="d-flex justify-content-end gap-1 mt-2 actions">
                        <a href="{{ route('purchasing.purchase_orders.show', $order->id) }}"
                            class="btn btn-outline-secondary btn-sm">
                            Detail
                        </a>
                        @if ($order->status === 'draft')
                            <a href="{{ route('purchasing.purchase_orders.edit', $order->id) }}"
                                class="btn btn-outline-primary btn-sm">
                                Edit
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center text-muted py-3 small">
                    Belum ada Purchase Order.
                </div>
            @endforelse

            <div class="mt-2">
                {{ $orders->links() }}
            </div>
        </div>
    </div>
@endsection
