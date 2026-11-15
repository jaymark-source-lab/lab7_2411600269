<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // history of actions on any model (polymorphic)
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');                                 // subject_type + subject_id
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 30);                              // created, submitted, approved...
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    // undo
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};