<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'training_id')) {
                $table->foreignId('training_id')
                    ->nullable()
                    ->after('application_id')
                    ->constrained('internship_opportunities')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('tasks', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
        });

        if (Schema::hasColumn('tasks', 'training_id')) {
            DB::statement("
                UPDATE tasks
                SET training_id = (
                    SELECT applications.opportunity_id
                    FROM applications
                    WHERE applications.id = tasks.application_id
                )
                WHERE training_id IS NULL
            ");
        }

        if (Schema::hasColumn('tasks', 'description') && Schema::hasColumn('tasks', 'details')) {
            DB::statement("UPDATE tasks SET description = details WHERE description IS NULL");
        }
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if (Schema::hasColumn('tasks', 'training_id')) {
                $table->dropConstrainedForeignId('training_id');
            }

            if (Schema::hasColumn('tasks', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};

