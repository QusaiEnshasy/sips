<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillTestResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'skill_test_id',
        'specialization_code',
        'specialization_name',
        'score',
        'passed',
        'answers',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'passed' => 'boolean',
            'answers' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skillTest(): BelongsTo
    {
        return $this->belongsTo(SkillTest::class);
    }
}
