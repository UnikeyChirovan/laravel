<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'webname', 'address', 'phone', 'email', 'facebook', 'twitter', 'linkedin',
    ];
}
