@extends('layouts.app')

@section('title', 'Inventory • Inventory Adjustment')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .75rem .75rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(129, 140, 248, 0.15) 0,
                    rgba(45, 212, 191, 0.10) 26%,
                    #f9fafb 60%);
        }

        .card-main {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .3);
            box-shadow:
                0 10px 28px rgba(15, 23, 42, .06),
                0 0 0 1px rgba(15, 23, 42, .03);
        }

        .badge-status {
            font-size: .7rem;
            padding: .18rem .5rem;
            border-radius: 999px;
            font-weight: 600;
        }

        .badge-status--draft {
            background: rgba(148, 163, 184, .25);
            color: #475569;
        }

        .badge-status--approved {
            background: rgba(22, 163, 74, .18);
            color: #15803d;
        }

        .badge-status--rejected {
            background: rgba(220, 38, 38, .16);
            color: #b91c1c;
        }

        .table-wrap {
            margin-top: .75rem;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .25);
            overflow: hidden;
        }

        .table thead th {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #64748b;
            background: rgba(15, 23, 42, .02);
        }

        @media(max-width: 767.98px) {
            .table thead {
                display: none;
            }

            .table tbody tr {
                display: block;
                padding: .5rem .75rem;
                border-bottom: 1px solid rgba(148, 163, 184, .3);
            }

            .table tbody td {
                display: flex;
                justify-content: space-between;
                gap: .75rem;
                padding: .2rem 0;
            }

            .table tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                color: #64748b;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h5 mb-1">Inventory • Adjustment</h1>
                <p class="text-muted mb-0" style="font-size:.86rem;">
                    Daftar penyesuaian stok (hasil Stock Opname atau manual).
                </p>
            </div>
            <div>
                {{-- tombol create bisa diaktifkan nanti --}}
                {{-- <a href="{{ route('inventory.adjustments.create') }}" class="btn btn-sm btn-primary">+ Adjustment</a> --}}
            </div>
        </div>

        <div class="card card-main mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label form-label-sm">Gudang</label>
                        <select name="warehouse_id" class="form-select form-select-sm">
                            <option value="">Semua gudang</option>
                            @foreach ($warehouses ?? [] as $wh)
                                <option value="{{ $wh->id }}" @selected(request('warehouse_id') == $wh->id)>
                                    {{ $wh->code }} — {{ $wh->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label form-label-sm">Status</label>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Semua status</option>
                            @foreach (['draft', 'approved', 'rejected'] as $st)
                                <option value="{{ $st }}" @selected(request('status') === $st)>
                                    {{ ucfirst($st) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label form-label-sm">Dari Tanggal</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}"
                            class="form-control form-control-sm">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label form-label-sm">Sampai</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                            class="form-control form-control-sm">
                    </div>

                    <div class="col-md-12 d-flex gap-2 mt-2">
                        <button class="btn btn-sm btn-outline-primary">Filter</button>
                        <a href="{{ route('inventory.adjustments.index') }}"
                            class="btn btn-sm btn-outline-secondary">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card card-main">
            <div class="card-body">

                <div class="d-flex justify-content-between mb-2">
                    <h2 class="h6 mb-0">Daftar Adjustment</h2>
                    @if (method_exists($adjustments, 'total'))
                        <span class="text-muted" style="font-size:.8rem;">
                            {{ $adjustments->total() }} dokumen
                        </span>
                    @endif
                </div>

                <div class="table-wrap">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Kode</th>
                                <th>Tanggal</th>
                                <th>Gudang</th>
                                <th>Status</th>
                                <th>Sumber</th>
                                <th class="text-end">Jumlah Item</th>
                                <th style="width:80px;"></th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($adjustments as $i => $adj)
                                @php
                                    $statusClass = match ($adj->status) {
                                        'approved' => 'badge-status badge-status--approved',
                                        'rejected' => 'badge-status badge-status--rejected',
                                        default => 'badge-status badge-status--draft',
                                    };
                                @endphp

                                <tr>
                                    <td data-label="#">
                                        {{ ($adjustments->currentPage() - 1) * $adjustments->perPage() + $i + 1 }}
                                    </td>

                                    <td data-label="Kode">
                                        <span class="fw-semibold">{{ $adj->code }}</span>
                                    </td>

                                    <td data-label="Tanggal">
                                        {{ $adj->date?->format('d M Y') }}
                                    </td>

                                    <td data-label="Gudang">
                                        {{ $adj->warehouse?->code }}
                                        <div class="text-muted" style="font-size:.82rem;">
                                            {{ $adj->warehouse?->name }}
                                        </div>
                                    </td>

                                    <td data-label="Status">
                                        <span class="{{ $statusClass }}">{{ ucfirst($adj->status) }}</span>
                                    </td>

                                    <td data-label="Sumber">
                                        @if ($adj->source_type === App\Models\StockOpname::class)
                                            <span class="text-primary">Stock Opname</span>
                                        @else
                                            <span class="text-muted">Manual</span>
                                        @endif
                                    </td>

                                    <td data-label="Jumlah Item" class="text-end">
                                        {{ $adj->lines_count ?? $adj->lines?->count() }}
                                    </td>

                                    <td data-label="Aksi" class="text-end">
                                        <a href="{{ route('inventory.adjustments.show', $adj) }}"
                                            class="btn btn-sm btn-outline-secondary">Detail</a>
                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-3">
                                        <span class="text-muted">Belum ada adjustment.</span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if (method_exists($adjustments, 'links'))
                    <div class="mt-3">
                        {{ $adjustments->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>
@endsection
