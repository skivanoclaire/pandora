<?php

namespace App\Models\Staging;

use Illuminate\Database\Eloquent\Model;

class SyncDataChange extends Model
{
    protected $table = 'sync_data_changes';

    protected $guarded = [];

    protected $casts = [
        'changed_fields' => 'array',
    ];
}
