<?php

namespace App\Models\Staging;

use Illuminate\Database\Eloquent\Model;

class SyncFakeGps extends Model
{
    protected $table = 'sync_fake_gps';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
        ];
    }
}
