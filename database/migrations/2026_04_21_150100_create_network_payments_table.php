<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_subscriber_id')->constrained('network_subscribers')->cascadeOnDelete();
            $table->date('period_month');
            $table->decimal('amount', 10, 2);
            $table->date('paid_at');
            $table->string('payment_method')->default('cash');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['period_month', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_payments');
    }
};
