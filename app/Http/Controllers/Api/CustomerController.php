<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

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

        $query->orderBy('name');

        $customers = $query->limit(20)->get([
            'id',
            'code',
            'name',
            'phone',
        ]);

        return response()->json([
            'data' => $customers->map(function ($c) {
                return [
                    'id' => $c->id,
                    'label' => trim(($c->code ? $c->code . ' - ' : '') . $c->name . ($c->phone ? ' (' . $c->phone . ')' : '')),
                ];
            }),
        ]);
    }
}
