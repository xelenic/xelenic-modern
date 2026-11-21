<?php

namespace App\Models;

use App\Enums\ActivityType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One entry in a project's log (see Project::log()): what happened, who did
 * it, and for a Kiosk Studio save, what changed in the design.
 *
 * `changes` is a list of lines, each {tone, label, items}: tone is added,
 * removed, renamed, edited or info, and colours the line's marker.
 */
#[Fillable(['user_id', 'type', 'summary', 'changes', 'created_at'])]
class ProjectActivity extends Model
{
    /**
     * Entries are written once and never changed.
     */
    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'changes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
