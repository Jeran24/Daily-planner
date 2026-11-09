<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'name', 'category', 'start_time', 'duration_minutes', 'weekdays', 'is_default', 'is_active', 'remind_minutes_before', 'alarm_enabled', 'goal_id', 'minimum_version', 'sort_order'])]
class TaskTemplate extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'weekdays' => 'array',
            'duration_minutes' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'remind_minutes_before' => 'integer',
            'alarm_enabled' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TaskLog::class);
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class)->withTrashed();
    }
}
