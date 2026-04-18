<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('trello_integrations')) {
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
                $table->unique('company_user_id', 'trello_integrations_company_unique');
            });
        }

        if (! Schema::hasTable('trello_internship_links')) {
            Schema::create('trello_internship_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('trello_integration_id')->constrained('trello_integrations')->cascadeOnDelete();
                $table->foreignId('opportunity_id')->constrained('internship_opportunities')->cascadeOnDelete();
                $table->string('trello_list_id');
                $table->string('trello_list_name')->nullable();
                $table->timestamp('last_synced_at')->nullable();
                $table->string('sync_status')->default('idle');
                $table->timestamps();
                $table->unique(['trello_integration_id', 'opportunity_id'], 'trello_links_integration_opportunity_unique');
            });
        }

        if (! Schema::hasTable('task_assignments')) {
            Schema::create('task_assignments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['task_id', 'student_id'], 'task_assignments_task_student_unique');
            });
        }

        Schema::table('tasks', function (Blueprint $table) {
            if (! Schema::hasColumn('tasks', 'company_user_id')) {
                $table->foreignId('company_user_id')
                    ->nullable()
                    ->after('application_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('tasks', 'trello_integration_id')) {
                $table->foreignId('trello_integration_id')
                    ->nullable()
                    ->after('company_user_id')
                    ->constrained('trello_integrations')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('tasks', 'trello_list_id')) {
                $table->string('trello_list_id')->nullable()->after('trello_card_id');
            }

            if (! Schema::hasColumn('tasks', 'source')) {
                $table->string('source')->default('manual')->after('trello_list_id');
            }

            if (! Schema::hasColumn('tasks', 'trello_last_synced_at')) {
                $table->timestamp('trello_last_synced_at')->nullable()->after('source');
            }
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
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                if (Schema::hasColumn('tasks', 'trello_integration_id')) {
                    $table->dropConstrainedForeignId('trello_integration_id');
                }

                if (Schema::hasColumn('tasks', 'company_user_id')) {
                    $table->dropConstrainedForeignId('company_user_id');
                }

                $columnsToDrop = collect(['trello_list_id', 'source', 'trello_last_synced_at'])
                    ->filter(fn (string $column) => Schema::hasColumn('tasks', $column))
                    ->values()
                    ->all();

                if (! empty($columnsToDrop)) {
                    $table->dropColumn($columnsToDrop);
                }
            });
        }

        Schema::dropIfExists('task_assignments');
        Schema::dropIfExists('trello_internship_links');
        Schema::dropIfExists('trello_integrations');
    }
};

