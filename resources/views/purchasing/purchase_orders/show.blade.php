{{-- resources/views/purchasing/purchase_orders/show.blade.php --}}
@extends('layouts.app')

@section('title', 'PO ' . $order->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1080px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            overflow: hidden;
        }

        th.sticky {
            position: sticky;
            top: 0;
            background: var(--card);
            z-index: 1;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .tag {
            border-radius: 999px;
            padding: .15rem .65rem;
            font-size: .7rem;
            border: 1px solid var(--line);
            background: rgba(148, 163, 184, .12);
        }

        .tag-status-draft {
            background: rgba(148, 163, 184, .12);
            color: #64748b;
            border-color: rgba(148, 163, 184, .6);
        }

        .tag-status-approved {
            background: rgba(22, 163, 74, .12);
            color: #15803d;
            border-color: rgba(22, 163, 74, .6);
        }

        .tag-status-cancelled {
            background: rgba(220, 38, 38, .08);
            color: #b91c1c;
            border-color: rgba(220, 38, 38, .6);
        }

        .tag-grn {
            border-radius: 999px;
            padding: .15rem .6rem;
            font-size: .7rem;
            border: 1px solid rgba(59, 130, 246, .5);
            background: rgba(59, 130, 246, .08);
            color: #1d4ed8;
        }

        .badge-pill {
            border-radius: 999px;
            font-size: .7rem;
            padding: .1rem .5rem;
            border: 1px solid transparent;
        }

        .badge-posted {
            background: rgba(22, 163, 74, .12);
            color: #15803d;
            border-color: rgba(22, 163, 74, .5);
        }

        .badge-draft {
            background: rgba(148, 163, 184, .12);
            color: #64748b;
            border-color: rgba(148, 163, 184, .5);
        }




        /* MOBILE */
        @media (max-width: 768px) {
            .page-wrap {
                padding-inline: .75rem;
            }

            .po-item-name {
                font-size: .9rem;
                font-weight: 600;
            }

            .po-item-code {
                font-size: .78rem;
            }

            .card .card-body {
                padding: .75rem .85rem;
            }

            .card-header {
                padding: .6rem .85rem;
            }

            .po-mobile-card {
                border-top: 1px solid var(--line);
                padding-top: .5rem;
                margin-top: .5rem;
            }

            .po-mobile-card:first-of-type {
                border-top: none;
                padding-top: 0;
                margin-top: 0;
            }

            .po-actions .btn-action {
                width: 100%;
            }


        }
    </style>
@endpush

@section('content')
    @php
        $user = auth()->user();
        $status = $order->status;
        $statusClass = match ($status) {
            'approved' => 'tag tag-status-approved',
            'cancelled' => 'tag tag-status-cancelled',
            default => 'tag tag-status-draft',
        };

        $grnList = $order->purchaseReceipts ?? collect();
        $grnCount = $grnList->count();
    @endphp

    <div class="page-wrap py-4">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3 flex-column flex-md-row gap-2">

            {{-- Left: Title + kode --}}
            <div class="w-100 w-md-auto">
                <h2 class="mb-0">Purchase Order</h2>
                <div class="text-muted mono">Kode: {{ $order->code }}</div>
            </div>

            {{-- Right: Actions (desktop inline kanan, mobile stack) --}}
            <div class="d-flex flex-column flex-md-row flex-wrap gap-2 justify-content-end w-100 po-actions">

                {{-- Back button mobile --}}
                <a href="{{ route('purchasing.purchase_orders.index') }}"
                    class="btn btn-outline-secondary btn-sm btn-action d-md-none">
                    &larr; Kembali
                </a>

                {{-- Back button desktop --}}
                <a href="{{ route('purchasing.purchase_orders.index') }}"
                    class="btn btn-outline-secondary btn-sm btn-action d-none d-md-inline-flex align-items-center">
                    &larr; Kembali
                </a>

                {{-- EDIT (draft saja) --}}
                @if ($order->status === 'draft')
                    <a href="{{ route('purchasing.purchase_orders.edit', $order->id) }}"
                        class="btn btn-outline-primary btn-sm btn-action">
                        Edit PO
                    </a>
                @endif

                {{-- APPROVE (owner + draft) --}}
                @if ($user && $user->role === 'owner' && $order->status === 'draft')
                    <form action="{{ route('purchasing.purchase_orders.approve', $order->id) }}" method="POST"
                        onsubmit="return confirm('Approve PO ini? Setelah di-approve, PO tidak bisa diedit lagi.');">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm btn-action">
                            Approve PO
                        </button>
                    </form>
                @endif

                {{-- CANCEL (owner + draft/approved + belum ada GRN) --}}
                @if ($user && $user->role === 'owner' && in_array($order->status, ['draft', 'approved'], true) && $grnCount === 0)
                    <form action="{{ route('purchasing.purchase_orders.cancel', $order->id) }}" method="POST"
                        onsubmit="return confirm('Batalkan PO ini? Tindakan ini tidak bisa dibatalkan.');">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm btn-action">
                            Cancel PO
                        </button>
                    </form>
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

        {{-- INFO CARD --}}
        <div class="card mb-4">
            <div class="card-body row g-3">

                <div class="col-md-3 col-6">
                    <div class="text-muted small">Tanggal</div>
                    <div class="fw-semibold mono">
                        {{ id_date($order->date) }}
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="text-muted small">Supplier</div>
                    <div class="fw-semibold">
                        {{ optional($order->supplier)->name ?? '—' }}
                        @if ($order->supplier)
                            <div class="text-muted small mono">{{ $order->supplier->code }}</div>
                        @endif
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="text-muted small">Status</div>
                    <span class="{{ $statusClass }} mono">
                        {{ strtoupper($order->status) }}
                    </span>
                    @if ($grnCount > 0)
                        <span class="tag-grn mono ms-1">
                            GRN x{{ $grnCount }}
                        </span>
                    @endif
                </div>

                <div class="col-md-3 col-6">
                    <div class="text-muted small">Dibuat oleh</div>
                    <div class="fw-semibold">
                        {{ optional($order->createdBy)->name ?? '—' }}
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="text-muted small">Disetujui oleh</div>
                    <div class="fw-semibold">
                        @if ($order->approved_by)
                            {{ optional($order->approvedBy)->name ?? '—' }}
                            @if ($order->approved_at)
                                <div class="text-muted small mono">
                                    {{ $order->approved_at->format('Y-m-d H:i') }}
                                </div>
                            @endif
                        @else
                            <span class="text-muted">Belum di-approve</span>
                        @endif
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="text-muted small">Dibatalkan oleh</div>
                    <div class="fw-semibold">
                        @if ($order->cancelled_by)
                            {{ optional($order->cancelledBy)->name ?? '—' }}
                            @if ($order->cancelled_at)
                                <div class="text-muted small mono">
                                    {{ $order->cancelled_at->format('Y-m-d H:i') }}
                                </div>
                            @endif
                        @else
                            <span class="text-muted">Tidak dibatalkan</span>
                        @endif
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="text-muted small">Jumlah GRN</div>
                    <div class="fw-semibold">
                        {{ $grnCount }}
                    </div>
                </div>

                <div class="col-12">
                    <div class="text-muted small">Catatan</div>
                    <div>{{ $order->notes ?: '—' }}</div>
                </div>

            </div>
        </div>

        {{-- GOODS RECEIPTS (GRN) CARD --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="fw-semibold">
                    Goods Receipts (GRN) terkait
                </div>

                <div class="d-flex flex-wrap gap-2">
                    @if ($order->status === 'approved')
                        <a href="{{ route('purchasing.purchase_receipts.create_from_order', $order->id) }}"
                            class="btn btn-sm btn-outline-primary">
                            + GRN baru dari PO ini
                        </a>
                    @endif

                    <a href="{{ route('purchasing.purchase_receipts.index', ['po' => $order->id]) }}"
                        class="btn btn-sm btn-outline-secondary">
                        Lihat semua GRN
                    </a>
                </div>
            </div>

            {{-- DESKTOP TABLE --}}
            <div class="table-responsive d-none d-md-block">
                @if ($grnCount === 0)
                    <div class="p-3 text-muted small">
                        Belum ada GRN untuk PO ini.
                    </div>
                @else
                    <table class="table table-sm mb-0 mono align-middle">
                        <thead>
                            <tr>
                                <th style="width: 5%;">#</th>
                                <th style="width: 16%;">Tanggal</th>
                                <th style="width: 18%;">No. GRN</th>
                                <th style="width: 20%;">Warehouse</th>
                                <th>Catatan</th>
                                <th style="width: 16%;" class="text-end">Grand Total</th>
                                <th style="width: 12%;" class="text-center">Status</th>
                                <th style="width: 13%;" class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($grnList as $grn)
                                @php
                                    $isPosted = ($grn->status ?? 'draft') === 'posted';
                                    $badgeStatusClass = $isPosted
                                        ? 'badge-pill badge-posted'
                                        : 'badge-pill badge-draft';
                                    $statusIcon = $isPosted ? '✅' : '⏳';
                                    $statusLabel = $isPosted ? 'POSTED' : 'DRAFT';
                                    $wh = $grn->warehouse ?? null;
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>

                                    <td>{{ $grn->date ? id_date($grn->date) : '-' }}</td>

                                    <td>
                                        <a href="{{ route('purchasing.purchase_receipts.show', $grn->id) }}"
                                            class="text-decoration-none">
                                            {{ $grn->code ?? $grn->id }}
                                        </a>
                                    </td>

                                    <td>
                                        @if ($wh)
                                            <div class="fw-semibold">
                                                {{ $wh->code }}
                                            </div>
                                            <div class="text-muted small">
                                                {{ $wh->name }}
                                            </div>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>

                                    <td class="small">
                                        {{ $grn->notes ?: '—' }}
                                    </td>

                                    <td class="text-end">
                                        {{ isset($grn->grand_total) ? rupiah($grn->grand_total) : '—' }}
                                    </td>

                                    <td class="text-center">
                                        <span class="{{ $badgeStatusClass }}">
                                            {{ $statusIcon }} {{ $statusLabel }}
                                        </span>
                                    </td>

                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="{{ route('purchasing.purchase_receipts.show', $grn->id) }}"
                                                class="btn btn-xs btn-outline-secondary btn-sm">
                                                Detail
                                            </a>

                                            {{-- Tombol POST: hanya kalau belum posted --}}
                                            @if (!$isPosted)
                                                <form action="{{ route('purchasing.purchase_receipts.post', $grn->id) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Post GRN ini? Setelah di-post, stok akan ter-update.');">
                                                    @csrf
                                                    <button type="submit" class="btn btn-xs btn-success btn-sm">
                                                        Post
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- MOBILE CARDS --}}
            <div class="d-md-none">
                @if ($grnCount === 0)
                    <div class="p-3 text-muted small">
                        Belum ada GRN untuk PO ini.
                    </div>
                @else
                    <div class="p-3 pt-2">
                        @foreach ($grnList as $grn)
                            @php
                                $isPosted = ($grn->status ?? 'draft') === 'posted';
                                $badgeStatusClass = $isPosted ? 'badge-pill badge-posted' : 'badge-pill badge-draft';
                                $statusIcon = $isPosted ? '✅' : '⏳';
                                $statusLabel = $isPosted ? 'POSTED' : 'DRAFT';
                                $wh = $grn->warehouse ?? null;
                            @endphp

                            <div class="po-mobile-card">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-semibold mono">
                                            <a href="{{ route('purchasing.purchase_receipts.show', $grn->id) }}"
                                                class="text-decoration-none">
                                                {{ $grn->code ?? $grn->id }}
                                            </a>
                                        </div>
                                        <div class="text-muted small mono">
                                            {{ $grn->date ? id_date($grn->date) : '-' }}
                                        </div>
                                        @if ($wh)
                                            <div class="small mt-1">
                                                <span class="fw-semibold mono">{{ $wh->code }}</span>
                                                <span class="text-muted">• {{ $wh->name }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="text-end">
                                        <span class="{{ $badgeStatusClass }}">
                                            {{ $statusIcon }} {{ $statusLabel }}
                                        </span>
                                    </div>
                                </div>

                                <div class="small mt-2">
                                    <div class="text-muted">Catatan</div>
                                    <div>{{ $grn->notes ?: '—' }}</div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div class="small text-muted">
                                        Grand Total
                                    </div>
                                    <div class="mono fw-semibold">
                                        {{ isset($grn->grand_total) ? rupiah($grn->grand_total) : '—' }}
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-1 mt-2">
                                    <a href="{{ route('purchasing.purchase_receipts.show', $grn->id) }}"
                                        class="btn btn-outline-secondary btn-sm">
                                        Detail
                                    </a>

                                    @if (!$isPosted)
                                        <form action="{{ route('purchasing.purchase_receipts.post', $grn->id) }}"
                                            method="POST"
                                            onsubmit="return confirm('Post GRN ini? Setelah di-post, stok akan ter-update.');">
                                            @csrf
                                            <button type="submit" class="btn btn-success btn-sm">
                                                Post
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- DETAIL BARANG --}}
        <div class="card mb-4">
            <div class="card-header fw-semibold">
                Detail Barang
            </div>

            {{-- DESKTOP TABLE --}}
            <div class="table-responsive d-none d-md-block">
                <table class="table table-sm mb-0 mono">
                    <thead>
                        <tr>
                            <th class="sticky" style="width: 5%">No</th>
                            <th class="sticky">Item</th>
                            <th class="text-end sticky" style="width: 12%">Qty</th>
                            <th class="text-end sticky" style="width: 18%">Harga</th>
                            <th class="text-end sticky" style="width: 15%">Diskon</th>
                            <th class="text-end sticky" style="width: 18%">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($order->lines as $line)
                            <tr>
                                <td class="text-center align-middle">
                                    {{ $loop->iteration }}
                                </td>

                                <td>
                                    <div class="po-item-name">
                                        {{ optional($line->item)->name ?? '—' }}
                                    </div>
                                    @if ($line->item)
                                        <div class="text-muted small po-item-code">
                                            {{ $line->item->code }}
                                        </div>
                                    @endif
                                </td>

                                <td class="text-end">
                                    {{ decimal_id($line->qty, 2) }}
                                </td>

                                <td class="text-end">
                                    {{ angka($line->unit_price) }}
                                </td>

                                <td class="text-end">
                                    {{ angka($line->discount) }}
                                </td>

                                <td class="text-end fw-semibold">
                                    {{ angka($line->line_total) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">
                                    Tidak ada item
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-end">Subtotal</th>
                            <th class="text-end">
                                {{ rupiah($order->subtotal) }}
                            </th>
                        </tr>
                        <tr>
                            <th colspan="5" class="text-end">Diskon</th>
                            <th class="text-end">
                                {{ rupiah($order->discount) }}
                            </th>
                        </tr>
                        <tr>
                            <th colspan="5" class="text-end">
                                PPN
                                @if ($order->tax_percent)
                                    ({{ angka($order->tax_percent) }}%)
                                @endif
                            </th>
                            <th class="text-end">
                                {{ rupiah($order->tax_amount) }}
                            </th>
                        </tr>
                        <tr>
                            <th colspan="5" class="text-end">Ongkir</th>
                            <th class="text-end">
                                {{ rupiah($order->shipping_cost) }}
                            </th>
                        </tr>
                        <tr class="table-light">
                            <th colspan="5" class="text-end">Grand Total</th>
                            <th class="text-end fs-5 fw-bold">
                                {{ rupiah($order->grand_total) }}
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- MOBILE CARDS --}}
            <div class="d-md-none">
                <div class="p-3 pt-2">
                    @forelse ($order->lines as $line)
                        @php
                            $item = $line->item;
                        @endphp
                        <div class="po-mobile-card">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <div class="po-item-name">
                                        {{ $item->name ?? '—' }}
                                    </div>
                                    @if ($item)
                                        <div class="text-muted small mono">
                                            {{ $item->code }}
                                        </div>
                                    @endif
                                </div>
                                <div class="text-end mono small text-muted">
                                    No. {{ $loop->iteration }}
                                </div>
                            </div>

                            <div class="mt-2 small">
                                <div class="d-flex justify-content-between">
                                    <span>Qty</span>
                                    <span class="mono">{{ decimal_id($line->qty, 2) }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Harga</span>
                                    <span class="mono">{{ angka($line->unit_price) }}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Diskon</span>
                                    <span class="mono">{{ angka($line->discount) }}</span>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <div class="small text-muted">
                                    Total
                                </div>
                                <div class="mono fw-semibold">
                                    {{ angka($line->line_total) }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted py-3 small">
                            Tidak ada item
                        </div>
                    @endforelse

                    {{-- Ringkasan total di mobile --}}
                    <div class="mt-3 border-top pt-2 small">
                        <div class="d-flex justify-content-between">
                            <span>Subtotal</span>
                            <span class="mono">{{ rupiah($order->subtotal) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Diskon</span>
                            <span class="mono">{{ rupiah($order->discount) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>
                                PPN
                                @if ($order->tax_percent)
                                    ({{ angka($order->tax_percent) }}%)
                                @endif
                            </span>
                            <span class="mono">{{ rupiah($order->tax_amount) }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Ongkir</span>
                            <span class="mono">{{ rupiah($order->shipping_cost) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mt-1 fw-bold">
                            <span>Grand Total</span>
                            <span class="mono">{{ rupiah($order->grand_total) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- FOOT ACTION --}}
        <div class="d-flex justify-content-end">
            <a href="{{ route('purchasing.purchase_orders.index') }}" class="btn btn-outline-secondary">
                ⬅️ Kembali ke daftar
            </a>
        </div>
    </div>
@endsection
