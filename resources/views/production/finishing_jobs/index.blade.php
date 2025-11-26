{{-- resources/views/production/finishing_jobs/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Finishing Jobs')

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

        .badge-status {
            border-radius: 999px;
            padding: .12rem .6rem;
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

        .row-clickable {
            cursor: pointer;
            transition: background-color .12s ease, box-shadow .12s ease;
        }

        .row-clickable:hover {
            background: color-mix(in srgb, var(--card) 85%, var(--line) 15%);
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .65rem;
            }

            .table-wrap {
                font-size: .85rem;
            }

            /* Mobile: jadikan list card */
            .table-main {
                display: none;
            }

            .list-mobile {
                display: flex;
                flex-direction: column;
                gap: .6rem;
            }

            .job-card {
                border-radius: 14px;
                border: 1px solid var(--line);
                padding: .6rem .75rem;
                background: var(--card);
            }

            .job-card-header {
                display: flex;
                justify-content: space-between;
                gap: .4rem;
                align-items: center;
                margin-bottom: .15rem;
            }

            .job-card-title {
                font-size: .9rem;
                font-weight: 600;
            }

            .job-card-meta {
                font-size: .8rem;
                color: var(--muted);
            }

            .job-card-footer {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: .25rem;
                font-size: .8rem;
            }
        }

        @media (min-width: 768px) {
            .list-mobile {
                display: none;
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

        {{-- HEADER --}}
        <div class="card p-3 mb-3">
            <div class="page-header">
                <div class="page-title-wrap">
                    <div class="page-icon">
                        <i class="bi bi-scissors"></i>
                    </div>
                    <div>
                        <h1 class="page-title">Finishing Jobs</h1>
                        <div class="page-subtitle">
                            Daftar proses finishing dari WIP-FIN menjadi barang jadi (FG) + reject.
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <a href="{{ route('production.finishing_jobs.create') }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> Finishing Job Baru
                    </a>
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
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="draft" @selected($status === 'draft')>Draft</option>
                        <option value="posted" @selected($status === 'posted')>Posted</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Cari</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                        placeholder="Cari kode / catatan" value="{{ $search }}">
                </div>
                <div class="col-12 col-md-1 d-flex justify-content-end gap-2 mt-2 mt-md-0">
                    <a href="{{ route('production.finishing_jobs.index') }}" class="btn btn-sm btn-outline-secondary">
                        Reset
                    </a>
                    <button type="submit" class="btn btn-sm btn-primary">
                        Tampil
                    </button>
                </div>
            </form>
        </div>

        {{-- TABLE DESKTOP --}}
        <div class="card p-0 mb-4">
            <div class="px-3 pt-3 pb-2 d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">Daftar Finishing Jobs</div>
                    <div class="help">
                        Klik baris untuk lihat detail & posting (kalau masih draft).
                    </div>
                </div>
                <div class="help">
                    Total: {{ $jobs->total() }} job
                </div>
            </div>

            <div class="table-wrap table-main">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 1%;">#</th>
                            <th>Kode</th>
                            <th>Tanggal</th>
                            <th>Created By</th>
                            <th class="text-center">Lines</th>
                            <th class="text-end">Total In</th>
                            <th class="text-end text-success">Total OK</th>
                            <th class="text-end text-danger">Total Reject</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($jobs as $i => $job)
                            @php
                                $totalIn = $job->lines->sum('qty_in');
                                $totalOk = $job->lines->sum('qty_ok');
                                $totalReject = $job->lines->sum('qty_reject');
                            @endphp
                            <tr class="row-clickable"
                                onclick="window.location='{{ route('production.finishing_jobs.show', $job) }}'">
                                <td class="text-muted small">
                                    {{ $jobs->firstItem() + $i }}
                                </td>
                                <td class="mono">
                                    {{ $job->code }}
                                </td>
                                <td class="mono">
                                    {{ function_exists('id_date') ? id_date($job->date) : $job->date->format('Y-m-d') }}
                                </td>
                                <td class="small">
                                    {{ $job->createdBy->name ?? '-' }}
                                </td>
                                <td class="text-center mono">
                                    {{ $job->lines_count }}
                                </td>
                                <td class="text-end mono">
                                    {{ number_format($totalIn) }}
                                </td>
                                <td class="text-end mono text-success">
                                    {{ number_format($totalOk) }}
                                </td>
                                <td class="text-end mono text-danger">
                                    {{ number_format($totalReject) }}
                                </td>
                                <td>
                                    @if ($job->status === 'posted')
                                        <span class="badge-status badge-status-posted">POSTED</span>
                                    @else
                                        <span class="badge-status badge-status-draft">DRAFT</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    Belum ada Finishing Job.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- LIST MOBILE --}}
            <div class="list-mobile px-3 pb-3">
                @forelse($jobs as $job)
                    @php
                        $totalIn = $job->lines->sum('qty_in');
                        $totalOk = $job->lines->sum('qty_ok');
                        $totalReject = $job->lines->sum('qty_reject');
                    @endphp
                    <a href="{{ route('production.finishing_jobs.show', $job) }}" class="text-decoration-none text-reset">
                        <div class="job-card">
                            <div class="job-card-header">
                                <div class="job-card-title mono">
                                    {{ $job->code }}
                                </div>
                                <div>
                                    @if ($job->status === 'posted')
                                        <span class="badge-status badge-status-posted">POSTED</span>
                                    @else
                                        <span class="badge-status badge-status-draft">DRAFT</span>
                                    @endif
                                </div>
                            </div>
                            <div class="job-card-meta">
                                {{ function_exists('id_date') ? id_date($job->date) : $job->date->format('Y-m-d') }}
                                · {{ $job->createdBy->name ?? 'Unknown' }}
                            </div>
                            <div class="job-card-footer">
                                <div>
                                    <span class="mono">{{ $job->lines_count }}</span> baris
                                </div>
                                <div class="text-end">
                                    <div class="mono small">
                                        In: {{ number_format($totalIn) }} ·
                                        <span class="text-success">OK: {{ number_format($totalOk) }}</span> ·
                                        <span class="text-danger">RJ: {{ number_format($totalReject) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="text-center text-muted py-3">
                        Belum ada Finishing Job.
                    </div>
                @endforelse
            </div>

            @if ($jobs->hasPages())
                <div class="px-3 py-2 border-top">
                    {{ $jobs->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
