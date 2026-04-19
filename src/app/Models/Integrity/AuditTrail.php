<?php

namespace App\Models\Integrity;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditTrail extends Model
{
    protected $table = 'audit_trail_pandora';
    protected $guarded = [];

    public $timestamps = false; // Hanya created_at, tanpa updated_at

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function catat(
        int $userId,
        string $aksi,
        ?string $entitas = null,
        ?int $entitasId = null,
        ?array $metadata = null,
    ): self {
        return self::create([
            'user_id' => $userId,
            'aksi' => $aksi,
            'entitas' => $entitas,
            'entitas_id' => $entitasId,
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
}
