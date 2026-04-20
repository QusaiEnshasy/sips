<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trello_internship_links', function (Blueprint $table) {
            if (! Schema::hasColumn('trello_internship_links', 'assignment_mode')) {
                $table->string('assignment_mode')->default('marker_required')->after('trello_list_name');
            }

            if (! Schema::hasColumn('trello_internship_links', 'target_student_ids')) {
                $table->json('target_student_ids')->nullable()->after('assignment_mode');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trello_internship_links', function (Blueprint $table) {
            if (Schema::hasColumn('trello_internship_links', 'target_student_ids')) {
                $table->dropColumn('target_student_ids');
            }

            if (Schema::hasColumn('trello_internship_links', 'assignment_mode')) {
                $table->dropColumn('assignment_mode');
            }
        });
    }
};

