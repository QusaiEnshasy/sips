<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tasks') || ! Schema::hasColumn('tasks', 'training_id')) {
            return;
        }

        DB::statement("
            UPDATE tasks
            INNER JOIN applications ON applications.id = tasks.application_id
            SET tasks.training_id = applications.opportunity_id
            WHERE tasks.source = 'trello'
              AND tasks.training_id IS NULL
        ");
    }

    public function down(): void
    {
        // Do not remove repaired training links.
    }
};
