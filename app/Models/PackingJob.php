<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackingJob extends Model
{
    protected $table = 'packing_jobs';

    protected $fillable = [
        'code',
        'date',
        'status',
        'posted_at',
        'unposted_at',
        'channel',
        'reference',
        'notes',
        'warehouse_from_id',
        'warehouse_to_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'posted_at' => 'datetime',
        'unposted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
     */

    /**
     * Detail baris packing (item + qty).
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PackingJobLine::class);
    }

    /**
     * Gudang asal, misal FG.
     */
    public function warehouseFrom(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_from_id');
    }

    /**
     * Gudang tujuan, misal PCK.
     */
    public function warehouseTo(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_to_id');
    }

    /**
     * User yang membuat dokumen.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * User yang terakhir mengupdate dokumen.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS & SCOPES
    |--------------------------------------------------------------------------
     */

    /**
     * Cek apakah sudah posted.
     */
    public function getIsPostedAttribute(): bool
    {
        return $this->status === 'posted';
    }

    public function getTotalQtyPackedAttribute(): float
    {
        return (float) $this->lines()->sum('qty_packed');
    }

    /**
     * Scope hanya draft.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope hanya posted.
     */
    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }
}
