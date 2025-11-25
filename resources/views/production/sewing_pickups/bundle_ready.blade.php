@extends('layouts.app')

@section('title', 'Produksi • Bundles Ready to Pick')

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
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .help {
            color: var(--muted);
            font-size: .85rem;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .17rem .5rem;
            font-size: .70rem;
        }

        .table-wrap {
            overflow-x: auto;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h5 mb-1">Bundles Ready to Pick (Sewing)</h1>
                    <div class="help">
                        Daftar bundle yang sudah QC Cutting dan siap dijahit.
                    </div>
                </div>

                <a href="{{ route('production.sewing_pickups.create') }}" class="btn btn-primary btn-sm">
                    + Sewing Pickup
                </a>
            </div>
        </div>

        {{-- TABEL BUNDLE --}}
        <div class="card p-3">
            <h2 class="h6 mb-2">Daftar Bundle QC OK</h2>

            <div class="table-wrap">
                <table class="table table-sm align-middle mono">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Bundle Code</th>
                            <th>Item Jadi</th>
                            <th>Qty (pcs)</th>
                            <th>QC OK</th>
                            <th>QC Reject</th>
                            <th>Lot</th>
                            <th>Operator Cutting</th>
                            <th style="width: 80px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bundles as $b)
                            @php
                                $qc = $b->qcResults->where('stage', 'cutting')->sortByDesc('qc_date')->first();
                            @endphp

                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $b->bundle_code }}</td>

                                <td>
                                    {{ $b->finishedItem?->code }}
                                </td>

                                <td>{{ number_format($b->qty_pcs, 2, ',', '.') }}</td>

                                <td>{{ number_format($qc?->qty_ok ?? 0, 2, ',', '.') }}</td>

                                <td>{{ number_format($qc?->qty_reject ?? 0, 2, ',', '.') }}</td>

                                <td>
                                    {{-- Item Lot + badge LOT Code --}}
                                    {{ $b->cuttingJob?->lot?->item?->code }}
                                    <span class="badge bg-secondary badge-soft">
                                        {{ $b->cuttingJob?->lot?->code }}
                                    </span>
                                </td>

                                <td>
                                    @php
                                        $op = $b->operator;
                                    @endphp
                                    {{ $op?->code ? $op->code . ' — ' . $op->name : '-' }}
                                </td>

                                <td>
                                    <a href="{{ route('production.sewing_pickups.create', ['bundle_id' => $b->id]) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        Pick
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted small">
                                    Belum ada bundle QC OK / siap dijahit.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

    </div>
@endsection
