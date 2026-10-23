<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use SoftDeletes;

    // Thêm user_id để lưu thông tin người tạo sản phẩm
    protected $fillable = ['name', 'price', 'description', 'user_id'];

    /**
     * Một sản phẩm thuộc về một người dùng (chủ sở hữu).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
