<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NetworkCardSale extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_date',
        'card_name',
        'cards_count',
        'unit_price',
        'total_amount',
        'payment_method',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'cards_count' => 'integer',
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (NetworkCardSale $sale): void {
            $sale->total_amount = (float) $sale->cards_count * (float) $sale->unit_price;
        });
    }
}
