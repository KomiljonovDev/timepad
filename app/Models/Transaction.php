<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

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

    public static function boot()
    {
        parent::boot();

        static::created(function ($transaction) {
            foreach ([10, 25, 50, 100] as $page) {
                Cache::forget("user_work_summary_page_$page");
                Cache::forget("user_work_detail_page_$page");
            }
        });
    }

}
