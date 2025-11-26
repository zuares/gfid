{{-- resources/views/production/finishing_jobs/bundles_ready.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Bundles Ready for Finishing')

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
            font-size: .84rem;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .14rem .5rem;
            font-size: .7rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .bundle-row {
            cursor: pointer;
            transition: background-color .12s ease, box-shadow .12s ease;
        }

        .bundle-row:hover {
            background: color-mix(in srgb, var(--card) 70%, var(--line) 30%);
        }

        .bundle-row.selected {
            background: rgba(25, 135, 84, 0.06);
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .5rem;
            }

            .table-wrap {
                font-size: .86rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <h1 class="h5 mb-1">Bundles Ready for Finishing</h1>
                    <div class="help">
                        Daftar bundle dengan saldo WIP-FIN yang siap diproses Finishing Job.
                    </div>
                    @php
                        $summaryBundles = $totalBundles ?? $bundles->total();
                        $summaryWipQty = $totalWipQty ?? $bundles->sum('wip_qty');
                    @endphp
                    <div class="small text-muted mt-1">
                        {{ $summaryBundles }} bundle · {{ number_format($summaryWipQty) }} pcs di WIP-FIN
                    </div>
                </div>

                <div class="text-end">
                    <a href="{{ route('production.finishing_jobs.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Finishing Jobs
                    </a>
                </div>
            </div>

            {{-- FILTER --}}
            <form method="get" class="mt-2">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label form-label-sm">Kode Bundle</label>
                        <input type="text" name="bundle_code" value="{{ request('bundle_code') }}"
                            class="form-control form-control-sm" placeholder="Cari kode bundle...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label form-label-sm">Warna</label>
                        <input type="text" name="color" value="{{ request('color') }}"
                            class="form-control form-control-sm" placeholder="NVY / BLK / MST ...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label form-label-sm">Item ID</label>
                        <input type="number" name="item_id" value="{{ request('item_id') }}"
                            class="form-control form-control-sm" placeholder="Opsional">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary mt-3 mt-md-0">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>
                        <a href="{{ route('production.finishing_jobs.bundles_ready') }}"
                            class="btn btn-sm btn-outline-secondary mt-3 mt-md-0">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- FORM SELECT BUNDLES --}}
        <form id="ready-bundles-form" action="{{ route('production.finishing_jobs.create') }}" method="get">
            <div class="card p-0 mb-3">
                <div class="px-3 pt-3 pb-2 d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-semibold">Bundle dengan saldo WIP-FIN</div>
                        <div class="help">
                            Pilih satu atau beberapa bundle lalu klik "Buat Finishing Job".
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="submit" class="btn btn-sm btn-success" id="btn-create-job" disabled>
                            <i class="bi bi-check2-square me-1"></i>
                            Buat Finishing Job
                        </button>
                    </div>
                </div>

                <div class="table-wrap">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 1%;">
                                    <input type="checkbox" id="select-all">
                                </th>
                                <th>Bundle</th>
                                <th>Item</th>
                                <th>Cutting Job</th>
                                <th class="text-end">Saldo WIP-FIN</th>
                                <th class="text-end">Qty Cutting OK</th>
                                <th class="text-end">Total Pick ke Sewing</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bundles as $bundle)
                                @php
                                    $cutJob = $bundle->cuttingJob;
                                    $lot = $bundle->lot;
                                    $item = $bundle->finishedItem ?? $lot?->item;

                                    $wipFinBalance = (float) ($bundle->wip_qty ?? 0);
                                    $qtyCut = (float) ($bundle->qty_cutting_ok ?? ($bundle->qty_pcs ?? 0));
                                    $qtySewPicked = (float) ($bundle->sewing_picked_qty ?? 0);
                                @endphp

                                <tr class="bundle-row">
                                    <td>
                                        <input type="checkbox" class="bundle-checkbox" name="bundle_ids[]"
                                            value="{{ $bundle->id }}">
                                    </td>
                                    <td class="mono">
                                        {{ $bundle->bundle_code }}
                                        <div class="small text-muted">
                                            Bundle #{{ $bundle->bundle_no }}
                                            @if ($lot?->code)
                                                · LOT: {{ $lot->code }}
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if ($item)
                                            <div class="small fw-semibold">
                                                {{ $item->code ?? '' }} — {{ $item->name ?? '' }}
                                            </div>
                                            <div class="small text-muted">
                                                {{ $item->color ?? '' }}
                                            </div>
                                        @else
                                            <span class="text-muted small">Item tidak ditemukan</span>
                                        @endif
                                    </td>
                                    <td class="mono">
                                        @if ($cutJob)
                                            @if (Route::has('production.cutting_jobs.show'))
                                                <a href="{{ route('production.cutting_jobs.show', $cutJob) }}">
                                                    {{ $cutJob->code }}
                                                </a>
                                            @else
                                                {{ $cutJob->code }}
                                            @endif
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end mono">
                                        {{ number_format($wipFinBalance) }}
                                    </td>
                                    <td class="text-end mono text-muted">
                                        {{ number_format($qtyCut) }}
                                    </td>
                                    <td class="text-end mono text-success">
                                        {{ number_format($qtySewPicked) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Belum ada bundle dengan saldo WIP-FIN.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($bundles instanceof \Illuminate\Pagination\LengthAwarePaginator && $bundles->hasPages())
                    <div class="border-top px-3 py-2">
                        {{ $bundles->links() }}
                    </div>
                @endif
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            (function() {
                const tableBody = document.querySelector('tbody');
                const btnCreateJob = document.getElementById('btn-create-job');
                const selectAll = document.getElementById('select-all');

                function updateRowHighlight() {
                    const checkboxes = document.querySelectorAll('.bundle-checkbox');
                    checkboxes.forEach(cb => {
                        const row = cb.closest('.bundle-row');
                        if (!row) return;
                        row.classList.toggle('selected', cb.checked);
                    });
                }

                function updateButtonState() {
                    const checked = document.querySelectorAll('.bundle-checkbox:checked').length;
                    if (btnCreateJob) {
                        btnCreateJob.disabled = checked === 0;
                    }
                }

                // Click row = toggle checkbox
                if (tableBody) {
                    tableBody.addEventListener('click', function(e) {
                        const row = e.target.closest('.bundle-row');
                        if (!row) return;

                        const checkbox = row.querySelector('.bundle-checkbox');
                        if (!checkbox) return;

                        // Jangan double toggle kalau klik langsung checkbox
                        if (e.target === checkbox) {
                            updateRowHighlight();
                            updateButtonState();
                            return;
                        }

                        checkbox.checked = !checkbox.checked;
                        updateRowHighlight();
                        updateButtonState();
                    });
                }

                // Checkbox change
                document.addEventListener('change', function(e) {
                    if (e.target.classList.contains('bundle-checkbox')) {
                        updateRowHighlight();
                        updateButtonState();
                    }
                });

                // Select all
                if (selectAll) {
                    selectAll.addEventListener('change', function() {
                        const checkboxes = document.querySelectorAll('.bundle-checkbox');
                        checkboxes.forEach(cb => {
                            cb.checked = selectAll.checked;
                        });
                        updateRowHighlight();
                        updateButtonState();
                    });
                }

                // Init
                updateRowHighlight();
                updateButtonState();
            })();
        </script>
    @endpush
@endsection
