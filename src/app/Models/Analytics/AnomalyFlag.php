<?php

namespace App\Models\Analytics;

use App\Models\Staging\SyncPegPegawai;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AnomalyFlag extends Model
{
    protected $table = 'anomaly_flags';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'metadata' => 'array',
            'detected_at' => 'datetime',
            'direview_pada' => 'datetime',
        ];
    }

    public function pegawai()
    {
        return $this->belongsTo(SyncPegPegawai::class, 'id_pegawai', 'id_pegawai');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'direview_oleh');
    }

    public function scopeBelumDireview($query)
    {
        return $query->where('status_review', 'belum_direview');
    }

    public function scopeTingkat($query, int $tingkat)
    {
        return $query->where('tingkat', $tingkat);
    }
}
