<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringRule extends Model
{
    protected $fillable = [
        'user_id',
        'account_id',
        'day_of_month',
        'name',
        'amount',
        'category_id',
        'enabled',
    ];

    protected $casts = [
        'day_of_month' => 'integer',
        'amount' => 'decimal:2',
        'enabled' => 'boolean',
    ];

    /**
     * Get the user that owns the recurring rule.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the account for the recurring rule.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the category for the recurring rule.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
