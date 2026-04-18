<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trello_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('trello_api_key')->nullable();
            $table->text('trello_token');
            $table->string('trello_board_id')->nullable();
            $table->string('trello_board_name')->nullable();
            $table->string('trello_member_id')->nullable();
            $table->string('webhook_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->unique('company_user_id');
        });

        Schema::create('trello_internship_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trello_integration_id')->constrained('trello_integrations')->cascadeOnDelete();
            $table->foreignId('opportunity_id')->constrained('internship_opportunities')->cascadeOnDelete();
            $table->string('trello_list_id');
            $table->string('trello_list_name')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('sync_status')->default('idle');
            $table->timestamps();
            $table->unique(['trello_integration_id', 'opportunity_id']);
        });

        Schema::create('task_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['task_id', 'student_id']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('company_user_id')->nullable()->after('application_id')->constrained('users')->nullOnDelete();
            $table->foreignId('trello_integration_id')->nullable()->after('company_user_id')->constrained('trello_integrations')->nullOnDelete();
            $table->string('trello_list_id')->nullable()->after('trello_card_id');
            $table->string('source')->default('manual')->after('trello_list_id');
            $table->timestamp('trello_last_synced_at')->nullable()->after('source');
        });

        // Backfill existing tasks with company owner and a default student assignment.
        $tasks = DB::table('tasks')
            ->join('applications', 'applications.id', '=', 'tasks.application_id')
            ->join('internship_opportunities', 'internship_opportunities.id', '=', 'applications.opportunity_id')
            ->select([
                'tasks.id as task_id',
                'applications.student_id',
                'internship_opportunities.company_user_id',
            ])
            ->get();

        foreach ($tasks as $row) {
            DB::table('tasks')->where('id', $row->task_id)->update([
                'company_user_id' => $row->company_user_id,
            ]);

            if (! empty($row->student_id)) {
                DB::table('task_assignments')->updateOrInsert(
                    ['task_id' => $row->task_id, 'student_id' => $row->student_id],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('trello_integration_id');
            $table->dropConstrainedForeignId('company_user_id');
            $table->dropColumn(['trello_list_id', 'source', 'trello_last_synced_at']);
        });

        Schema::dropIfExists('task_assignments');
        Schema::dropIfExists('trello_internship_links');
        Schema::dropIfExists('trello_integrations');
    }
};

