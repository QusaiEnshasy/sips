<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrelloSyncLog extends Model
{
    protected $fillable = [
        'trello_integration_id',
        'trello_internship_link_id',
        'opportunity_id',
        'trigger',
        'status',
        'created_count',
        'updated_count',
        'skipped_count',
        'message',
        'details',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(TrelloIntegration::class, 'trello_integration_id');
    }

    public function internshipLink(): BelongsTo
    {
        return $this->belongsTo(TrelloInternshipLink::class, 'trello_internship_link_id');
    }
}
