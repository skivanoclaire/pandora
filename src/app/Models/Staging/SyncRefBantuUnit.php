<?php

namespace App\Models\Staging;

use Illuminate\Database\Eloquent\Model;

class SyncRefBantuUnit extends Model
{
    protected $table = 'sync_ref_bantu_unit';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
        ];
    }
}
