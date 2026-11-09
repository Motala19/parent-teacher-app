<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpCode extends Model
{
    protected $fillable = [
        'user_id', 'channel', 'destination', 'code_hash', 'attempts',
        'expires_at', 'verified_at', 'reset_token_hash', 'reset_expires_at', 'ip',
    ];

    protected $casts = [
        'expires_at'       => 'datetime',
        'verified_at'      => 'datetime',
        'reset_expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}