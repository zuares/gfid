<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * 1 channel punya banyak store.
     */
    public function stores()
    {
        return $this->hasMany(Store::class);
    }

    /**
     * Scope: hanya channel yang aktif.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
