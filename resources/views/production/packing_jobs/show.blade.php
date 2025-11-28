{{-- resources/views/production/packing_jobs/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Packing ' . ($job->code ?? ''))

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding-block: .75rem 1.5rem;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 16px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .help {
            color: var(--muted);
            font-size: .84rem;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .page-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin: 0;
        }

        .page-subtitle {
            font-size: .85rem;
            color: var(--muted);
        }

        .status-badge {
            font-size: .75rem;
            border-radius: 999px;
            padding: .2rem .7rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .badge-warehouse {
            border-radius: 999px;
            padding: .15rem .7rem;
            font-size: .75rem;
            background: color-mix(in srgb, var(--card) 70%, var(--line) 30%);
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .5rem;
            }

            .page-header {
                align-items: flex-start;
            }

            .page-title {
                font-size: 1rem;
            }

            .page-subtitle {
                font-size: .8rem;
            }

            .table-wrap {
                font-size: .86rem;
            }

            .btn .label {
                display: none;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        @php
            $fromWh = $job->warehouseFrom;
            $toWh = $job->warehouseTo;
            $totalLines = $job->lines->count();
            $totalPacked = (float) $job->lines->sum('qty_packed');
        @endphp

        {{-- HEADER --}}
        <div class="card p-3 mb-3">
            <div class="page-header">
                <div>
                    <h1 class="page-title">
                        Packing Job • <span class="mono">{{ $job->code }}</span>
                    </h1>
                    <div class="page-subtitle">
                        Dokumen ini hanya mencatat <strong>status packing</strong> dari stok produksi (WH-PRD).
                        Posting / unpost <strong>tidak mengubah stok gudang</strong>, hanya status dokumen.
                    </div>

                    <div class="mt-2 d-flex flex-wrap gap-2 align-items-center">
                        {{-- Status --}}
                        @if ($job->status === 'posted')
                            <span class="status-badge bg-success-subtle text-success">
                                <i class="bi bi-check-circle me-1"></i> posted
                            </span>
                        @else
                            <span class="status-badge bg-secondary-subtle text-secondary">
                                <i class="bi bi-pencil me-1"></i> draft
                            </span>
                        @endif>

                        {{-- Tanggal --}}
                        @if ($job->date)
                            <span class="badge bg-light border mono">
                                {{ function_exists('id_date') ? id_date($job->date) : \Carbon\Carbon::parse($job->date)->format('Y-m-d') }}
                            </span>
                        @endif

                        {{-- Gudang --}}
                        @if ($fromWh)
                            <span class="badge-warehouse mono">
                                {{ $fromWh->code }} — {{ $fromWh->name }}
                            </span>
                        @endif
                    </div>
                </div>

                <div class="d-flex flex-column align-items-end gap-2">
                    <a href="{{ route('production.packing_jobs.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i>
                        <span class="label">Kembali ke daftar</span>
                    </a>

                    <div class="d-flex gap-2">
                        @if ($job->status === 'draft')
                            <a href="{{ route('production.packing_jobs.edit', $job->id) }}"
                                class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil me-1"></i>
                                <span class="label">Edit Draft</span>
                            </a>
                            <form action="{{ route('production.packing_jobs.post', $job->id) }}" method="post"
                                onsubmit="return confirm('Posting Packing Job ini? Hanya status yang berubah, stok tidak berubah.');">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="bi bi-check2-circle me-1"></i>
                                    <span class="label">Posting</span>
                                </button>
                            </form>
                        @else
                            <form action="{{ route('production.packing_jobs.unpost', $job->id) }}" method="post"
                                onsubmit="return confirm('Unpost dokumen ini dan kembalikan ke status draft?');">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-warning">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>
                                    <span class="label">Unpost</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- INFO HEADER --}}
        <div class="card p-3 mb-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="small text-muted mb-1">Tanggal</div>
                    <div class="mono">
                        @if ($job->date)
                            {{ function_exists('id_date') ? id_date($job->date) : \Carbon\Carbon::parse($job->date)->format('Y-m-d') }}
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted mb-1">Channel</div>
                    <div>
                        {{ $job->channel ?? '-' }}
                    </div>
                    @if ($job->reference)
                        <div class="help">
                            Ref: {{ $job->reference }}
                        </div>
                    @endif
                </div>
                <div class="col-md-4">
                    <div class="small text-muted mb-1">Gudang Produksi</div>
                    <div>
                        @if ($fromWh)
                            <span class="mono">{{ $fromWh->code }}</span> — {{ $fromWh->name }}
                        @else
                            <span class="text-muted">WH-PRD (default)</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-4">
                    <div class="small text-muted mb-1">Total Baris</div>
                    <div class="h6 mono mb-0">{{ $totalLines }}</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted mb-1">Total Qty Packed</div>
                    <div class="h6 mono mb-0">{{ number_format($totalPacked) }}</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted mb-1">Dibuat oleh</div>
                    <div>
                        @if ($job->createdBy)
                            <span class="fw-semibold">{{ $job->createdBy->name }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </div>
                    <div class="help">
                        Dibuat:
                        {{ function_exists('id_datetime') && $job->created_at ? id_datetime($job->created_at) : $job->created_at?->format('Y-m-d H:i') ?? '-' }}
                    </div>
                </div>
            </div>

            @if ($job->notes)
                <div class="mt-3">
                    <div class="small text-muted mb-1">Catatan</div>
                    <div class="small">{{ $job->notes }}</div>
                </div>
            @endif

            <div class="mt-3 border-top pt-2 d-flex flex-wrap gap-3">
                <div class="help mb-0">
                    <strong>Catatan:</strong> Dokumen Packing Job ini <strong>tidak memindahkan stok</strong>. Stok fisik
                    tetap berada di gudang produksi WH-PRD. Dokumen ini dipakai untuk tracking status packing per item.
                </div>
            </div>
        </div>

        {{-- DETAIL BARIS PACKING --}}
        <div class="card p-0 mb-4">
            <div class="px-3 pt-3 pb-2 d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">Detail Item yang Dipacking</div>
                    <div class="help">
                        Menampilkan item, saldo produksi (snapshot saat input), dan Qty Packed per baris.
                    </div>
                </div>
                <div class="help">
                    Total baris: {{ $totalLines }}
                </div>
            </div>

            <div class="table-wrap">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 1%;">#</th>
                            <th>Item</th>
                            <th class="text-end">Saldo WH-PRD (snapshot)</th>
                            <th class="text-end">Qty Packed</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($job->lines as $i => $line)
                            @php
                                $item = $line->item;
                            @endphp
                            <tr>
                                <td class="text-muted small">{{ $i + 1 }}</td>

                                {{-- ITEM --}}
                                <td>
                                    @if ($item)
                                        <div class="small fw-semibold mono">
                                            {{ $item->code ?? '' }}
                                        </div>
                                        <div class="small">
                                            {{ $item->name ?? '' }}
                                            @if ($item->color)
                                                <span class="text-muted">• {{ $item->color }}</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted small">Item tidak ditemukan</span>
                                    @endif
                                </td>

                                {{-- SALDO SNAPSHOT --}}
                                <td class="text-end mono">
                                    {{ number_format((float) $line->qty_fg) }}
                                </td>

                                {{-- QTY PACKED --}}
                                <td class="text-end mono">
                                    {{ number_format((float) $line->qty_packed) }}
                                </td>

                                {{-- CATATAN BARIS --}}
                                <td>
                                    @if ($line->notes)
                                        <span class="small">{{ $line->notes }}</span>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Belum ada baris detail untuk dokumen ini.
                                </td>
                            </tr>
                        @endforelse
                        @if ($job->lines->isNotEmpty())
                    <tfoot>
                        <tr class="table-light">
                            <th colspan="3" class="text-end">TOTAL</th>
                            <th class="text-end mono">{{ number_format($totalPacked) }}</th>
                            <th></th>
                        </tr>
                    </tfoot>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
