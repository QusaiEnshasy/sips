<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkillTestAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'skill_test_id',
        'specialization_code',
        'specialization_name',
        'status',
        'answers',
        'started_at',
        'expires_at',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
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
