<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('details')->nullable();
            $table->text('student_solution')->nullable();
            $table->enum('status', ['todo', 'progress', 'done'])->default('todo')->index();
            $table->string('label')->nullable();
            $table->date('due_date')->nullable();
            $table->string('assigned_user')->nullable();
            $table->unsignedInteger('order')->default(0)->index();
            $table->unsignedTinyInteger('company_score')->nullable();
            $table->unsignedTinyInteger('supervisor_score')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
