<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('jisr_tasks')
            ->where('order_number', 1)
            ->update([
                'title' => 'تنسيق صفحة تعريف شخصية',
                'description' => 'صمّم صفحة تعريف بسيطة تعرض معلومات شخصية ومهارات وروابط تواصل باستخدام HTML و CSS.',
                'instructions' => 'أنشئ صفحة تحتوي على صورة أو رمز، اسم الطالب، نبذة قصيرة، قائمة مهارات، وروابط تواصل. استخدم CSS لتنسيق الألوان، المسافات، وحجم الخطوط بشكل مرتب.',
                'type' => 'practical',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('jisr_tasks')
            ->where('order_number', 1)
            ->update([
                'title' => 'أساسيات HTML و CSS',
                'description' => 'ابنِ صفحة هبوط بسيطة توضّح بنية الصفحة والتنسيق.',
                'instructions' => 'أنشئ صفحة مرتبة تحتوي على رأس الصفحة، قسم تعريفي، قائمة مميزات، وتذييل. اشرح باختصار سبب اختيارك للتخطيط والتنسيق.',
                'type' => 'practical',
                'updated_at' => now(),
            ]);
    }
};
