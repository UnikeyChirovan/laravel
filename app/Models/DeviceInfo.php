<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent', // Remove 'reason' from fillable fields
    ];

     public function user()
    {
        return $this->belongsTo(User::class);
    }


}
