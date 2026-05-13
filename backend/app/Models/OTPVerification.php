<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OTPVerification extends Model
{
    protected $table = 'otp_verifications';

    protected $fillable = [
        'email',
        'code',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
