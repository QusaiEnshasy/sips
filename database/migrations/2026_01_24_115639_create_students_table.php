<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('university_id')->nullable()->constrained('universities')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('major_id')->nullable()->constrained('majors')->nullOnDelete();

            $table->string('student_number')->unique();
            $table->decimal('gpa', 3, 2)->nullable();
            $table->string('cv_url')->nullable();

            $table->timestamps();

            $table->unique('user_id'); // كل مستخدم له بروفايل طالب واحد فقط
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
