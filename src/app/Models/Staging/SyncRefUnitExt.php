<?php

namespace App\Models\Staging;

use Illuminate\Database\Eloquent\Model;

class SyncRefUnitExt extends Model
{
    protected $table = 'sync_ref_unit_ext';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
        ];
    }
}
