<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // purchase order header
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number', 30)->unique();          // PO-20261115-001
            $table->foreignId('supplier_id')->constrained();           // suppliers are soft-deleted, never removed
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'sent', 'received', 'cancelled'])->default('draft');
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->boolean('is_auto_generated')->default(false);      // created by the reorder command
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'order_date']);                   // list filtered by status and date
        });
    }

    // undo
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};