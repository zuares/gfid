@extends('layouts.app')

@section('title', 'Tambah Piece Rate')

@push('head')
    <style>
        .page-wrap {
            max-width: 720px;
            margin-inline: auto;
            padding: .9rem .75rem 4rem;
        }

        body[data-theme="light"] .page-wrap {
            background: radial-gradient(circle at top left,
                    rgba(59, 130, 246, 0.16) 0,
                    rgba(34, 197, 94, 0.10) 30%,
                    #f9fafb 60%);
        }

        .card {
            background: var(--card);
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.3);
            box-shadow:
                0 18px 45px rgba(15, 23, 42, 0.12),
                0 0 0 1px rgba(15, 23, 42, 0.02);
        }

        .help-text {
            font-size: .8rem;
            color: var(--muted);
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        <a href="{{ route('payroll.piece_rates.index') }}" class="btn btn-link px-0 small mb-2">
            ← Kembali ke Master Piece Rate
        </a>

        <div class="card">
            <div class="card-body p-3 p-md-4">
                <h1 class="h6 mb-1">Tambah Piece Rate</h1>
                <p class="help-text mb-3">
                    Atur tarif borongan untuk operator per module, kategori, atau item tertentu.
                </p>

                @if ($errors->any())
                    <div class="alert alert-danger py-2 small">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('payroll.piece_rates.store') }}" method="POST">
                    @include('payroll.piece_rates._form', ['rate' => $rate])
                </form>
            </div>
        </div>
    </div>
@endsection
