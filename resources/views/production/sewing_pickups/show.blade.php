@extends('layouts.app')

@section('title', 'Produksi • Sewing Pickup ' . $pickup->code)

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
        $totalBundles = $pickup->lines->count();
        $totalQty = $pickup->lines->sum('qty_bundle');

        $statusMap = [
            'draft' => ['label' => 'DRAFT', 'class' => 'secondary'],
            'posted' => ['label' => 'POSTED', 'class' => 'primary'],
            'closed' => ['label' => 'CLOSED', 'class' => 'success'],
        ];

        $cfg = $statusMap[$pickup->status] ?? [
            'label' => strtoupper($pickup->status ?? '-'),
            'class' => 'secondary',
        ];

        // apakah masih ada line in_progress (belum semua dikembalikan)?
        $hasInProgress = $pickup->lines->contains(fn($l) => $l->status === 'in_progress');

        // apakah sudah ada qty_return (OK / Reject) yang > 0?
        $hasReturn = $pickup->lines->contains(function ($l) {
            return ($l->qty_returned_ok ?? 0) > 0 || ($l->qty_returned_reject ?? 0) > 0;
        });

        $totalReturnOk = $pickup->lines->sum('qty_returned_ok');
        $totalReturnReject = $pickup->lines->sum('qty_returned_reject');
    @endphp

    <div class="page-wrap">

        {{-- HEADER ATAS --}}
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <h1 class="h5 mb-1">Sewing Pickup: {{ $pickup->code }}</h1>
                    <div class="help">
                        Tanggal: {{ $pickup->date?->format('Y-m-d') ?? $pickup->date }} •
                        Gudang: {{ $pickup->warehouse?->code ?? '-' }} —
                        {{ $pickup->warehouse?->name ?? '-' }}
                    </div>
                    <div class="help mt-1">
                        Operator Jahit:
                        @if ($pickup->operator)
                            <span class="mono">
                                {{ $pickup->operator->code }} — {{ $pickup->operator->name }}
                            </span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </div>
                </div>

                <div class="d-flex flex-column align-items-end gap-2">
                    <span class="badge bg-{{ $cfg['class'] }} px-3 py-2">
                        {{ $cfg['label'] }}
                    </span>

                    <div class="d-flex gap-2">
                        <a href="{{ route('production.sewing_pickups.index') }}" class="btn btn-sm btn-outline-secondary">
                            Kembali
                        </a>

                        {{-- Tombol Input Sewing Return kalau masih ada line in_progress --}}
                        @if ($hasInProgress)
                            <a href="{{ route('production.sewing_returns.create', ['pickup_id' => $pickup->id]) }}"
                                class="btn btn-sm btn-primary">
                                Input Sewing Return
                            </a>
                        @else
                            <button type="button" class="btn btn-sm btn-outline-success" disabled>
                                Semua bundle sudah dikembalikan
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            @if ($pickup->notes)
                <div class="mt-2 small text-muted">
                    Catatan: {{ $pickup->notes }}
                </div>
            @endif
        </div>

        {{-- SUMMARY --}}
        <div class="card p-3 mb-3">
            <h2 class="h6 mb-2">Ringkasan Pickup</h2>

            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <div class="help mb-1">Jumlah Bundle</div>
                    <div class="mono">
                        {{ $totalBundles }}
                    </div>
                </div>

                <div class="col-md-3 col-6">
                    <div class="help mb-1">Total Qty Pickup (pcs)</div>
                    <div class="mono">
                        {{ number_format($totalQty, 2, ',', '.') }}
                    </div>
                </div>

                @if ($hasReturn)
                    <div class="col-md-3 col-6">
                        <div class="help mb-1">Total Return OK (pcs)</div>
                        <div class="mono">
                            {{ number_format($totalReturnOk, 2, ',', '.') }}
                        </div>
                    </div>

                    <div class="col-md-3 col-6">
                        <div class="help mb-1">Total Return Reject (pcs)</div>
                        <div class="mono">
                            {{ number_format($totalReturnReject, 2, ',', '.') }}
                        </div>
                    </div>
                @else
                    <div class="col-md-6 col-12">
                        <div class="help mb-1">Gudang Asal & Tujuan</div>
                        <div class="small">
                            {{-- Asumsi: asal = WIP-CUT, tujuan = warehouse sewing --}}
                            <span class="mono">From: WIP-CUT</span>
                            <span class="mx-2">→</span>
                            <span class="mono">
                                To: {{ $pickup->warehouse?->code ?? '-' }}
                            </span>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- TABEL DETAIL BUNDLE --}}
        <div class="card p-3 mb-4">
            <h2 class="h6 mb-2">Detail Bundles</h2>

            <div class="table-wrap">
                <table class="table table-sm align-middle mono">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="width: 150px;">Bundle Code</th>
                            <th style="width: 160px;">Item Jadi</th>
                            <th style="width: 180px;">Lot</th>
                            <th style="width: 120px;">Qty Pickup</th>
                            @if ($hasReturn)
                                <th style="width: 120px;">Qty Return OK</th>
                                <th style="width: 120px;">Qty Return Reject</th>
                                <th style="width: 120px;">Sisa Belum Return</th>
                            @endif
                            <th style="width: 110px;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pickup->lines as $line)
                            @php
                                $bundle = $line->bundle;
                                $lot = $bundle?->cuttingJob?->lot;
                                $statusLine = $line->status ?? 'in_progress';

                                $statusLineMap = [
                                    'in_progress' => ['label' => 'IN PROGRESS', 'class' => 'warning'],
                                    'done' => ['label' => 'DONE', 'class' => 'success'],
                                ];
                                $cfgLine = $statusLineMap[$statusLine] ?? [
                                    'label' => strtoupper($statusLine),
                                    'class' => 'secondary',
                                ];

                                $returnedOk = (float) ($line->qty_returned_ok ?? 0);
                                $returnedReject = (float) ($line->qty_returned_reject ?? 0);
                                $remaining = (float) $line->qty_bundle - ($returnedOk + $returnedReject);
                            @endphp
                            <tr>
                                <td>{{ $loop->iteration }}</td>
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
                                <td>{{ number_format($line->qty_bundle, 2, ',', '.') }}</td>

                                @if ($hasReturn)
                                    <td>{{ number_format($returnedOk, 2, ',', '.') }}</td>
                                    <td>{{ number_format($returnedReject, 2, ',', '.') }}</td>
                                    <td>{{ number_format($remaining, 2, ',', '.') }}</td>
                                @endif

                                <td>
                                    <span class="badge bg-{{ $cfgLine['class'] }}">
                                        {{ $cfgLine['label'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $hasReturn ? 8 : 6 }}" class="text-center text-muted small">
                                    Belum ada detail bundle untuk pickup ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
