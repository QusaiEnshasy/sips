<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_card_sales', function (Blueprint $table) {
            $table->id();
            $table->date('sale_date');
            $table->string('card_name');
            $table->unsignedInteger('cards_count');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_method')->default('cash');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('sale_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_card_sales');
    }
};
