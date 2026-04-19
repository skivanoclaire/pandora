<?php

namespace App\Models\Staging;

use Illuminate\Database\Eloquent\Model;

class SyncPresentSikaraLog extends Model
{
    protected $table = 'sync_present_sikara_log';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'cdate' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }
}
