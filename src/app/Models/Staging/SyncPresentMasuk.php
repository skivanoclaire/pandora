<?php

namespace App\Models\Staging;

use Illuminate\Database\Eloquent\Model;

class SyncPresentMasuk extends Model
{
    protected $table = 'sync_present_masuk';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'synced_at' => 'datetime',
        ];
    }
}
