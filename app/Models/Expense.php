<?php

namespace App\Models;

use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'amount',
        'title',
        'description',
        'date',
        'type',
        'recurring_frequency',
        'recurring_start_date',
        'recurring_end_date',
        'parent_expense_id',
        'is_auto_generated'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function parentExpense(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'parent_expense_id');

    }

    public function  childExpenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'parent_expense_id');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecurring($query)
    {
        return $query->where('type', 'recurring');
    }

    public function scopeOneTime($query)
    {
        return $query->where('type', 'one-time');
    }

    public function scopeInMonth($query, $month, $year)
    {
        return $query->whereYear('date', $year)
                    ->whereMonth('date', $month);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function isRecurring(): bool
    {
        return $this->type === 'recurring';
    }

    public function isOneTime(): bool
    {
        return $this->type === 'one-time';
    }

    public function shouldGenerateNextOccurrence(): bool
    {
        if (!$this->isRecurring()) {
            return false;
        }

        if($this->recurring_end_date && now()->isAfter($this->recurring_end_date)) {
            return false; // No more occurrences after end date
        }

        return true;
    }

    public function getNextOccurrenceDate(): ?\Carbon\Carbon
    {
        if(!$this->isRecurring())
        {
            return null;
        }

        $lastChildExpense = $this->childExpenses()->orderBy('date', 'desc')->first();
        $baseDate = $lastChildExpense ? $lastChildExpense->date : $this->recurring_start_date ?? $this->date;

        return match ($this->recurring_frequency) {
            'daily' => $baseDate->copy()->addDay(),
            'weekly' => $baseDate->copy()->addWeek(),
            'monthly' => $baseDate->copy()->addMonth(),
            'yearly' => $baseDate->copy()->addYear(),
            default => null,
        };
    }


    // Alternative method to getNextOccurrenceDate
    // public function getNextOccurrenceDate(): ?\Illuminate\Support\Carbon
    // {
    //     if (!$this->shouldGenerateNextOccurrence()) {
    //         return null;
    //     }

    //     $nextDate = null;

    //     switch ($this->recurring_frequency) {
    //         case 'daily':
    //             $nextDate = $this->date->copy()->addDay();
    //             break;
    //         case 'weekly':
    //             $nextDate = $this->date->copy()->addWeek();
    //             break;
    //         case 'monthly':
    //             $nextDate = $this->date->copy()->addMonth();
    //             break;
    //         case 'yearly':
    //             $nextDate = $this->date->copy()->addYear();
    //             break;
    //     }

    //     // Ensure next occurrence is not before the recurring start date
    //     if ($this->recurring_start_date && $nextDate->lt($this->recurring_start_date)) {
    //         $nextDate = $this->recurring_start_date->copy();
    //     }

    //     return $nextDate;
    // }

    // public function isCurrentlyActive(): bool
    // {
    //     if ($this->isOneTime()) {
    //         return $this->date->isToday() || $this->date->isPast();
    //     }

    //     if ($this->isRecurring()) {
    //         $today = now()->startOfDay();
    //         $startDate = $this->recurring_start_date ? $this->recurring_start_date->startOfDay() : null;
    //         $endDate = $this->recurring_end_date ? $this->recurring_end_date->startOfDay() : null;

    //         if ($startDate && $today->lt($startDate)) {
    //             return false; // Not started yet
    //         }

    //         if ($endDate && $today->gt($endDate)) {
    //             return false; // Already ended
    //         }

    //         return true; // Currently active
    //     }

    //     return false;
    // }

}
