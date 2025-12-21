<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    protected $fillable = [
        'user_id',
        'year',
        'month',
        'category_id',
        'amount',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the user that owns the budget.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category for the budget.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
