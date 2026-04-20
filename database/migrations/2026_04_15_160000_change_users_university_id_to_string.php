<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite uses dynamic typing and doesn't support MODIFY syntax.
            return;
        }

        DB::statement('ALTER TABLE users MODIFY university_id VARCHAR(255) NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE users MODIFY university_id BIGINT UNSIGNED NULL');
    }
};
