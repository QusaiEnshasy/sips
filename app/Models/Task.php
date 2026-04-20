<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\InternshipOpportunity;

class Task extends Model
{
    protected $fillable = [
        'application_id',
        'training_id',
        'company_user_id',
        'trello_integration_id',
        'created_by',
        'trello_card_id',
        'trello_list_id',
        'source',
        'trello_last_synced_at',
        'title',
        'description',
        'details',
        'student_solution',
        'assigned_user',
        'due_date',
        'label',
        'status',
        'order',
        'company_score',
        'supervisor_score',
    ];

    protected $casts = [
        'due_date' => 'date',
        'trello_last_synced_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(InternshipOpportunity::class, 'training_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_user_id');
    }

    public function trelloIntegration(): BelongsTo
    {
        return $this->belongsTo(TrelloIntegration::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function assignedStudents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_assignments', 'task_id', 'student_id')
            ->withPivot('status')
            ->withTimestamps();
    }
}

