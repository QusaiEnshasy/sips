<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'university_id',
        'role',
        'password',
        'supervisor_code',
        'phone_number',
        'company_website',
        'company_name',
        'company_address',
        'status',
        'skill_test_required',
        'skill_test_passed',
        'is_in_jisr',
        'skill_test_completed_at',
        'jisr_completed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'skill_test_required' => 'boolean',
            'skill_test_passed' => 'boolean',
            'is_in_jisr' => 'boolean',
            'skill_test_completed_at' => 'datetime',
            'jisr_completed_at' => 'datetime',
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'student_id');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(InternshipOpportunity::class, 'company_user_id');
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function notificationsList(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'user_id');
    }

    public function skillTestResults(): HasMany
    {
        return $this->hasMany(SkillTestResult::class, 'user_id');
    }

    public function jisrSubmissions(): HasMany
    {
        return $this->hasMany(JisrSubmission::class, 'user_id');
    }
}
