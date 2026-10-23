<?php

namespace App\Enums;

/**
 * The kiosk hardware a client can pick when they start a Kiosk App project.
 * Each has a flat illustration in public/kiosk/flat, named after its value.
 */
enum KioskMachine: string
{
    case OrderingBilling = 'ordering-billing';
    case SelfServiceTower = 'self-service-tower';
    case Advertising = 'advertising';
    case Portrait55 = 'portrait-55';
    case Ticketing = 'ticketing';
    case TableTower = 'table-tower';
    case WideTouchTable = 'wide-touch-table';

    public function label(): string
    {
        return match ($this) {
            self::OrderingBilling => 'Self-Ordering & Billing Kiosk',
            self::SelfServiceTower => 'Self-Service Tower',
            self::Advertising => 'Advertising Kiosk',
            self::Portrait55 => '55-inch Portrait Display',
            self::Ticketing => 'Ticketing Kiosk with Keyboard',
            self::TableTower => 'Table Tower Kiosk',
            self::WideTouchTable => 'Wide Touch Table',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::OrderingBilling => 'Portrait touchscreen with a card terminal and receipt printer, for restaurants and retail.',
            self::SelfServiceTower => 'Slim pedestal kiosk for check-in, queueing and quick payments.',
            self::Advertising => 'Floor-standing touchscreen for promotions and interactive ads.',
            self::Portrait55 => 'Ultra-slim digital signage totem for lobbies, malls and wayfinding.',
            self::Ticketing => 'Angled screen with a built-in keyboard for forms, registrations and tickets.',
            self::TableTower => 'Landscape touchscreen on a sturdy stand for browsing catalogues and maps.',
            self::WideTouchTable => 'Extra-wide landscape screen for showrooms and visual product browsing.',
        };
    }

    /**
     * The screen this machine usually ships with, as the settings panel's
     * starting point (see App\Support\KioskSettings).
     *
     * @return array{size: string, orientation: string}
     */
    public function screen(): array
    {
        [$size, $orientation] = match ($this) {
            self::OrderingBilling => ['27', 'portrait'],
            self::SelfServiceTower => ['21.5', 'portrait'],
            self::Advertising => ['43', 'portrait'],
            self::Portrait55 => ['55', 'portrait'],
            self::Ticketing => ['32', 'landscape'],
            self::TableTower => ['43', 'landscape'],
            self::WideTouchTable => ['55', 'landscape'],
        };

        return ['size' => $size, 'orientation' => $orientation];
    }

    /**
     * The URL of the machine's flat illustration, public/kiosk/flat/{value}.svg.
     */
    public function image(): string
    {
        return asset("kiosk/flat/{$this->value}.svg");
    }
}
