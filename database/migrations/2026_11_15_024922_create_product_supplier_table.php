<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // which supplier sells which product, and at what price
    public function up(): void
    {
        Schema::create('product_supplier', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->decimal('unit_cost', 10, 2)->default(0);              // agreed buying price
            $table->unsignedInteger('minimum_order_quantity')->default(1);
            $table->boolean('is_preferred')->default(false);              // used by auto-reorder
            $table->timestamps();

            $table->unique(['product_id', 'supplier_id']);               // no duplicate pairs
        });
    }

    // undo
    public function down(): void
    {
        Schema::dropIfExists('product_supplier');
    }
};