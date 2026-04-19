<?php

namespace App\Models\Staging;

use Illuminate\Database\Eloquent\Model;

class SyncPegPegawai extends Model
{
    protected $table = 'sync_peg_pegawai';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'bebas_lokasi' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }

    public function unit()
    {
        return $this->belongsTo(SyncRefUnit::class, 'id_unit', 'id_unit');
    }
}
