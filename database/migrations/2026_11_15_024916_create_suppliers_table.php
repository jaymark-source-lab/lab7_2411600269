<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // create the suppliers table
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');                                    // company name
            $table->string('contact_person')->nullable();
            $table->string('email')->unique();
            $table->string('phone', 30)->nullable();
            $table->string('address')->nullable();
            $table->string('payment_terms', 30)->default('Net 30');    // Net 30, COD, ...
            $table->unsignedSmallInteger('lead_time_days')->default(7);
            $table->decimal('minimum_order_amount', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();                                     // adds deleted_at

            $table->index(['is_active', 'name']);                      // active list sorted by name
        });
    }

    // undo
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};