<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('category')->default('Umum')->after('title');
            $table->decimal('price', 12, 2)->default(0)->after('content');
            $table->string('stock_type')->default('none')->after('price');
            $table->boolean('is_active')->default(true)->after('stock_type');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['category', 'price', 'stock_type', 'is_active']);
        });
    }
};
