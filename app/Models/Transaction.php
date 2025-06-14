<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'time',
        'device_id',
        'server_received_timestamp'
    ];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }
}
