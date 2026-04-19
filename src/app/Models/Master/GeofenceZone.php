<?php

namespace App\Models\Master;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class GeofenceZone extends Model
{
    protected $table = 'geofence_zones';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    public function rules()
    {
        return $this->hasMany(GeofenceRule::class, 'geofence_zone_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
