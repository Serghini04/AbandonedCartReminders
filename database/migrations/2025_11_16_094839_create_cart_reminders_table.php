<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_reminders', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('cart_id')->constrained()->onDelete('cascade');
            $table->integer('reminder_number');
            $table->timestamp('scheduled_at');
            $table->timestamp('sent_at')->nullable();
            $table->enum('status', ['pending', 'sent', 'cancelled'])->default('pending');
            $table->timestamps();
            $table->index(['cart_id', 'reminder_number']);
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_reminders');
    }
};
