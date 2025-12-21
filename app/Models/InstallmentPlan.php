<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstallmentPlan extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'start_date',
        'pay_day',
        'amount',
        'times',
        'remaining_times',
        'account_id',
        'category_id',
        'enabled',
    ];

    protected $casts = [
        'start_date' => 'date',
        'pay_day' => 'integer',
        'amount' => 'decimal:2',
        'times' => 'integer',
        'remaining_times' => 'integer',
        'enabled' => 'boolean',
    ];

    /**
     * Get the user that owns the installment plan.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the account for the installment plan.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the category for the installment plan.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Check if the installment plan is completed.
     */
    public function isCompleted(): bool
    {
        return $this->remaining_times === 0;
    }
}
