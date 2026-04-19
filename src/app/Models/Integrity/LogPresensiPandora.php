<?php

namespace App\Models\Integrity;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class LogPresensiPandora extends Model
{
    protected $table = 'log_presensi_pandora';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'waktu' => 'datetime',
            'invalidated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Append-only: cegah UPDATE kecuali untuk soft-invalidation
        static::updating(function ($model) {
            $allowed = ['invalidated_at', 'invalidation_reason', 'invalidated_by', 'updated_at'];
            $dirty = array_keys($model->getDirty());
            $forbidden = array_diff($dirty, $allowed);
            if (!empty($forbidden)) {
                throw new RuntimeException(
                    'log_presensi_pandora is append-only. Cannot update: ' . implode(',', $forbidden)
                );
            }
        });

        // Cegah DELETE fisik
        static::deleting(function () {
            throw new RuntimeException('log_presensi_pandora is append-only. Use soft invalidation instead.');
        });
    }

    public function isValid(): bool
    {
        return $this->invalidated_at === null;
    }

    public function invalidate(string $reason, int $userId): void
    {
        $this->invalidated_at = now();
        $this->invalidation_reason = $reason;
        $this->invalidated_by = $userId;
        $this->save();
    }

    public function invalidatedBy()
    {
        return $this->belongsTo(User::class, 'invalidated_by');
    }
}
