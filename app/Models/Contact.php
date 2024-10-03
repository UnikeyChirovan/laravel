<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'username',
        'name',
        'email',
        'title',
        'message',
        'contacted_at', // Thêm vào đây
    ];
}
