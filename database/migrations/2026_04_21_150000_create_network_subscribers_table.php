<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('subscriber_code')->nullable()->unique();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('location');
            $table->string('installation_address')->nullable();
            $table->string('subscription_type')->default('broadband');
            $table->string('ip_address')->nullable()->unique();
            $table->string('service_speed')->nullable();
            $table->string('router_model')->nullable();
            $table->decimal('monthly_fee', 10, 2)->default(0);
            $table->unsignedTinyInteger('due_day')->default(1);
            $table->date('activation_date')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'subscription_type']);
            $table->index('location');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_subscribers');
    }
};
