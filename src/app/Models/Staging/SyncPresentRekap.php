<?php

namespace App\Models\Staging;

use Illuminate\Database\Eloquent\Model;

class SyncPresentRekap extends Model
{
    protected $table = 'sync_present_rekap';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'tw' => 'boolean',
            'mkttw' => 'boolean',
            'pktw' => 'boolean',
            'plc' => 'boolean',
            'tk' => 'boolean',
            'ta' => 'boolean',
            'i' => 'boolean',
            's' => 'boolean',
            'c' => 'boolean',
            'dl' => 'boolean',
            'dsp' => 'boolean',
            'll' => 'boolean',
            'cdate' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    public function pegawai()
    {
        return $this->belongsTo(SyncPegPegawai::class, 'id_pegawai', 'id_pegawai');
    }
}
