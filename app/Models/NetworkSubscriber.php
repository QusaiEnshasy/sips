<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NetworkSubscriber extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'subscriber_code',
        'name',
        'phone',
        'location',
        'installation_address',
        'subscription_type',
        'ip_address',
        'service_speed',
        'router_model',
        'monthly_fee',
        'due_day',
        'activation_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'monthly_fee' => 'decimal:2',
            'due_day' => 'integer',
            'activation_date' => 'date',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(NetworkPayment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function subscriptionTypeLabel(): string
    {
        return match ($this->subscription_type) {
            'access_point' => 'اكسس بوينت',
            default => 'برود باند',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'suspended' => 'موقوف مؤقتاً',
            'cancelled' => 'ملغي',
            default => 'فعال',
        };
    }
}
