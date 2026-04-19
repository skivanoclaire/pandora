<?php

namespace App\Models\Staging;

use Illuminate\Database\Eloquent\Model;

class SyncPresentDevice extends Model
{
    protected $table = 'sync_present_device';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'cdate' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }
}
