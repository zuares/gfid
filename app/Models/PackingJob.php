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
     * Gudang asal.
     * Saat ini dipakai sebagai gudang produksi (misal: WH-PRD).
     */
    public function warehouseFrom(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_from_id');
    }

    /**
     * Gudang tujuan.
     * Bisa dipakai nanti kalau kamu mau pindahkan ke WH-RTS / gudang lain.
     */
    public function warehouseTo(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_to_id');
    }

    /**
     * User yang membuat dokumen.
     * Nama relasi: createdBy → untuk konsisten dengan modul lain (Cutting, Finishing, dll).
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Alias lama (kalau ada yang terlanjur pakai $job->creator).
     */
    public function creator(): BelongsTo
    {
        return $this->createdBy();
    }

    /**
     * User yang terakhir mengupdate dokumen.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Alias lama (kalau ada yang pakai $job->updater).
     */
    public function updater(): BelongsTo
    {
        return $this->updatedBy();
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

    /**
     * Total qty_packed (sum semua line).
     */
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
