<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->text('company_final_note')->nullable()->after('company_final_score');
            $table->text('supervisor_final_note')->nullable()->after('supervisor_final_score');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'company_final_note',
                'supervisor_final_note',
            ]);
        });
    }
};
