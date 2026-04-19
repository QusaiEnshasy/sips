<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tasks = [
            1 => [
                'title' => 'تنسيق صفحة تعريف شخصية',
                'description' => 'صمّم صفحة تعريف بسيطة تعرض معلومات شخصية ومهارات وروابط تواصل باستخدام HTML و CSS.',
                'instructions' => 'أنشئ صفحة تحتوي على صورة أو رمز، اسم الطالب، نبذة قصيرة، قائمة مهارات، وروابط تواصل. استخدم CSS لتنسيق الألوان، المسافات، وحجم الخطوط بشكل مرتب.',
                'type' => 'practical',
            ],
            2 => [
                'title' => 'تمرين منطق JavaScript',
                'description' => 'حل مسألة صغيرة باستخدام المتغيرات والحلقات والشروط.',
                'instructions' => 'اكتب حلًا قصيرًا لحاسبة درجات طالب، واشرح كيف يعمل المنطق البرمجي.',
                'type' => 'practical',
            ],
            3 => [
                'title' => 'ملخص أساسيات Laravel',
                'description' => 'لخّص دورة الطلب ودور المسارات والـ Controllers والـ Views.',
                'instructions' => 'اكتب شرحًا مختصرًا لكيفية معالجة Laravel للطلب وإرجاع الاستجابة.',
                'type' => 'theoretical',
            ],
        ];

        foreach ($tasks as $orderNumber => $task) {
            DB::table('jisr_tasks')
                ->where('order_number', $orderNumber)
                ->update($task + ['updated_at' => now()]);
        }
    }

    public function down(): void
    {
        $tasks = [
            1 => [
                'title' => 'HTML and CSS Foundations',
                'description' => 'Build a simple landing page that demonstrates structure and styling.',
                'instructions' => 'Create a clean page with a header, hero section, features list, and footer. Explain your layout choices briefly in the submission.',
                'type' => 'practical',
            ],
            2 => [
                'title' => 'JavaScript Logic Exercise',
                'description' => 'Solve a small problem using variables, loops, and conditions.',
                'instructions' => 'Write a short solution for a student score calculator and describe how the logic works.',
                'type' => 'practical',
            ],
            3 => [
                'title' => 'Laravel Basics Reflection',
                'description' => 'Summarize the request lifecycle and the role of routes, controllers, and views.',
                'instructions' => 'Write a concise explanation of how Laravel processes a request and returns a response.',
                'type' => 'theoretical',
            ],
        ];

        foreach ($tasks as $orderNumber => $task) {
            DB::table('jisr_tasks')
                ->where('order_number', $orderNumber)
                ->update($task + ['updated_at' => now()]);
        }
    }
};
