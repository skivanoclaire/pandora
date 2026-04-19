<?php

namespace App\Models\Staging;

use Illuminate\Database\Eloquent\Model;

class SyncPresentIjin extends Model
{
    protected $table = 'sync_present_ijin';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'cdate' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }
}
