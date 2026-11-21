<?php

namespace App\Models;

use App\Enums\ActivityType;
use App\Enums\KioskMachine;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Support\KioskDesign;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['type', 'name', 'description', 'budget', 'timeline', 'details', 'status'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /**
     * The choices offered for budget and timeline on every project.
     */
    public const BUDGETS = ['Under $5,000', '$5,000–$15,000', '$15,000–$50,000', 'Over $50,000', 'Not sure yet'];

    public const TIMELINES = ['As soon as possible', '1–3 months', '3–6 months', 'More than 6 months', 'Flexible'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'submitted',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ProjectType::class,
            'status' => ProjectStatus::class,
            'details' => 'array',
            'shared_at' => 'datetime',
        ];
    }

    /**
     * The client who asked for the project.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The project's log, oldest first (see log()).
     *
     * @return HasMany<ProjectActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(ProjectActivity::class);
    }

    /**
     * Add an entry to the project's log: what happened, who did it, and what
     * changed (see ProjectActivity).
     *
     * @param  list<array{tone: string, label: string, items: list<string>}>  $changes
     */
    public function log(ActivityType $type, string $summary, ?User $by = null, array $changes = []): ProjectActivity
    {
        return $this->activities()->create([
            'user_id' => $by?->id,
            'type' => $type,
            'summary' => $summary,
            'changes' => $changes ?: null,
        ]);
    }

    /**
     * The machine picked for a kiosk project, if one was.
     */
    public function kioskMachine(): ?KioskMachine
    {
        return KioskMachine::tryFrom($this->details['machine'] ?? '');
    }

    /**
     * The kiosk settings panel's values (see App\Support\KioskSettings), for
     * kiosk projects.
     *
     * @return array<string, mixed>|null
     */
    public function kioskSettings(): ?array
    {
        return $this->details['settings'] ?? null;
    }

    /**
     * A summary of the Kiosk Studio design attached to a kiosk project
     * (see App\Support\KioskDesign), if one was.
     *
     * @return array{name: string, screens: int, orientation: string}|null
     */
    public function kioskDesign(): ?array
    {
        return $this->details['design'] ?? null;
    }

    /**
     * A kiosk app can be shared to play once it has a design.
     */
    public function canBeShared(): bool
    {
        return $this->type === ProjectType::KioskApp && KioskDesign::exists($this);
    }

    /**
     * Its play link is on (see share()).
     */
    public function isShared(): bool
    {
        return $this->share_token !== null;
    }

    /**
     * The public page where anyone with the link plays the kiosk app.
     */
    public function playUrl(): ?string
    {
        return $this->share_token ? route('play', $this->share_token) : null;
    }

    /**
     * Turn the play link on, with a new secret code: a link given out before
     * stops working.
     */
    public function share(): void
    {
        $this->forceFill(['share_token' => Str::random(40), 'shared_at' => now()])->save();
    }

    public function stopSharing(): void
    {
        $this->forceFill(['share_token' => null, 'shared_at' => null])->save();
    }

    /**
     * The client can keep changing their Kiosk Studio design until the
     * project is completed or cancelled.
     */
    public function canChangeKioskDesign(): bool
    {
        return $this->type === ProjectType::KioskApp
            && ! in_array($this->status, [ProjectStatus::Completed, ProjectStatus::Cancelled]);
    }

    /**
     * The answers to the type's extra questions, keyed by question label,
     * with lists joined up for display. Unanswered questions are left out.
     *
     * @return array<string, string>
     */
    public function answers(): array
    {
        return collect($this->type->questions())
            ->filter(fn (array $question) => filled($this->details[$question['name']] ?? null))
            ->mapWithKeys(fn (array $question) => [
                $question['label'] => implode(', ', (array) $this->details[$question['name']]),
            ])
            ->all();
    }
}
