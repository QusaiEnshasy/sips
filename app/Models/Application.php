<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Application extends Model
{
    protected $fillable = [
        'student_id',
        'opportunity_id',
        'skills',
        'motivation',
        'cv',
        'company_status',
        'supervisor_status',
        'final_status',
        'approved_at',
        'company_final_score',
        'company_final_note',
        'supervisor_final_score',
        'supervisor_final_note',
        'final_score',
        'training_completed_at',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'training_completed_at' => 'datetime',
    ];
 

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(InternshipOpportunity::class, 'opportunity_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
