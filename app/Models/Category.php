<?php

namespace App\Models;

use App\Models\User;
use App\Models\Budget;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected  $fillable = [
        'user_id',
        'name',
        'color',
        'icon'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function getTotalSpentForMonth(int $month, int $year): float
    {
        return $this->expenses()
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->sum('amount');
    }
}
