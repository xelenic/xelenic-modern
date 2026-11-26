<?php

namespace App\Enums;

/**
 * Where a client's cloud service is: ordered and waiting for the team
 * (requested), being set up on InterServer (provisioning), running (active),
 * or not going ahead (rejected, failed, cancelled).
 */
enum CloudStatus: string
{
    case Requested = 'requested';
    case Provisioning = 'provisioning';
    case Active = 'active';
    case Rejected = 'rejected';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Requested => 'Awaiting approval',
            self::Provisioning => 'Setting up',
            self::Active => 'Active',
            self::Rejected => 'Declined',
            self::Failed => 'Needs attention',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * What it means, as the client sees it.
     */
    public function description(): string
    {
        return match ($this) {
            self::Requested => 'We have your order. Our team will confirm it and set it up.',
            self::Provisioning => 'Your order is confirmed and being set up. This usually takes a few minutes.',
            self::Active => 'Up and running.',
            self::Rejected => 'This order was declined. Contact us if you have questions.',
            self::Failed => 'Something went wrong setting this up. Our team has been told and is on it.',
            self::Cancelled => 'This service has been cancelled.',
        };
    }

    /**
     * The <x-badge> colour.
     */
    public function color(): string
    {
        return match ($this) {
            self::Requested => 'amber',
            self::Provisioning => 'blue',
            self::Active => 'emerald',
            self::Rejected, self::Cancelled => 'slate',
            self::Failed => 'violet',
        };
    }

    /**
     * Still in play (not rejected or cancelled).
     */
    public function isOpen(): bool
    {
        return ! in_array($this, [self::Rejected, self::Cancelled], true);
    }
}
