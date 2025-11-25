@extends('layouts.app')

@section('title', 'Produksi • QC Overview')

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

        .nav-qc .nav-link {
            border-radius: 999px;
            padding-inline: 1rem;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .badge-soft {
            border-radius: 999px;
            padding: .15rem .5rem;
            font-size: .7rem;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">

        {{-- HEADER --}}
        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h5 mb-1">QC Overview</h1>
                    <div class="help">
                        Monitoring QC untuk Cutting, Sewing, dan Packing.
                    </div>
                </div>
            </div>

            {{-- TAB STAGE --}}
            <ul class="nav nav-pills nav-qc mt-3">
                <li class="nav-item">
                    <a class="nav-link {{ $stage === 'cutting' ? 'active' : '' }}"
                        href="{{ route('production.qc.index', ['stage' => 'cutting']) }}">
                        QC Cutting
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $stage === 'sewing' ? 'active' : '' }}"
                        href="{{ route('production.qc.index', ['stage' => 'sewing']) }}">
                        QC Sewing
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $stage === 'packing' ? 'active' : '' }}"
                        href="{{ route('production.qc.index', ['stage' => 'packing']) }}">
                        QC Packing
                    </a>
                </li>
            </ul>
        </div>

        {{-- ISI TABEL PER STAGE --}}
        <div class="card p-3">
            @if ($stage === 'cutting')
                <h2 class="h6 mb-2">Daftar QC Cutting</h2>
                <div class="table-wrap">
                    <table class="table table-sm align-middle mono">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="width: 110px;">Tanggal</th>
                                <th style="width: 220px;">Lot</th>
                                <th style="width: 180px;">Bundles (Qty)</th>
                                <th style="width: 140px;">Status</th>
                                <th style="width: 90px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($records as $job)
                                @php
                                    $totalBundles = $job->bundles->count();
                                    $totalQty = $job->bundles->sum('qty_pcs');

                                    // mapping status → label + warna badge
                                    $rawStatus = $job->status ?? '-';

                                    if ($rawStatus === 'sent_to_qc') {
                                        $statusLabel = 'Belum QC';
                                        $statusClass = 'warning';
                                    } else {
                                        $map = [
                                            'cut' => ['label' => 'CUT', 'class' => 'primary'],
                                            'cut_qc_done' => ['label' => 'QC CUTTING', 'class' => 'info'],
                                            'qc_ok' => ['label' => 'QC OK', 'class' => 'success'],
                                            'qc_mixed' => ['label' => 'QC MIXED', 'class' => 'warning'],
                                            'qc_reject' => ['label' => 'QC REJECT', 'class' => 'danger'],
                                        ];

                                        $cfg = $map[$rawStatus] ?? [
                                            'label' => strtoupper($rawStatus),
                                            'class' => 'secondary',
                                        ];
                                        $statusLabel = $cfg['label'];
                                        $statusClass = $cfg['class'];
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration + ($records->currentPage() - 1) * $records->perPage() }}</td>
                                    <td>{{ $job->date?->format('Y-m-d') ?? $job->date }}</td>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $job->lot?->item?->code ?? '-' }}
                                        </div>
                                        @if ($job->lot)
                                            <span class="badge-soft bg-light border text-muted">
                                                {{ $job->lot->code }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $totalBundles }} bundle /
                                        {{ number_format($totalQty, 2, ',', '.') }} pcs
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('production.qc.cutting.edit', $job) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            QC
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted small">
                                        Belum ada data QC Cutting.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($records instanceof \Illuminate\Pagination\AbstractPaginator)
                    <div class="mt-2">
                        {{ $records->links() }}
                    </div>
                @endif
            @elseif ($stage === 'sewing')
                <h2 class="h6 mb-2">Daftar QC Sewing</h2>
                <p class="small text-muted mb-0">
                    Struktur QC Sewing akan mengikuti pola yang sama seperti Cutting.
                    Tinggal ganti query di controller ke model <code>SewingJob</code>.
                </p>
            @elseif ($stage === 'packing')
                <h2 class="h6 mb-2">Daftar QC Packing</h2>
                <p class="small text-muted mb-0">
                    Struktur QC Packing akan mengikuti pola yang sama seperti Cutting.
                    Tinggal ganti query di controller ke model <code>PackingJob</code>.
                </p>
            @endif
        </div>

    </div>
@endsection
