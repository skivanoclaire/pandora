<?php

namespace App\Models\Analytics;

use App\Models\Staging\SyncPegPegawai;
use Illuminate\Database\Eloquent\Model;

class FeatureKehadiranHarian extends Model
{
    protected $table = 'features_kehadiran_harian';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'aplikasi_fake_gps_terdeteksi' => 'boolean',
            'status_sikara_tw' => 'boolean',
            'status_sikara_mkttw' => 'boolean',
            'status_sikara_pktw' => 'boolean',
            'status_sikara_plc' => 'boolean',
            'status_sikara_tk' => 'boolean',
            'status_sikara_ta' => 'boolean',
            'status_data_final' => 'boolean',
        ];
    }

    public function pegawai()
    {
        return $this->belongsTo(SyncPegPegawai::class, 'id_pegawai', 'id_pegawai');
    }
}
