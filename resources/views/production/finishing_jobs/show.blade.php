{{-- resources/views/production/finishing_jobs/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Finishing Job ' . $job->code)

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

        .badge-status {
            border-radius: 999px;
            padding: .14rem .6rem;
            font-size: .7rem;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .badge-status-draft {
            background: color-mix(in srgb, var(--card) 85%, orange 15%);
            color: #b35a00;
        }

        .badge-status-posted {
            background: color-mix(in srgb, var(--card) 85%, seagreen 15%);
            color: #166534;
        }

        .table-wrap {
            overflow-x: auto;
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

        {{-- FLASH MESSAGE --}}
        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- HEADER CARD --}}
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <h1 class="h5 mb-0">
                            Finishing Job
                            <span class="mono">{{ $job->code }}</span>
                        </h1>

                        @if ($job->status === 'posted')
                            <span class="badge-status badge-status-posted">POSTED</span>
                        @else
                            <span class="badge-status badge-status-draft">DRAFT</span>
                        @endif
                    </div>

                    <div class="help">
                        Tanggal:
                        <span class="mono">
                            {{ function_exists('id_date') ? id_date($job->date) : $job->date->format('Y-m-d') }}
                        </span>
                        @if ($job->createdBy)
                            · Dibuat oleh:
                            <span class="mono">{{ $job->createdBy->name }}</span>
                        @endif
                    </div>

                    @if ($job->notes)
                        <div class="mt-2 small">
                            <span class="fw-semibold">Catatan:</span>
                            {!! nl2br(e($job->notes)) !!}
                        </div>
                    @endif
                </div>

                <div class="text-end d-flex flex-column gap-2 align-items-end">
                    <div class="d-flex gap-2">
                        <a href="{{ route('production.finishing_jobs.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Kembali
                        </a>

                        @if ($job->status !== 'posted')
                            <a href="{{ route('production.finishing_jobs.edit', $job) }}"
                                class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil-square me-1"></i> Edit Draft
                            </a>
                        @endif
                    </div>

                    @if ($job->status !== 'posted')
                        <form action="{{ route('production.finishing_jobs.post', $job) }}" method="post"
                            onsubmit="return confirm('Posting Finishing Job akan mengupdate stok:\nWIP-FIN → FG + REJECT.\nLanjutkan?');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-success mt-1">
                                <i class="bi bi-check2-circle me-1"></i>
                                Posting & Update Stok
                            </button>
                        </form>
                    @else
                        <div class="help mt-1">
                            Stok sudah ter-update pada saat posting.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- SUMMARY CARD --}}
        @php
            $totalIn = $job->lines->sum('qty_in');
            $totalOk = $job->lines->sum('qty_ok');
            $totalReject = $job->lines->sum('qty_reject');
        @endphp

        <div class="card p-3 mb-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="small text-muted mb-1">Total Qty In</div>
                    <div class="h5 mono mb-0">{{ number_format($totalIn) }}</div>
                    <div class="help">Jumlah pcs yang masuk proses finishing.</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted mb-1">Total OK (FG)</div>
                    <div class="h5 mono text-success mb-0">{{ number_format($totalOk) }}</div>
                    <div class="help">Masuk ke gudang FG setelah posting.</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted mb-1">Total Reject</div>
                    <div class="h5 mono text-danger mb-0">{{ number_format($totalReject) }}</div>
                    <div class="help">Masuk ke gudang REJECT (final reject).</div>
                </div>
            </div>
        </div>

        {{-- DETAIL LINES --}}
        <div class="card p-0 mb-4">
            <div class="px-3 pt-3 pb-2 d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">Detail Bundle</div>
                    <div class="help">
                        Hasil finishing per bundle: qty masuk, OK, dan reject.
                    </div>
                </div>
                <div class="help">
                    Total baris: {{ $job->lines->count() }}
                </div>
            </div>

            <div class="table-wrap">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 1%;">#</th>
                            <th>Bundle</th>
                            <th>Item (Barang Jadi)</th>
                            <th>Operator</th>
                            <th class="text-end">Qty In</th>
                            <th class="text-end text-success">OK</th>
                            <th class="text-end text-danger">Reject</th>
                            <th>Alasan Reject</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($job->lines as $i => $line)
                            @php
                                $bundle = $line->bundle;
                                $cutJob = $bundle?->cuttingJob;

                                // 🔁 PRIORITAS: barang jadi (finished item)
                                // 1) $line->item  (kolom item_id di finishing_job_lines → harusnya sudah finished item, K7BLK)
                                // 2) $bundle->finishedItem (fallback)
                                // 3) baru terakhir: lot item (raw material) kalau benar-benar kosong
                                $item = $line->item ?? ($bundle?->finishedItem ?? $bundle?->cuttingJob?->lot?->item);
                            @endphp
                            <tr>
                                {{-- NO --}}
                                <td class="text-muted small">
                                    {{ $i + 1 }}
                                </td>

                                {{-- BUNDLE --}}
                                <td class="mono">
                                    @if ($bundle)
                                        @php
                                            $bundleCode =
                                                $bundle->bundle_code ?? ($bundle->code ?? 'BND-' . $bundle->id);
                                        @endphp

                                        @if ($cutJob)
                                            {{-- link drilldown ke Cutting Job --}}
                                            <a href="{{ route('production.cutting_jobs.show', $cutJob) }}">
                                                {{ $bundleCode }}
                                            </a>
                                        @else
                                            {{ $bundleCode }}
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>

                                {{-- ITEM (BARANG JADI) --}}
                                <td>
                                    @if ($item)
                                        <div class="small fw-semibold">
                                            {{ $item->code ?? '' }} — {{ $item->name ?? '' }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $item->color ?? '' }}
                                            @if (isset($bundle->size_label))
                                                · Size: {{ $bundle->size_label }}
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted small">Item tidak ditemukan</span>
                                    @endif
                                </td>

                                {{-- OPERATOR --}}
                                <td>
                                    @if ($line->operator)
                                        <div class="small fw-semibold">
                                            {{ $line->operator->code ?? '' }} — {{ $line->operator->name }}
                                        </div>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>

                                {{-- QTY IN --}}
                                <td class="text-end mono">
                                    {{ number_format($line->qty_in) }}
                                </td>

                                {{-- OK --}}
                                <td class="text-end mono text-success">
                                    {{ number_format($line->qty_ok) }}
                                </td>

                                {{-- REJECT --}}
                                <td class="text-end mono text-danger">
                                    {{ number_format($line->qty_reject) }}
                                </td>

                                {{-- REJECT REASON --}}
                                <td>
                                    @if ($line->qty_reject > 0)
                                        @if ($line->reject_reason)
                                            <div class="small fw-semibold text-danger">
                                                {{ $line->reject_reason }}
                                            </div>
                                        @endif
                                        @if ($line->reject_notes)
                                            <div class="small text-muted">
                                                {!! nl2br(e($line->reject_notes)) !!}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    Belum ada detail finishing untuk job ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($job->lines->isNotEmpty())
                        <tfoot>
                            <tr class="table-light">
                                <th colspan="4" class="text-end">TOTAL</th>
                                <th class="text-end mono">{{ number_format($totalIn) }}</th>
                                <th class="text-end mono text-success">{{ number_format($totalOk) }}</th>
                                <th class="text-end mono text-danger">{{ number_format($totalReject) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- FOOT NOTE --}}
        <div class="help mb-4">
            Dibuat:
            <span class="mono">
                {{ function_exists('id_datetime') ? id_datetime($job->created_at) : $job->created_at->format('Y-m-d H:i') }}
            </span>
            · Diupdate:
            <span class="mono">
                {{ function_exists('id_datetime') ? id_datetime($job->updated_at) : $job->updated_at->format('Y-m-d H:i') }}
            </span>
        </div>
    </div>
@endsection
