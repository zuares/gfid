<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionCostPeriod extends Model
{
    protected $fillable = [
        'code',
        'name',
        'date_from',
        'date_to',
        'snapshot_date',
        'cutting_payroll_period_id',
        'sewing_payroll_period_id',
        'finishing_payroll_period_id',
        'status',
        'is_active',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'snapshot_date' => 'date',
        'is_active' => 'boolean',
    ];

    // 🔗 Relasi ke periode payroll
    public function cuttingPayrollPeriod()
    {
        return $this->belongsTo(
            \App\Models\PieceworkPayrollPeriod::class,
            'cutting_payroll_period_id'
        );
    }

    public function sewingPayrollPeriod()
    {
        return $this->belongsTo(
            \App\Models\PieceworkPayrollPeriod::class,
            'sewing_payroll_period_id'
        );
    }

    public function finishingPayrollPeriod()
    {
        return $this->belongsTo(
            \App\Models\PieceworkPayrollPeriod::class,
            'finishing_payroll_period_id'
        );
    }
}
