<?php

namespace App\Models\Staging;

use Illuminate\Database\Eloquent\Model;

class SyncPresentPresensi extends Model
{
    protected $table = 'sync_present_presensi';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'cdate' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }

    public function group()
    {
        return $this->belongsTo(SyncPresentGroup::class, 'id_group', 'id_group');
    }
}
