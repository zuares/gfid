{{-- resources/views/production/reports/finishing_jobs.blade.php --}}
@extends('layouts.app')

@section('title', 'Report • Finishing Jobs per Item')

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

        .page-title-wrap {
            display: flex;
            align-items: center;
            gap: .8rem;
        }

        .page-icon {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: color-mix(in srgb, var(--primary, #0d6efd) 10%, var(--card) 90%);
            border: 1px solid color-mix(in srgb, var(--primary, #0d6efd) 30%, var(--line) 70%);
        }

        .page-title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 600;
        }

        .page-subtitle {
            font-size: .82rem;
            color: var(--muted);
        }

        .table-wrap {
            overflow-x: auto;
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .65rem;
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
            <div class="page-header">
                <div class="page-title-wrap">
                    <div class="page-icon">
                        <i class="bi bi-clipboard-data"></i>
                    </div>
                    <div>
                        <h1 class="page-title">Report Finishing Jobs per Item</h1>
                        <div class="page-subtitle">
                            Ringkasan perpindahan stok dari WIP-FIN → FG + REJECT per item dalam periode tertentu.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- FILTERS --}}
        <div class="card p-3 mb-3">
            <form method="get" class="row g-2 g-md-3 align-items-end">
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Dari tanggal</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Sampai tanggal</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small mb-1">Item</label>
                    <select name="item_id" class="form-select form-select-sm">
                        <option value="">Semua item</option>
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}" @selected($itemId == $item->id)>
                                {{ $item->code }} — {{ $item->name }} {{ $item->color }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small mb-1">Operator finishing</label>
                    <select name="operator_id" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        @foreach ($operators as $op)
                            <option value="{{ $op->id }}" @selected($operatorId == $op->id)>
                                {{ $op->code ?? '' }} — {{ $op->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-between mt-2">
                    <div class="help">
                        Periode:
                        <span class="mono">{{ $dateFrom }}</span>
                        s/d
                        <span class="mono">{{ $dateTo }}</span>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="" class="btn btn-sm btn-outline-secondary">
                            Reset
                        </a>
                        <button type="submit" class="btn btn-sm btn-primary">
                            Tampilkan
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {{-- SUMMARY --}}
        <div class="card p-3 mb-3">
            <div class="row g-3">
                <div class="col-4">
                    <div class="small text-muted mb-1">Total Qty In</div>
                    <div class="h5 mono mb-0">{{ number_format($summary['total_in']) }}</div>
                    <div class="help">Masuk proses finishing (WIP-FIN keluar).</div>
                </div>
                <div class="col-4">
                    <div class="small text-muted mb-1">Total OK (FG)</div>
                    <div class="h5 mono text-success mb-0">{{ number_format($summary['total_ok']) }}</div>
                    <div class="help">Masuk gudang FG.</div>
                </div>
                <div class="col-4">
                    <div class="small text-muted mb-1">Total Reject</div>
                    <div class="h5 mono text-danger mb-0">{{ number_format($summary['total_reject']) }}</div>
                    <div class="help">Masuk gudang REJECT.</div>
                </div>
            </div>
        </div>

        {{-- TABLE --}}
        <div class="card p-0 mb-4">
            <div class="px-3 pt-3 pb-2 d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">Detail per Item</div>
                    <div class="help">
                        Yield per item berdasarkan Finishing Job yang sudah diposting.
                    </div>
                </div>
                <div class="help">
                    Total item: {{ $rows->count() }}
                </div>
            </div>

            <div class="table-wrap">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 1%;">#</th>
                            <th>Item</th>
                            <th class="text-end">Qty In</th>
                            <th class="text-end text-success">OK (FG)</th>
                            <th class="text-end text-danger">Reject</th>
                            <th class="text-end">Yield %</th>
                            <th class="text-end">Reject %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $i => $row)
                            @php
                                $in = (float) $row->total_in;
                                $ok = (float) $row->total_ok;
                                $rej = (float) $row->total_reject;
                                $yieldPct = $in > 0 ? ($ok / $in) * 100 : 0;
                                $rejectPct = $in > 0 ? ($rej / $in) * 100 : 0;
                            @endphp
                            <tr>
                                <td class="text-muted small">{{ $i + 1 }}</td>
                                <td>
                                    <div class="small fw-semibold">
                                        {{ $row->item_code }} — {{ $row->item_name }}
                                    </div>
                                    <div class="small text-muted">
                                        {{ $row->item_color }}
                                    </div>
                                </td>
                                <td class="text-end mono">{{ number_format($in) }}</td>
                                <td class="text-end mono text-success">{{ number_format($ok) }}</td>
                                <td class="text-end mono text-danger">{{ number_format($rej) }}</td>
                                <td class="text-end mono">
                                    {{ number_format($yieldPct, 1) }}%
                                </td>
                                <td class="text-end mono">
                                    {{ number_format($rejectPct, 1) }}%
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Belum ada data finishing pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
