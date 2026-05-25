<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 商户表
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_name');
            $table->string('contact_phone');
            $table->string('business_license')->nullable();
            $table->string('status')->default('pending')->index();
            $table->decimal('commission_rate', 5, 4)->default(0.0500);
            $table->timestamps();
            $table->softDeletes();
        });

        // 店铺表
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->string('phone')->nullable();
            $table->string('status')->default('active')->index();
            $table->json('business_hours')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // 商品分类表
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->string('name');
            $table->string('icon')->nullable();
            $table->integer('sort')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 商品表
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->foreignId('shop_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('price')->default(0);
            $table->string('status')->default('on_sale')->index();
            $table->integer('sort')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // SKU 表
        Schema::create('skus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('image')->nullable();
            $table->unsignedInteger('price')->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->json('specs')->nullable();
            $table->string('status')->default('on_sale')->index();
            $table->timestamps();
        });

        // 客户表
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nickname')->nullable();
            $table->string('avatar')->nullable();
            $table->string('phone')->nullable();
            $table->string('gender')->nullable();
            $table->date('birthday')->nullable();
            $table->timestamps();
        });

        // 收货地址表
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->string('contact_name');
            $table->string('contact_phone');
            $table->string('province');
            $table->string('city');
            $table->string('district');
            $table->string('address');
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // 订单表
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->unique();
            $table->string('type')->default('normal');
            $table->string('status')->default('pending')->index();
            $table->foreignId('merchant_id')->constrained();
            $table->foreignId('shop_id')->constrained();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('address_id')->nullable()->constrained();
            $table->unsignedInteger('total_amount')->default(0);
            $table->unsignedInteger('pay_amount')->default(0);
            $table->unsignedInteger('discount_amount')->default(0);
            $table->unsignedInteger('delivery_fee')->default(0);
            $table->string('remark')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'status']);
            $table->index(['merchant_id', 'status']);
            $table->index('created_at');
        });

        // 订单明细表
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained();
            $table->foreignId('sku_id')->nullable()->constrained();
            $table->string('product_name');
            $table->string('sku_name')->nullable();
            $table->string('image')->nullable();
            $table->unsignedInteger('price')->default(0);
            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedInteger('total_amount')->default(0);
            $table->timestamps();
        });

        // 支付表
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_no')->unique();
            $table->foreignId('order_id')->constrained();
            $table->string('order_no')->index();
            $table->foreignId('merchant_id')->constrained();
            $table->foreignId('customer_id')->constrained();
            $table->string('channel');
            $table->unsignedInteger('amount')->default(0);
            $table->string('status')->default('pending')->index();
            $table->string('transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();
        });

        // 配送表
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('delivery_no')->unique();
            $table->foreignId('order_id')->constrained();
            $table->string('order_no')->index();
            $table->foreignId('merchant_id')->constrained();
            $table->foreignId('shop_id')->constrained();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('address_id')->nullable()->constrained();
            $table->unsignedInteger('rider_id')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamp('pickup_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('fail_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('skus');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('shops');
        Schema::dropIfExists('merchants');
    }
};
