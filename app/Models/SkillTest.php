<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SkillTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'specialization_code',
        'specialization_name',
        'duration_minutes',
        'passing_score',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function questions(): HasMany
    {
        return $this->hasMany(SkillTestQuestion::class)->orderBy('order_number');
    }
}
