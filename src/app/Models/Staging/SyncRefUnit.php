<?php

namespace App\Models\Staging;

use Illuminate\Database\Eloquent\Model;

class SyncRefUnit extends Model
{
    protected $table = 'sync_ref_unit';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
        ];
    }

    public function pegawai()
    {
        return $this->hasMany(SyncPegPegawai::class, 'id_unit', 'id_unit');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id', 'id_unit');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id', 'id_unit');
    }

    public function lokasiUnit()
    {
        return $this->hasManyThrough(
            SyncRefLokasiUnit::class,
            SyncRefBantuUnit::class,
            'id_unit',
            'id_lokasi',
            'id_unit',
            'id_lokasi'
        );
    }
}
