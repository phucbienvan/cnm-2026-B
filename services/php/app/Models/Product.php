<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'price', 'description', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
