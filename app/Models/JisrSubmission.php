<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JisrSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'jisr_task_id',
        'user_id',
        'content',
        'attachments',
        'status',
        'score',
        'feedback',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(JisrTask::class, 'jisr_task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
