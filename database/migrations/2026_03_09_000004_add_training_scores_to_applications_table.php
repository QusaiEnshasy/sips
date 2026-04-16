<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->unsignedTinyInteger('company_final_score')->nullable()->after('approved_at');
            $table->unsignedTinyInteger('supervisor_final_score')->nullable()->after('company_final_score');
            $table->unsignedTinyInteger('final_score')->nullable()->after('supervisor_final_score');
            $table->timestamp('training_completed_at')->nullable()->after('final_score');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'company_final_score',
                'supervisor_final_score',
                'final_score',
                'training_completed_at',
            ]);
        });
    }
};
