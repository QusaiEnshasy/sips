<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('trello_sync_logs')) {
            Schema::create('trello_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('trello_integration_id')->constrained('trello_integrations')->cascadeOnDelete();
                $table->foreignId('trello_internship_link_id')->nullable()->constrained('trello_internship_links')->nullOnDelete();
                $table->foreignId('opportunity_id')->nullable()->constrained('internship_opportunities')->nullOnDelete();
                $table->string('trigger')->default('manual');
                $table->string('status')->default('started');
                $table->unsignedInteger('created_count')->default(0);
                $table->unsignedInteger('updated_count')->default(0);
                $table->unsignedInteger('skipped_count')->default(0);
                $table->text('message')->nullable();
                $table->json('details')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('trello_sync_logs');
    }
};
