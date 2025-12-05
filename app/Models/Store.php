<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'channel_id',
        'is_active',
        // 'code' boleh tidak diisi manual, karena kita auto generate
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (Store $store) {
            // Pastikan channel_id sudah diisi sebelum create
            $channel = Channel::find($store->channel_id);

            $channelCode = $channel?->code ?? 'GEN';

            // Slug dari nama, contoh: "Offline Store Utama" → "OFFLINE-STORE-UTAMA"
            $nameSlug = Str::slug($store->name, '-');

            // Code final → SHP-OFFLINE-STORE-UTAMA
            $store->code = strtoupper($channelCode . '-' . $nameSlug);
        });
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
