<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillTestQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'skill_test_id',
        'question',
        'options',
        'correct_answer',
        'order_number',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }

    public function skillTest(): BelongsTo
    {
        return $this->belongsTo(SkillTest::class);
    }
}
