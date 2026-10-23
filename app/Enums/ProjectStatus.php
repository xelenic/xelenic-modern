<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Submitted = 'submitted';
    case InReview = 'in_review';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::InReview => 'In review',
            self::InProgress => 'In progress',
            self::Completed => 'Completed',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * What this status means, as shown to the client.
     */
    public function description(): string
    {
        return match ($this) {
            self::Submitted => "We've received your request and will review it soon.",
            self::InReview => 'Our team is reviewing your request and will be in touch.',
            self::InProgress => "We're working on your project.",
            self::Completed => 'This project is complete.',
            self::Cancelled => 'This project was cancelled.',
        };
    }

    /**
     * The <x-badge> color for this status.
     */
    public function color(): string
    {
        return match ($this) {
            self::Submitted => 'amber',
            self::InReview => 'blue',
            self::InProgress => 'violet',
            self::Completed => 'emerald',
            self::Cancelled => 'slate',
        };
    }

    /**
     * Clients can still cancel a project nobody has started on.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this, [self::Submitted, self::InReview]);
    }

    /**
     * Every status keyed by value, for a <select>.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $status) => [$status->value => $status->label()])->all();
    }
}
