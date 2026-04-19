<?php

namespace App\Models\Staging;

use Illuminate\Database\Eloquent\Model;

class SyncRefLokasiUnit extends Model
{
    protected $table = 'sync_ref_lokasi_unit';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }
}
