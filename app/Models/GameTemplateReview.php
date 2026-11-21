<?php

namespace App\Models;

use Database\Factories\GameTemplateReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A signed-in user's star rating of a game template, with an optional
 * comment, left on its page in Kiosk Studio's marketplace. One per user per
 * game: posting again changes it.
 */
#[Fillable(['user_id', 'rating', 'comment'])]
class GameTemplateReview extends Model
{
    /** @use HasFactory<GameTemplateReviewFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<GameTemplate, $this>
     */
    public function gameTemplate(): BelongsTo
    {
        return $this->belongsTo(GameTemplate::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * How Kiosk Studio shows it on the game's page.
     *
     * @return array<string, mixed>
     */
    public function toStudio(?User $viewer): array
    {
        return [
            'id' => $this->id,
            'name' => $this->user->name,
            'initials' => $this->user->initials(),
            'rating' => $this->rating,
            'comment' => $this->comment,
            'date' => $this->updated_at->toDateString(),
            'ago' => $this->updated_at->gt(now()->subMinute()) ? 'just now' : $this->updated_at->diffForHumans(),
            'edited' => $this->updated_at->gt($this->created_at->addMinute()),
            'mine' => $viewer?->is($this->user) ?? false,
        ];
    }
}
