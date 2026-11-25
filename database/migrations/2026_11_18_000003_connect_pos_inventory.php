<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE ingredients MODIFY stock DECIMAL(18,6) NOT NULL DEFAULT 0, MODIFY max_stock DECIMAL(18,6) NOT NULL DEFAULT 0, MODIFY minimum_stock DECIMAL(18,6) NOT NULL DEFAULT 0');
        }
        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedInteger('stock')->default(0);
            $table->string('photo_path')->nullable();
        });
        Schema::create('product_recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 18, 6);
            $table->string('unit', 20);
            $table->unique(['post_id', 'ingredient_id']);
        });
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_id')->unique();
            $table->string('payload_hash', 64);
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('business_date');
            $table->string('order_type');
            $table->string('payment_method');
            $table->decimal('subtotal', 18, 2);
            $table->decimal('tax_percent', 5, 2);
            $table->decimal('tax', 18, 2);
            $table->decimal('total', 18, 2);
            $table->timestamp('paid_at');
            $table->timestamps();
        });
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 18, 2);
            $table->decimal('total', 18, 2);
        });
        Schema::create('stock_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('invoice', 100);
            $table->date('purchased_on');
            $table->decimal('total_cost', 18, 2);
            $table->timestamps();
            $table->unique(['supplier_id', 'invoice']);
        });
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('post_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('sale_item_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('stock_purchase_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('item_name');
            $table->string('kind');
            $table->string('unit', 20);
            $table->decimal('quantity', 18, 6);
            $table->decimal('balance_before', 18, 6);
            $table->decimal('balance_after', 18, 6);
            $table->string('note')->nullable();
            $table->timestamp('created_at');
        });
        Schema::create('pos_settings', function (Blueprint $table) {
            $table->id();
            $table->string('store_name')->default('Warkop Cak Kebo');
            $table->string('store_address')->default('');
            $table->string('receipt_footer')->default('Terima kasih sudah mampir!');
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->boolean('low_stock_alert')->default(true);
            $table->boolean('auto_print')->default(false);
        });
        DB::table('pos_settings')->insert(['id' => 1]);
        Schema::create('owner_web_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('token_hash', 64)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('target');
            $table->timestamp('expires_at');
        });
    }

    public function down(): void
    {
        foreach (['owner_web_tickets', 'pos_settings', 'inventory_movements', 'stock_purchases', 'sale_items', 'sales', 'product_recipes'] as $table) Schema::dropIfExists($table);
        Schema::table('posts', fn (Blueprint $table) => $table->dropColumn(['stock', 'photo_path']));
        // Keep ingredient precision: rollback must not round existing quantities.
    }
};
