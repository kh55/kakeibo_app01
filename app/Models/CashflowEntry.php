<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashflowEntry extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'name',
        'expense_amount',
        'income_amount',
        'memo',
    ];

    protected $casts = [
        'date' => 'date',
        'expense_amount' => 'decimal:2',
        'income_amount' => 'decimal:2',
    ];

    /**
     * Get the user that owns the cashflow entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the net amount (income - expense).
     */
    public function getNetAmountAttribute(): float
    {
        return $this->income_amount - $this->expense_amount;
    }
}
