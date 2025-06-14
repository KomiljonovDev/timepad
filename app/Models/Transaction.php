<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'time',
        'device_id',
    ];

    public function user() {
        $this->belongsTo(User::class);
    }
}
