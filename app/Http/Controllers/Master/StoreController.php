<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index()
    {
        $stores = Store::orderBy('code')->paginate(25);

        return view('master.stores.index', compact('stores'));
    }

    public function create()
    {
        return view('master.stores.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:stores,code',
            'name' => 'required|string|max:255',
            'channel' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        Store::create($data);

        return redirect()
            ->route('master.stores.index')
            ->with('success', 'Store berhasil ditambahkan.');
    }

    public function edit(Store $store)
    {
        return view('master.stores.edit', compact('store'));
    }

    public function update(Request $request, Store $store)
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:stores,code,' . $store->id,
            'name' => 'required|string|max:255',
            'channel' => 'nullable|string|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $store->update($data);

        return redirect()
            ->route('master.stores.index')
            ->with('success', 'Store berhasil diupdate.');
    }

    public function destroy(Store $store)
    {
        $store->delete();

        return redirect()
            ->route('master.stores.index')
            ->with('success', 'Store berhasil dihapus.');
    }
}
