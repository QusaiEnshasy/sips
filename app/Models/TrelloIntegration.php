<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrelloIntegration extends Model
{
    protected $fillable = [
        'company_user_id',
        'trello_api_key',
        'trello_token',
        'trello_board_id',
        'trello_board_name',
        'trello_member_id',
        'webhook_id',
        'is_active',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'trello_token' => 'encrypted',
            'is_active' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_user_id');
    }

    public function internshipLinks(): HasMany
    {
        return $this->hasMany(TrelloInternshipLink::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(TrelloSyncLog::class);
    }
}

