@extends('layouts.app')

@section('title', 'Purchase Order Baru')

@section('content')
    <div class="container py-3">
        <h1 class="mb-3">Purchase Order Baru</h1>

        <form action="{{ route('purchasing.purchase_orders.store') }}" method="POST">
            @csrf

            @include('purchasing.purchase_orders._form')

            <div class="mt-3 d-flex justify-content-between">
                <a href="{{ route('purchasing.purchase_orders.index') }}" class="btn btn-outline-secondary">
                    &larr; Kembali
                </a>
                <button type="submit" class="btn btn-primary">
                    Simpan PO
                </button>
            </div>
        </form>
    </div>
@endsection
