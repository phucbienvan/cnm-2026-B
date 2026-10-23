<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm cột user_id vào bảng products để xác định chủ sở hữu của sản phẩm.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Thêm khóa ngoại user_id: liên kết sản phẩm với người tạo ra nó
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Xóa cột user_id khi rollback migration.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
