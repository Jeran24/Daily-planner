<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'task_template_id', 'date', 'scheduled_at', 'status', 'completed_at', 'minutes_spent', 'note'])]
class TaskLog extends Model
{
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'immutable_datetime',
            'completed_at' => 'immutable_datetime',
            'minutes_spent' => 'integer',
            'note' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(TaskTemplate::class, 'task_template_id')->withTrashed();
    }
}
