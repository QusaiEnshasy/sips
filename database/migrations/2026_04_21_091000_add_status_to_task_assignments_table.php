<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_assignments', function (Blueprint $table) {
            if (! Schema::hasColumn('task_assignments', 'status')) {
                $table->string('status')->default('pending')->after('student_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('task_assignments', function (Blueprint $table) {
            if (Schema::hasColumn('task_assignments', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};

