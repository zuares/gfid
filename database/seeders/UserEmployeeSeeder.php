<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // ============================
        //  EMPLOYEE DATA
        // ============================
        $employees = [
            [
                'code' => 'OWN',
                'name' => 'Owner',
                'role' => 'owner', // ✅ masuk enum
                'active' => 1,
                'phone' => '081200000001',
                'address' => 'Alamat Owner',
            ],
            [
                'code' => 'NTA',
                'name' => 'Neng Nita',
                'role' => 'admin', // ✅ ganti dari "fullfilment" → "admin"
                'active' => 1,
                'phone' => '081200000002',
                'address' => 'Alamat Admin / Fulfillment',
            ],
            [
                'code' => 'ANG',
                'name' => 'Angga',
                'role' => 'operating', // ✅ ganti dari "production" → "operating"
                'active' => 1,
                'phone' => '081200000002',
                'address' => 'Alamat Angga (Gudang Produksi)',
            ],
            [
                'code' => 'MRF',
                'name' => 'Mang Arip',
                'role' => 'cutting', // ✅ enum
                'active' => 1,
                'phone' => '081200000003',
                'address' => 'Operator Cutting',
            ],
            [
                'code' => 'BBI',
                'name' => 'Bi rini',
                'role' => 'sewing', // ✅ enum
                'active' => 1,
                'phone' => '081200000004',
                'address' => 'Operator Sewing',
            ],
            [
                'code' => 'MYD',
                'name' => 'Mang Yadi',
                'role' => 'sewing',
                'active' => 1,
                'phone' => '081200000004',
                'address' => 'Operator Sewing',
            ],
            [
                'code' => 'RDN',
                'name' => 'Jang ridwan',
                'role' => 'sewing',
                'active' => 1,
                'phone' => '081200000004',
                'address' => 'Operator Sewing',
            ],
        ];

        foreach ($employees as $emp) {
            $employee = Employee::create($emp);

            // ============================
            //  TENTUKAN SIAPA YANG PUNYA LOGIN
            // ============================
            // Hanya owner + admin + operating yang dibuat user
            $loginRole = match ($employee->role) {
                'owner' => 'owner',
                'admin' => 'admin', // Nita → akses fulfillment / sales / packing
                'operating' => 'operating', // Angga → akses production / inventory
                default => null, // sewing, cutting, other → tidak dibuat user
            };

            if (!$loginRole) {
                continue; // skip bikin user
            }

            User::create([
                'employee_id' => $employee->id,
                'employee_code' => $employee->code,
                'name' => $employee->name,
                'role' => $loginRole, // pastikan schema users.role juga support ini
                'password' => Hash::make('123'), // default password
            ]);
        }
    }
}
