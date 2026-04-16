<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JisrTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'instructions',
        'type',
        'url',
        'max_score',
        'order_number',
    ];

    public function submissions(): HasMany
    {
        return $this->hasMany(JisrSubmission::class)->orderByDesc('submitted_at');
    }
}
