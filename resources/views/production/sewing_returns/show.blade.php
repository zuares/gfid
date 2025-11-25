@extends('layouts.app')

@section('title', 'Produksi • Sewing Return ' . $return->code)

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
            font-size: .85rem;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .15rem .5rem;
            font-size: .7rem;
        }

        .table-wrap {
            overflow-x: auto;
        }
    </style>
@endpush

@section('content')
    @php
        $totalBundles = $return->lines->count();
        $totalOk = $return->lines->sum('qty_ok');
        $totalReject = $return->lines->sum('qty_reject');

        $statusMap = [
            'draft' => ['DRAFT', 'secondary'],
            'posted' => ['POSTED', 'primary'],
            'closed' => ['CLOSED', 'success'],
        ];
        $cfg = $statusMap[$return->status] ?? [strtoupper($return->status ?? '-'), 'secondary'];

        $firstLine = $return->lines->first();
        $pickupLine = $firstLine?->pickupLine;
        $pickup = $pickupLine?->pickup;
        $warehouse = $return->warehouse ?? $pickup?->warehouse;
    @endphp

    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <h1 class="h5 mb-1">Sewing Return: {{ $return->code }}</h1>
                    <div class="help">
                        Tanggal: {{ $return->date?->format('Y-m-d') ?? $return->date }}
                    </div>
                    <div class="help mt-1">
                        Pickup:
                        @if ($pickup)
                            <span class="mono">{{ $pickup->code }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </div>
                    <div class="help">
                        Gudang Sewing:
                        @if ($warehouse)
                            <span class="mono">
                                {{ $warehouse->code }} — {{ $warehouse->name }}
                            </span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </div>
                    <div class="help">
                        Operator Jahit:
                        @if ($return->operator)
                            <span class="mono">
                                {{ $return->operator->code }} — {{ $return->operator->name }}
                            </span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </div>
                </div>

                <div class="d-flex flex-column align-items-end gap-2">
                    <span class="badge bg-{{ $cfg[1] }} px-3 py-2">
                        {{ $cfg[0] }}
                    </span>

                    <div class="d-flex gap-2">
                        <a href="{{ route('production.qc.index', ['stage' => 'sewing']) }}"
                            class="btn btn-sm btn-outline-secondary">
                            Kembali ke QC Sewing
                        </a>

                        @if ($pickup && Route::has('production.sewing_pickups.show'))
                            <a href="{{ route('production.sewing_pickups.show', $pickup) }}"
                                class="btn btn-sm btn-outline-primary">
                                Lihat Pickup
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            @if ($return->notes)
                <div class="mt-2 small text-muted">
                    Catatan: {{ $return->notes }}
                </div>
            @endif
        </div>

        {{-- SUMMARY --}}
        <div class="card p-3 mb-3">
            <h2 class="h6 mb-2">Ringkasan Sewing Return</h2>

            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <div class="help mb-1">Jumlah Bundles</div>
                    <div class="mono">
                        {{ $totalBundles }}
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="help mb-1">Total Qty OK (pcs)</div>
                    <div class="mono">
                        {{ number_format($totalOk, 2, ',', '.') }}
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="help mb-1">Total Qty Reject (pcs)</div>
                    <div class="mono">
                        {{ number_format($totalReject, 2, ',', '.') }}
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="help mb-1">Perpindahan Stok</div>
                    <div class="small">
                        <span class="mono">
                            From: {{ $warehouse?->code ?? 'SEWING' }}
                        </span>
                        <span class="mx-1">→</span>
                        <span class="mono">WIP-FIN & REJECT</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- DETAIL LINES --}}
        <div class="card p-3 mb-4">
            <h2 class="h6 mb-2">Detail Bundles</h2>

            <div class="table-wrap">
                <table class="table table-sm align-middle mono">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="width: 130px;">Pickup</th>
                            <th style="width: 150px;">Bundle Code</th>
                            <th style="width: 160px;">Item Jadi</th>
                            <th style="width: 200px;">Lot</th>
                            <th style="width: 120px;">Qty OK</th>
                            <th style="width: 120px;">Qty Reject</th>
                            <th style="width: 200px;">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($return->lines as $line)
                            @dump($line->toArray())
                            @dump($line->pickupLine?->toArray())
                            @dump($line->pickupLine?->bundle?->toArray())
                            @php
                                dd($line);

                                $pickupLine = $line->pickupLine;
                                $pickupRow = $pickupLine?->pickup;
                                $bundle = $pickupLine?->bundle;
                                $lot = $bundle?->cuttingJob?->lot;
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $pickupRow?->code ?? '-' }}</td>
                                <td>{{ $bundle?->bundle_code ?? '-' }}</td>
                                <td>{{ $bundle?->finishedItem?->code ?? '-' }}</td>
                                <td>
                                    @if ($lot)
                                        {{ $lot->item?->code ?? '-' }}
                                        <span class="badge-soft bg-light border text-muted">
                                            {{ $lot->code }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ number_format($line->qty_ok ?? 0, 2, ',', '.') }}</td>
                                <td>{{ number_format($line->qty_reject ?? 0, 2, ',', '.') }}</td>
                                <td>{{ $line->notes ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted small">
                                    Belum ada detail untuk Sewing Return ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
