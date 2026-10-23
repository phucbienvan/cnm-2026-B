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

    /**
     * Check if product belongs to the given user.
     *
     * @param \App\Models\User|int|string $user
     * @return bool
     */
    public function isOwnedBy($user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;

        return (int) $this->user_id === (int) $userId;
    }
}
