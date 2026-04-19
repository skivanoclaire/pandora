<?php

namespace App\Models\Master;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class GeofenceRule extends Model
{
    protected $table = 'geofence_rules';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'unit_kerja_ids' => 'array',
            'berlaku_mulai' => 'date',
            'berlaku_sampai' => 'date',
        ];
    }

    public function zone()
    {
        return $this->belongsTo(GeofenceZone::class, 'geofence_zone_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
