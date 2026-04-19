<?php

namespace App\Models\Staging;

use Illuminate\Database\Eloquent\Model;

class SyncPresentMapsLogs extends Model
{
    protected $table = 'sync_present_maps_logs';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'cdate' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }
}
