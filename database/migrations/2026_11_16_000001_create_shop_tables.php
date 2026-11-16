<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The shop.
 *
 * A product is the thing described on the catalogue page — "LECA 4–10 mm".
 * What a customer actually pays for is one way of buying it: a 50-litre bag,
 * a cubic metre loose. Those are `product_variants`, each with its own unit,
 * price and minimum. A product with no active variant simply is not for sale
 * and keeps its quote button.
 *
 * Money is whole tomans in unsigned big integers. Never floats: a price that is
 * off by one rial after a multiplication is an invoice that does not add up.
 * Gateways that want rials get `× 10` at the edge.
 *
 * An order keeps a copy of everything it was sold with — names, unit, price —
 * because the catalogue moves on and an invoice must not.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->json('name');   // "کیسه ۵۰ لیتری" / "50-litre bag"
            $table->json('unit');   // "کیسه" / "bag"
            $table->string('sku', 40)->nullable()->unique();

            $table->unsignedBigInteger('price');                 // tomans, per unit
            $table->unsignedInteger('min_quantity')->default(1);
            $table->unsignedInteger('max_quantity')->nullable();
            $table->unsignedInteger('step')->default(1);
            // Null is "made to order": no ceiling. A number is counted down
            // as orders are placed and back up when one is cancelled.
            $table->unsignedInteger('stock')->nullable();

            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['product_id', 'is_active', 'position']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // What the customer reads out on the phone: short, no lookalike
            // characters. The token is what the order page is reached by.
            $table->string('number', 20)->unique();
            $table->string('token', 64)->unique();

            $table->string('status', 24)->default('pending');
            $table->string('payment_method', 24);
            $table->string('shipping_method', 24);

            $table->string('customer_name', 120);
            $table->string('mobile', 20);
            $table->string('email', 180)->nullable();
            $table->string('company', 160)->nullable();
            // For an official invoice (فاکتور رسمی): national id of a person
            // or of a company, and the economic code. Both optional.
            $table->string('national_id', 20)->nullable();
            $table->string('economic_code', 20)->nullable();

            $table->string('province', 60);
            $table->string('city', 80);
            $table->string('address', 500);
            $table->string('postal_code', 10)->nullable();
            $table->string('notes', 1000)->nullable();

            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('tax')->default(0);
            $table->unsignedTinyInteger('tax_percent')->default(0);
            $table->unsignedBigInteger('shipping')->default(0);
            $table->unsignedBigInteger('total');

            $table->string('locale', 8);
            $table->string('ip_hash', 64)->nullable();
            $table->text('staff_note')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('mobile');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            // Nullable and not cascading: deleting a product must not delete
            // the line from an order that already sold it.
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();

            // Every language, as it read on the day.
            $table->json('product_name');
            $table->json('variant_name');
            $table->json('unit');

            $table->unsignedBigInteger('unit_price');
            $table->unsignedInteger('quantity');
            $table->unsignedBigInteger('line_total');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 24);
            $table->unsignedBigInteger('amount');                // tomans

            $table->string('status', 16)->default('pending');   // pending | paid | failed
            $table->string('authority', 80)->nullable()->unique();
            $table->string('reference', 80)->nullable();        // the bank's ref id
            $table->string('card_pan', 32)->nullable();         // masked, as the gateway sends it
            $table->string('failure', 255)->nullable();

            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('product_variants');
    }
};
