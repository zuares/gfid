<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($search = $request->input('q')) {
            $query->where(function ($q2) use ($search) {
                $q2->where('name', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%');
            });
        }

        $customers = $query
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('master.customers.index', compact('customers'));
    }

    public function create()
    {
        $customer = new Customer();
        return view('master.customers.create', compact('customer'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['created_by'] = Auth::id();

        Customer::create($data);

        return redirect()
            ->route('master.customers.index')
            ->with('status', 'Customer berhasil dibuat.');
    }

    public function edit(Customer $customer)
    {
        return view('master.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['updated_by'] = Auth::id();

        $customer->update($data);

        return redirect()
            ->route('master.customers.index')
            ->with('status', 'Customer berhasil diupdate.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()
            ->route('master.customers.index')
            ->with('status', 'Customer berhasil dihapus.');
    }
}
