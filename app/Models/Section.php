<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $fillable = ['title', 'section_number', 'file_path'];

    protected static function boot()
    {
        parent::boot();

        // You can add any logic you want to run before creating a section here, if needed
    }
}
