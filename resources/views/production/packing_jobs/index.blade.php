{{-- resources/views/production/packing_jobs/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Produksi • Packing Job')

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

        .page-icon {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: color-mix(in srgb, var(--primary, #0d6efd) 10%, var(--card) 90%);
            border: 1px solid color-mix(in srgb, var(--primary, #0d6efd) 30%, var(--line) 70%);
            font-size: 1.1rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .status-badge {
            font-size: .7rem;
            border-radius: 999px;
            padding: .18rem .6rem;
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

        {{-- HEADER --}}
        <div class="card p-3 mb-3">
            <div class="page-header">
                <div class="d-flex align-items-center gap-2">
                    <div class="page-icon">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div>
                        <h1 class="page-title">Packing Job</h1>
                        <div class="page-subtitle">
                            Dokumen <strong>packing status</strong> dari stok produksi di gudang
                            <strong>WH-PRD</strong>. Posting di sini hanya mengubah status dokumen, <strong>tidak
                                mengubah stok</strong>.
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-column align-items-end gap-2">
                    <a href="{{ route('production.packing_jobs.create') }}" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>
                        <span class="label">Packing Job Baru</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- FILTERS --}}
        <div class="card p-3 mb-3">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua</option>
                        <option value="draft" @selected($status === 'draft')>Draft</option>
                        <option value="posted" @selected($status === 'posted')>Posted</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Dari tanggal</label>
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Sampai tanggal</label>
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Cari</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                        placeholder="Kode / catatan / referensi" value="{{ $search }}">
                </div>

                <div class="col-12 d-flex justify-content-between mt-2">
                    <div class="help">
                        Filter berdasarkan tanggal dokumen, status, dan kata kunci (kode, catatan, referensi).
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-funnel me-1"></i> Terapkan
                        </button>
                        <a href="{{ route('production.packing_jobs.index') }}" class="btn btn-sm btn-outline-secondary">
                            Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- TABLE LIST --}}
        <div class="card p-0">
            <div class="px-3 pt-3 pb-2 d-flex justify-content-between align-items-center">
                <div>
                    <div class="fw-semibold">Daftar Packing Job</div>
                    <div class="help">
                        Klik kode dokumen untuk melihat detail item yang dipacking.
                    </div>
                </div>
                <div class="help">
                    Total dokumen: {{ $jobs->total() }}
                </div>
            </div>

            <div class="table-wrap">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 1%;">#</th>
                            <th>Kode</th>
                            <th>Tanggal</th>
                            <th>Channel / Ref</th>
                            <th>Gudang</th>
                            <th class="text-end">Total Packed</th>
                            <th>Pembuat</th>
                            <th>Status</th>
                            <th style="width: 1%;" class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($jobs as $i => $job)
                            @php
                                $rowNo = $jobs->firstItem() + $i;
                                $fromWh = $job->warehouseFrom;
                                $toWh = $job->warehouseTo;
                            @endphp
                            <tr>
                                <td class="text-muted small">{{ $rowNo }}</td>

                                {{-- KODE --}}
                                <td class="mono">
                                    <a href="{{ route('production.packing_jobs.show', $job->id) }}">
                                        {{ $job->code }}
                                    </a>
                                </td>

                                {{-- TANGGAL --}}
                                <td class="mono">
                                    @if ($job->date)
                                        {{ function_exists('id_date') ? id_date($job->date) : \Carbon\Carbon::parse($job->date)->format('Y-m-d') }}
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>

                                {{-- CHANNEL / REF --}}
                                <td>
                                    @if ($job->channel)
                                        <div class="small fw-semibold">{{ $job->channel }}</div>
                                    @endif
                                    @if ($job->reference)
                                        <div class="small text-muted">Ref: {{ $job->reference }}</div>
                                    @endif
                                    @if (!$job->channel && !$job->reference)
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>

                                {{-- GUDANG --}}
                                <td>
                                    <div class="small mono">
                                        {{ $fromWh?->code ?? 'WH-PRD' }}
                                        @if ($toWh && $toWh->id !== optional($fromWh)->id)
                                            <span class="text-muted">→ {{ $toWh->code }}</span>
                                        @endif
                                    </div>
                                    <div class="small text-muted">
                                        {{ $fromWh?->name ?? 'Gudang Produksi' }}
                                    </div>
                                </td>

                                {{-- TOTAL PACKED --}}
                                <td class="text-end mono">
                                    {{ number_format((float) $job->total_packed) }}
                                </td>

                                {{-- CREATED BY --}}
                                <td>
                                    @if ($job->createdBy)
                                        <div class="small fw-semibold">{{ $job->createdBy->name }}</div>
                                    @else
                                        <span class="text-muted small">-</span>
                                    @endif
                                </td>

                                {{-- STATUS --}}
                                <td>
                                    @if ($job->status === 'posted')
                                        <span class="status-badge bg-success-subtle text-success">
                                            <i class="bi bi-check-circle me-1"></i> posted
                                        </span>
                                    @else
                                        <span class="status-badge bg-secondary-subtle text-secondary">
                                            <i class="bi bi-pencil me-1"></i> draft
                                        </span>
                                    @endif
                                </td>

                                {{-- AKSI --}}
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('production.packing_jobs.show', $job->id) }}"
                                            class="btn btn-outline-secondary" title="Lihat detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @if ($job->status === 'draft')
                                            <a href="{{ route('production.packing_jobs.edit', $job->id) }}"
                                                class="btn btn-outline-primary" title="Edit draft">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('production.packing_jobs.post', $job->id) }}"
                                                method="post"
                                                onsubmit="return confirm('Posting dokumen ini? Hanya status yang berubah, stok tidak berubah.');">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-success" title="Posting">
                                                    <i class="bi bi-check2-circle"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('production.packing_jobs.unpost', $job->id) }}"
                                                method="post"
                                                onsubmit="return confirm('Unpost dokumen ini dan kembalikan ke status draft?');">
                                                @csrf
                                                <button type="submit" class="btn btn-outline-warning" title="Unpost">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    Belum ada Packing Job untuk filter yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="px-3 py-2 border-top">
                {{ $jobs->links() }}
            </div>
        </div>

    </div>
@endsection
