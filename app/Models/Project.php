<?php

namespace App\Models;

use App\Enums\KioskMachine;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
