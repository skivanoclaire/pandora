<?php

namespace App\Models\Staging;

use Illuminate\Database\Eloquent\Model;

class SyncPresentGroup extends Model
{
    protected $table = 'sync_present_group';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'berlaku' => 'date',
            'berakhir' => 'date',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * Ambil jam kerja untuk hari tertentu (0=Minggu..6=Sabtu).
     */
    public function jamUntukHari(int $dayOfWeek): ?array
    {
        $map = [
            0 => 'min', 1 => 'sen', 2 => 'sel', 3 => 'rab',
            4 => 'kam', 5 => 'jum', 6 => 'sab',
        ];

        $prefix = $map[$dayOfWeek] ?? null;
        if (!$prefix) {
            return null;
        }

        $awal = $this->{$prefix . '_awal'};
        $akhir = $this->{$prefix . '_akhir'};

        if ($awal === null) {
            return null; // Hari ini tidak dijadwalkan kerja
        }

        return ['jam_masuk' => $awal, 'jam_pulang' => $akhir];
    }
}
