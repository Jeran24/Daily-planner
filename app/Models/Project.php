<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['user_id', 'name', 'description', 'skills_needed', 'status', 'priority', 'notes'])]
class Project extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'skills_needed' => 'array',
            'priority' => 'integer',
            'notes' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
