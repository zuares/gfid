<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /* ==========================
     *  RELATIONSHIPS
     * ==========================
     */

    /**
     * Satu kategori memiliki banyak item.
     */
    public function items()
    {
        return $this->hasMany(Item::class);
    }

    /* ==========================
     *  SCOPE & HELPER
     * ==========================
     */

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function isActive(): bool
    {
        return $this->active === true;
    }
}
