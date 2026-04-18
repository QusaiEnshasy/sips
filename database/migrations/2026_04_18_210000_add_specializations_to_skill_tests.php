<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('skill_tests', function (Blueprint $table) {
            $table->string('specialization_code')->nullable()->after('description');
            $table->string('specialization_name')->nullable()->after('specialization_code');
        });

        Schema::table('skill_test_results', function (Blueprint $table) {
            $table->string('specialization_code')->nullable()->after('skill_test_id');
            $table->string('specialization_name')->nullable()->after('specialization_code');
        });

        $existingTest = DB::table('skill_tests')->orderBy('id')->first();

        if ($existingTest) {
            DB::table('skill_tests')
                ->where('id', $existingTest->id)
                ->update([
                    'specialization_code' => 'web_development',
                    'specialization_name' => 'تطوير الويب',
                ]);

            $this->insertSpecializationTest([
                'code' => 'networking',
                'name' => 'الشبكات',
                'title' => 'Network Skills Assessment Test',
                'description' => 'Placement test for students joining networking-related training tracks.',
                'duration_minutes' => 30,
                'passing_score' => 70,
                'questions' => [
                    [
                        'question' => 'What does IP stand for in computer networking?',
                        'options' => ['Internet Protocol', 'Internal Program', 'Input Port', 'Interface Process'],
                        'correct_answer' => 0,
                    ],
                    [
                        'question' => 'Which device forwards traffic between different networks?',
                        'options' => ['Switch', 'Router', 'Access Point', 'Patch Panel'],
                        'correct_answer' => 1,
                    ],
                    [
                        'question' => 'Which protocol is commonly used to automatically assign IP addresses?',
                        'options' => ['DNS', 'DHCP', 'FTP', 'SMTP'],
                        'correct_answer' => 1,
                    ],
                    [
                        'question' => 'Which layer of the OSI model is responsible for routing?',
                        'options' => ['Physical', 'Data Link', 'Network', 'Presentation'],
                        'correct_answer' => 2,
                    ],
                    [
                        'question' => 'What is the main purpose of a subnet mask?',
                        'options' => ['Encrypt traffic', 'Divide the network and host portions of an IP address', 'Improve Wi-Fi speed', 'Store DNS records'],
                        'correct_answer' => 1,
                    ],
                ],
            ]);

            $this->insertSpecializationTest([
                'code' => 'cybersecurity',
                'name' => 'الأمن السيبراني',
                'title' => 'Cybersecurity Skills Assessment Test',
                'description' => 'Placement test for students joining cybersecurity-related training tracks.',
                'duration_minutes' => 30,
                'passing_score' => 70,
                'questions' => [
                    [
                        'question' => 'What is phishing primarily used for?',
                        'options' => ['Speeding up networks', 'Tricking users into revealing sensitive data', 'Compressing files', 'Backing up databases'],
                        'correct_answer' => 1,
                    ],
                    [
                        'question' => 'Which principle gives users only the access they need?',
                        'options' => ['Least privilege', 'Open access', 'Shared trust', 'Maximum control'],
                        'correct_answer' => 0,
                    ],
                    [
                        'question' => 'Which tool category is designed to filter incoming and outgoing network traffic?',
                        'options' => ['Compiler', 'Firewall', 'Browser', 'Spreadsheet'],
                        'correct_answer' => 1,
                    ],
                    [
                        'question' => 'What does MFA stand for?',
                        'options' => ['Managed File Access', 'Multi-Factor Authentication', 'Main Function Audit', 'Manual Firewall Approval'],
                        'correct_answer' => 1,
                    ],
                    [
                        'question' => 'Why are software updates important in security?',
                        'options' => ['They only change colors', 'They can patch known vulnerabilities', 'They remove passwords', 'They disable backups'],
                        'correct_answer' => 1,
                    ],
                ],
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('skill_test_results', function (Blueprint $table) {
            $table->dropColumn([
                'specialization_code',
                'specialization_name',
            ]);
        });

        Schema::table('skill_tests', function (Blueprint $table) {
            $table->dropColumn([
                'specialization_code',
                'specialization_name',
            ]);
        });
    }

    private function insertSpecializationTest(array $data): void
    {
        $alreadyExists = DB::table('skill_tests')
            ->where('specialization_code', $data['code'])
            ->exists();

        if ($alreadyExists) {
            return;
        }

        $testId = DB::table('skill_tests')->insertGetId([
            'title' => $data['title'],
            'description' => $data['description'],
            'specialization_code' => $data['code'],
            'specialization_name' => $data['name'],
            'duration_minutes' => $data['duration_minutes'],
            'passing_score' => $data['passing_score'],
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($data['questions'] as $index => $question) {
            DB::table('skill_test_questions')->insert([
                'skill_test_id' => $testId,
                'question' => $question['question'],
                'options' => json_encode($question['options']),
                'correct_answer' => $question['correct_answer'],
                'order_number' => $index + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
