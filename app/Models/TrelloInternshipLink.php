<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrelloInternshipLink extends Model
{
    protected $fillable = [
        'trello_integration_id',
        'opportunity_id',
        'trello_list_id',
        'trello_list_name',
        'assignment_mode',
        'target_student_ids',
        'last_synced_at',
        'sync_status',
    ];

    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
            'target_student_ids' => 'array',
        ];
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(TrelloIntegration::class, 'trello_integration_id');
    }

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(InternshipOpportunity::class, 'opportunity_id');
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(TrelloSyncLog::class, 'trello_internship_link_id');
    }
}

