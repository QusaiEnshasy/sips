<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InternshipOpportunity extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_user_id',
        'title',
        'description',
        'type',
        'field',
        'city',
        'work_type',
        'requirements',
        'education_level',
        'duration',
        'deadline',
        'status',
    ];

    public function companyUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_user_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'opportunity_id');
    }

    public function getTitleAttribute($value): ?string
    {
        return $this->normalizePotentialMojibake($value);
    }

    public function setTitleAttribute($value): void
    {
        $this->attributes['title'] = $this->normalizePotentialMojibake($value);
    }

    private function normalizePotentialMojibake($value): ?string
    {
        if (!is_string($value) || $value === '') {
            return $value;
        }

        // Detect common mojibake artifacts (UTF-8 text decoded as Windows-1252/ISO-8859-1).
        if (!preg_match('/[ÃÂØÙ]/u', $value)) {
            return $value;
        }

        $fixed = @iconv('Windows-1252', 'UTF-8//IGNORE', $value);
        if (is_string($fixed) && $fixed !== '' && mb_check_encoding($fixed, 'UTF-8')) {
            return $fixed;
        }

        return $value;
    }
}
