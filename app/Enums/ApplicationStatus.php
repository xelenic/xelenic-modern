<?php

namespace App\Enums;

/**
 * Where a job application is in the hiring process.
 */
enum ApplicationStatus: string
{
    case New = 'new';
    case Reviewing = 'reviewing';
    case Interviewing = 'interviewing';
    case Offered = 'offered';
    case Hired = 'hired';
    case Declined = 'declined';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Reviewing => 'Reviewing',
            self::Interviewing => 'Interviewing',
            self::Offered => 'Offer made',
            self::Hired => 'Hired',
            self::Declined => 'Declined',
        };
    }

    /**
     * The <x-badge> colour.
     */
    public function color(): string
    {
        return match ($this) {
            self::New => 'amber',
            self::Reviewing, self::Interviewing => 'blue',
            self::Offered => 'violet',
            self::Hired => 'emerald',
            self::Declined => 'slate',
        };
    }
}
