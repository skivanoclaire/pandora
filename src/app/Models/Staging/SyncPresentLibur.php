<?php

namespace App\Models\Staging;

use Illuminate\Database\Eloquent\Model;

class SyncPresentLibur extends Model
{
    protected $table = 'sync_present_libur';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'synced_at' => 'datetime',
        ];
    }
}
