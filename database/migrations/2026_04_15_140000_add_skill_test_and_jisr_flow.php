<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('skill_test_required')->default(false)->after('status');
            $table->boolean('skill_test_passed')->default(false)->after('skill_test_required');
            $table->boolean('is_in_jisr')->default(false)->after('skill_test_passed');
            $table->timestamp('skill_test_completed_at')->nullable()->after('is_in_jisr');
            $table->timestamp('jisr_completed_at')->nullable()->after('skill_test_completed_at');
        });

        Schema::create('skill_tests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_minutes')->default(30);
            $table->unsignedInteger('passing_score')->default(70);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('skill_test_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('skill_test_id')->constrained('skill_tests')->cascadeOnDelete();
            $table->text('question');
            $table->json('options');
            $table->unsignedTinyInteger('correct_answer');
            $table->unsignedInteger('order_number')->default(1);
            $table->timestamps();
        });

        Schema::create('skill_test_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('skill_test_id')->constrained('skill_tests')->cascadeOnDelete();
            $table->unsignedInteger('score');
            $table->boolean('passed')->default(false);
            $table->json('answers')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('jisr_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->string('type')->default('practical');
            $table->string('url')->nullable();
            $table->unsignedInteger('max_score')->default(100);
            $table->unsignedInteger('order_number')->default(1);
            $table->timestamps();
        });

        Schema::create('jisr_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jisr_task_id')->constrained('jisr_tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->longText('content')->nullable();
            $table->json('attachments')->nullable();
            $table->string('status')->default('accepted');
            $table->unsignedInteger('score')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['jisr_task_id', 'user_id']);
        });

        $testId = DB::table('skill_tests')->insertGetId([
            'title' => 'Skill Assessment Test',
            'description' => 'Mandatory placement test for newly approved students before they can access internship opportunities.',
            'duration_minutes' => 30,
            'passing_score' => 70,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $questions = [
            [
                'question' => 'Which language is used by Laravel on the backend?',
                'options' => json_encode(['Python', 'PHP', 'Java', 'C#']),
                'correct_answer' => 1,
                'order_number' => 1,
            ],
            [
                'question' => 'Which option is a frontend framework?',
                'options' => json_encode(['Vue.js', 'MySQL', 'Laravel', 'PostgreSQL']),
                'correct_answer' => 0,
                'order_number' => 2,
            ],
            [
                'question' => 'What command is commonly used to run Laravel migrations?',
                'options' => json_encode(['php artisan migrate', 'npm run build', 'composer dump', 'git migrate']),
                'correct_answer' => 0,
                'order_number' => 3,
            ],
            [
                'question' => 'Which database system is relational?',
                'options' => json_encode(['MySQL', 'HTML', 'CSS', 'Figma']),
                'correct_answer' => 0,
                'order_number' => 4,
            ],
            [
                'question' => 'What does MVC stand for?',
                'options' => json_encode(['Main View Controller', 'Model View Controller', 'Module Value Class', 'Model Version Core']),
                'correct_answer' => 1,
                'order_number' => 5,
            ],
        ];

        foreach ($questions as $question) {
            DB::table('skill_test_questions')->insert([
                'skill_test_id' => $testId,
                'question' => $question['question'],
                'options' => $question['options'],
                'correct_answer' => $question['correct_answer'],
                'order_number' => $question['order_number'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $jisrTasks = [
            [
                'title' => 'HTML and CSS Foundations',
                'description' => 'Build a simple landing page that demonstrates structure and styling.',
                'instructions' => 'Create a clean page with a header, hero section, features list, and footer. Explain your layout choices briefly in the submission.',
                'type' => 'practical',
                'order_number' => 1,
            ],
            [
                'title' => 'JavaScript Logic Exercise',
                'description' => 'Solve a small problem using variables, loops, and conditions.',
                'instructions' => 'Write a short solution for a student score calculator and describe how the logic works.',
                'type' => 'practical',
                'order_number' => 2,
            ],
            [
                'title' => 'Laravel Basics Reflection',
                'description' => 'Summarize the request lifecycle and the role of routes, controllers, and views.',
                'instructions' => 'Write a concise explanation of how Laravel processes a request and returns a response.',
                'type' => 'theoretical',
                'order_number' => 3,
            ],
        ];

        foreach ($jisrTasks as $task) {
            DB::table('jisr_tasks')->insert([
                'title' => $task['title'],
                'description' => $task['description'],
                'instructions' => $task['instructions'],
                'type' => $task['type'],
                'max_score' => 100,
                'order_number' => $task['order_number'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('jisr_submissions');
        Schema::dropIfExists('jisr_tasks');
        Schema::dropIfExists('skill_test_results');
        Schema::dropIfExists('skill_test_questions');
        Schema::dropIfExists('skill_tests');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'skill_test_required',
                'skill_test_passed',
                'is_in_jisr',
                'skill_test_completed_at',
                'jisr_completed_at',
            ]);
        });
    }
};
