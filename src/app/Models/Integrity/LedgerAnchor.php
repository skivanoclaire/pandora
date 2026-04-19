<?php

namespace App\Models\Integrity;

use Illuminate\Database\Eloquent\Model;

class LedgerAnchor extends Model
{
    protected $table = 'ledger_anchor';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'anchored_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAnchored($query)
    {
        return $query->where('status', 'anchored')->whereNull('confirmed_at');
    }
}
