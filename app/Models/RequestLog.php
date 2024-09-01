<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ip_address',
        'user_agent',
        'request_count',
        'last_request_at',
    ];
}
