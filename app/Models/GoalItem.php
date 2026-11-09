<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['goal_id', 'title', 'done_at', 'sort_order'])]
class GoalItem extends Model
{
    protected function casts(): array
    {
        return ['done_at' => 'immutable_datetime', 'sort_order' => 'integer'];
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }
}
