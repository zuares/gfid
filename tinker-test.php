<?php
use App\Services\Payroll\CuttingPayrollGenerator;

$test = CuttingPayrollGenerator::generate('2025-11-01', '2025-11-30', 1);

PieceRate::create([
    'module' => 'sewing',
    'employee_id' => 1, // ganti ID operator
    'item_id' => 6, // atau null + pakai item_category_id
    'item_category_id' => null, // atau isi kalau mau by kategori
    'rate_per_pcs' => 500, // tarif per pcs
    'effective_from' => '2025-11-01',
    'effective_to' => null,
]);
