<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

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
     * Retrieve the model for bound value.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $user = Auth::user();
        if (! $user) {
            return null;
        }

        return $this->where('id', $value)
            ->where('user_id', $user->id)
            ->first();
    }

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
