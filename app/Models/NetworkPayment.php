<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'network_subscriber_id',
        'period_month',
        'amount',
        'paid_at',
        'payment_method',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'date',
            'amount' => 'decimal:2',
            'paid_at' => 'date',
        ];
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(NetworkSubscriber::class, 'network_subscriber_id');
    }
}
