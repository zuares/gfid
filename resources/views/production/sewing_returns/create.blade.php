@extends('layouts.app')

@section('title', 'Produksi • Sewing Return ' . $pickup->code)

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
            padding: .15rem .5rem;
            font-size: .7rem;
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
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <h1 class="h5 mb-1">Sewing Return: {{ $pickup->code }}</h1>
                    <div class="help">
                        Gudang Sewing:
                        {{ $pickup->warehouse?->code ?? '-' }} —
                        {{ $pickup->warehouse?->name ?? '-' }}
                    </div>
                    <div class="help">
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
                    <a href="{{ route('production.sewing_pickups.show', $pickup) }}"
                        class="btn btn-sm btn-outline-secondary">
                        Kembali ke Pickup
                    </a>
                </div>
            </div>
        </div>

        <form action="{{ route('production.sewing_returns.store') }}" method="post">
            @csrf

            <input type="hidden" name="pickup_id" value="{{ $pickup->id }}">

            {{-- HEADER RETURN --}}
            <div class="card p-3 mb-3">
                <div class="row g-3">
                    <div class="col-md-3 col-6">
                        <div class="help mb-1">Tanggal Return</div>
                        <input type="date" name="date" class="form-control @error('date') is-invalid @enderror"
                            value="{{ old('date', now()->format('Y-m-d')) }}">
                        @error('date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-9 col-12">
                        <div class="help mb-1">Catatan</div>
                        <input type="text" name="notes" class="form-control @error('notes') is-invalid @enderror"
                            value="{{ old('notes') }}">
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- TABEL HASIL JAHIT --}}
            <div class="card p-3 mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 mb-0">Input Hasil Jahit per Bundle</h2>
                    <div class="help">
                        Isi Qty OK dan/atau Reject. Sistem akan batasi supaya tidak melebihi sisa.
                    </div>
                </div>

                @error('results')
                    <div class="alert alert-danger py-1 small mb-2">
                        {{ $message }}
                    </div>
                @enderror

                <div class="table-wrap">
                    <table class="table table-sm align-middle mono">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="width: 150px;">Bundle Code</th>
                                <th style="width: 160px;">Item Jadi</th>
                                <th style="width: 200px;">Lot</th>
                                <th style="width: 110px;">Qty Pickup</th>
                                <th style="width: 130px;">Sudah Return</th>
                                <th style="width: 110px;">Qty OK</th>
                                <th style="width: 110px;">Qty Reject</th>
                                <th style="width: 180px;">Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $oldResults = old('results', []);
                            @endphp

                            @forelse ($lines as $i => $line)
                                @php
                                    $bundle = $line->bundle;
                                    $lot = $bundle?->cuttingJob?->lot;

                                    $oldRow = $oldResults[$i] ?? null;

                                    $qtyPickup = (float) $line->qty_bundle;
                                    $returnedOk = (float) ($line->qty_returned_ok ?? 0);
                                    $returnedReject = (float) ($line->qty_returned_reject ?? 0);
                                    $remaining = $qtyPickup - ($returnedOk + $returnedReject);
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>

                                    <input type="hidden" name="results[{{ $i }}][line_id]"
                                        value="{{ $line->id }}">

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

                                    <td>{{ number_format($qtyPickup, 2, ',', '.') }}</td>

                                    <td>
                                        OK: {{ number_format($returnedOk, 2, ',', '.') }} /
                                        Rj: {{ number_format($returnedReject, 2, ',', '.') }}
                                        <div class="small text-muted">
                                            Sisa: {{ number_format($remaining, 2, ',', '.') }}
                                        </div>
                                    </td>

                                    <td>
                                        <input type="number" step="0.01" min="0"
                                            name="results[{{ $i }}][qty_ok]"
                                            class="form-control form-control-sm @error("results.$i.qty_ok") is-invalid @enderror"
                                            value="{{ $oldRow['qty_ok'] ?? '0' }}">
                                        @error("results.$i.qty_ok")
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    <td>
                                        <input type="number" step="0.01" min="0"
                                            name="results[{{ $i }}][qty_reject]"
                                            class="form-control form-control-sm @error("results.$i.qty_reject") is-invalid @enderror"
                                            value="{{ $oldRow['qty_reject'] ?? '0' }}">
                                        @error("results.$i.qty_reject")
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>

                                    <td>
                                        <input type="text" name="results[{{ $i }}][notes]"
                                            class="form-control form-control-sm @error("results.$i.notes") is-invalid @enderror"
                                            value="{{ $oldRow['notes'] ?? '' }}">
                                        @error("results.$i.notes")
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted small">
                                        Tidak ada bundle in_progress pada pickup ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- SUBMIT --}}
            <div class="d-flex justify-content-between align-items-center mb-5">
                <a href="{{ route('production.sewing_pickups.show', $pickup) }}" class="btn btn-sm btn-outline-secondary">
                    Batal
                </a>

                <button type="submit" class="btn btn-sm btn-primary">
                    Simpan Sewing Return
                </button>
            </div>
        </form>
    </div>
@endsection
