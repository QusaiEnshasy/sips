<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('trello_internship_links') || ! Schema::hasColumn('trello_internship_links', 'assignment_mode')) {
            return;
        }

        DB::table('trello_internship_links')
            ->where(function ($query) {
                $query->whereNull('assignment_mode')
                    ->orWhere('assignment_mode', '')
                    ->orWhere('assignment_mode', 'marker_required');
            })
            ->update([
                'assignment_mode' => 'all',
                'target_student_ids' => null,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Keep the safer visible-by-default behavior on rollback.
    }
};
