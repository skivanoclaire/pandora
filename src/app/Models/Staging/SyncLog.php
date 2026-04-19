<?php

namespace App\Models\Staging;

use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    protected $table = 'sync_log';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function markSuccess(int $fetched, int $inserted, int $updated): void
    {
        $this->update([
            'finished_at' => now(),
            'rows_fetched' => $fetched,
            'rows_inserted' => $inserted,
            'rows_updated' => $updated,
            'status' => 'success',
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'finished_at' => now(),
            'status' => 'failed',
            'error_message' => $error,
        ]);
    }
}
