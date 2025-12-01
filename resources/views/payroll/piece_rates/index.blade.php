@extends('layouts.app')

@section('title', 'Master Piece Rate')

@push('head')
    <style>
        .page-wrap {
            max-width: 1100px;
            margin-inline: auto;
            padding: .7rem .75rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(14, 165, 233, 0.14) 0,
                    rgba(16, 185, 129, 0.10) 25%,
                    #f9fafb 60%);
        }

        .card {
            background: var(--card);
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.25);
            box-shadow:
                0 12px 32px rgba(15, 23, 42, 0.08),
                0 0 0 1px rgba(15, 23, 42, 0.02);
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas;
        }

        .help-text {
            font-size: .8rem;
            color: var(--muted);
        }

        @media (max-width: 767.98px) {
            .page-wrap {
                padding-inline: .6rem;
            }

            .table-responsive {
                font-size: .86rem;
            }
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h1 class="h5 mb-1">Master Piece Rate</h1>
                <p class="help-text mb-0">
                    Atur tarif borongan per modul, operator, item, atau kategori.
                </p>
            </div>
            <div>
                <a href="{{ route('payroll.piece_rates.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i> Tambah Tarif
                </a>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success py-2 small">
                {{ session('status') }}
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger py-2 small">
                {{ session('error') }}
            </div>
        @endif

        {{-- FILTER --}}
        <div class="card mb-3">
            <div class="card-body py-2">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-6 col-md-3">
                        <label class="form-label mb-1 small">Module</label>
                        <select name="module" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @foreach ($modules as $key => $label)
                                <option value="{{ $key }}" {{ request('module') === $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label mb-1 small">Operator</label>
                        <select name="employee_id" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->id }}"
                                    {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                    {{ $emp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label mb-1 small">Kategori</label>
                        <select name="item_category_id" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @foreach ($categories as $cat)
                                <option value="{{ $cat->id }}"
                                    {{ request('item_category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label mb-1 small">Item</label>
                        <select name="item_id" class="form-select form-select-sm">
                            <option value="">Semua</option>
                            @foreach ($items as $it)
                                <option value="{{ $it->id }}" {{ request('item_id') == $it->id ? 'selected' : '' }}>
                                    {{ $it->code }} - {{ $it->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-12 col-md-3 mt-2">
                        <button class="btn btn-outline-secondary btn-sm w-100">
                            <i class="bi bi-funnel"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- TABLE RATES --}}
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Module</th>
                                <th>Operator</th>
                                <th>Kategori</th>
                                <th>Item</th>
                                <th class="text-end">Rate / pcs</th>
                                <th class="text-center">Periode Berlaku</th>
                                <th class="text-end"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rates as $rate)
                                <tr>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            {{ strtoupper($rate->module) }}
                                        </span>
                                    </td>
                                    <td>{{ $rate->employee->name ?? '-' }}</td>
                                    <td>{{ $rate->category->name ?? '-' }}</td>
                                    <td>
                                        @if ($rate->item)
                                            <span class="mono">{{ $rate->item->code }}</span>
                                            <span class="d-block small text-muted">
                                                {{ $rate->item->name }}
                                            </span>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end mono">
                                        {{ number_format($rate->rate_per_pcs, 2, ',', '.') }}
                                    </td>
                                    <td class="text-center small mono">
                                        {{ optional($rate->effective_from)->format('d/m/Y') }}
                                        &mdash;
                                        {{ $rate->effective_to ? $rate->effective_to->format('d/m/Y') : '∞' }}
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-1">
                                            <a href="{{ route('payroll.piece_rates.edit', $rate) }}"
                                                class="btn btn-outline-secondary btn-sm">
                                                Edit
                                            </a>
                                            <form action="{{ route('payroll.piece_rates.destroy', $rate) }}" method="POST"
                                                onsubmit="return confirm('Hapus tarif ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger btn-sm">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-3">
                                        Belum ada data tarif borongan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-2">
                    {{ $rates->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
