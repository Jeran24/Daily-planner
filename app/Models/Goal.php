<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'title', 'start_date', 'end_date', 'measure_type', 'target_value', 'manual_percent', 'status'])]
class Goal extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'start_date' => 'immutable_date',
            'end_date' => 'immutable_date',
            'target_value' => 'integer',
            'manual_percent' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoalItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(GoalProgressSnapshot::class);
    }
}
