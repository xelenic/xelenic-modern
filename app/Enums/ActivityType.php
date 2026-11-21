<?php

namespace App\Enums;

/**
 * The kinds of entry in a project's log (App\Models\ProjectActivity).
 */
enum ActivityType: string
{
    case Created = 'created';
    case Design = 'design';
    case Status = 'status';
    case PlayLink = 'play-link';

    /**
     * The <x-icon> on its point in the timeline.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Created => 'flag',
            self::Design => 'cube',
            self::Status => 'switch',
            self::PlayLink => 'link',
        };
    }

    /**
     * Classes for its point in the timeline: a tinted circle.
     */
    public function tint(): string
    {
        return match ($this) {
            self::Created => 'bg-slate-100 text-slate-600',
            self::Design => 'bg-blue-100 text-blue-700',
            self::Status => 'bg-violet-100 text-violet-700',
            self::PlayLink => 'bg-emerald-100 text-emerald-700',
        };
    }
}
