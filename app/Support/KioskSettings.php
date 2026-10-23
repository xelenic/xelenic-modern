<?php

namespace App\Support;

use App\Enums\KioskMachine;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * The settings panel on a Kiosk App project: screen, rendering, game board,
 * payments and extras. They're stored under the project's details.settings.
 */
final class KioskSettings
{
    /**
     * Screen diagonals on offer, in inches.
     */
    public const SCREEN_SIZES = ['15.6', '21.5', '27', '32', '43', '49', '55', '65', '75', '86'];

    public const ORIENTATIONS = ['portrait' => 'Portrait', 'landscape' => 'Landscape'];

    /**
     * 16:9 resolutions, keyed by their landscape width x height.
     */
    public const RESOLUTIONS = [
        '1366x768' => 'HD',
        '1920x1080' => 'Full HD',
        '2560x1440' => 'QHD',
        '3840x2160' => '4K UHD',
    ];

    public const GAMES = ['Spin the wheel', 'Quiz', 'Scratch card', 'Memory match', 'Catch game'];

    public const PAYMENT_METHODS = ['Card (chip & tap)', 'QR code payments', 'Mobile wallets (Apple Pay, Google Pay)', 'Cash'];

    public const ADD_ONS = [
        'Receipt printer', 'Barcode & QR scanner', 'Camera', 'Multi-language',
        'Accessibility mode', 'Offline mode', 'Remote content management',
    ];

    /**
     * How many preview pixels stand for one inch of screen, so screen sizes
     * can be compared at a glance (an 86-inch screen is about 210px long).
     */
    public const PREVIEW_PX_PER_INCH = 2.8;

    /**
     * The settings a new project starts with: the machine's own screen, and
     * payments switched on for the machine with a card terminal.
     *
     * @return array<string, mixed>
     */
    public static function defaults(?KioskMachine $machine): array
    {
        $screen = $machine?->screen() ?? ['size' => '32', 'orientation' => 'portrait'];

        return [
            'screen_size' => $screen['size'],
            'orientation' => $screen['orientation'],
            'resolution' => '1920x1080',
            'webgl' => false,
            'game_board' => false,
            'games' => [],
            'leaderboard' => false,
            'payments' => $machine === KioskMachine::OrderingBilling,
            'payment_methods' => [],
            'add_ons' => [],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'settings' => ['required', 'array'],
            'settings.screen_size' => ['required', Rule::in(self::SCREEN_SIZES)],
            'settings.orientation' => ['required', Rule::in(array_keys(self::ORIENTATIONS))],
            'settings.resolution' => ['required', Rule::in(array_keys(self::RESOLUTIONS))],
            'settings.webgl' => ['boolean'],
            'settings.game_board' => ['boolean'],
            'settings.games' => ['array'],
            'settings.games.*' => [Rule::in(self::GAMES)],
            'settings.leaderboard' => ['boolean'],
            'settings.payments' => ['boolean'],
            'settings.payment_methods' => ['array'],
            'settings.payment_methods.*' => [Rule::in(self::PAYMENT_METHODS)],
            'settings.add_ons' => ['array'],
            'settings.add_ons.*' => [Rule::in(self::ADD_ONS)],
        ];
    }

    /**
     * Validated input as stored: switches as booleans, and the choices under
     * a switch dropped when it's off.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public static function fromInput(array $input): array
    {
        $gameBoard = (bool) ($input['game_board'] ?? false);
        $payments = (bool) ($input['payments'] ?? false);

        return [
            'screen_size' => (string) $input['screen_size'],
            'orientation' => $input['orientation'],
            'resolution' => $input['resolution'],
            'webgl' => (bool) ($input['webgl'] ?? false),
            'game_board' => $gameBoard,
            'games' => $gameBoard ? array_values($input['games'] ?? []) : [],
            'leaderboard' => $gameBoard && (bool) ($input['leaderboard'] ?? false),
            'payments' => $payments,
            'payment_methods' => $payments ? array_values($input['payment_methods'] ?? []) : [],
            'add_ons' => array_values($input['add_ons'] ?? []),
        ];
    }

    /**
     * The physical size and pixel size of a 16:9 screen as it's mounted.
     * resources/js/kiosk-settings.js does the same sums for the live preview.
     *
     * @return array{width_in: float, height_in: float, width_cm: string, height_cm: string, width_px: int, height_px: int, ppi: int}
     */
    public static function dimensions(string $size, string $orientation, string $resolution): array
    {
        $diagonal = max((float) $size, 1);
        $long = $diagonal * 16 / hypot(16, 9);
        $short = $diagonal * 9 / hypot(16, 9);
        [$resWidth, $resHeight] = array_map('intval', explode('x', $resolution) + [1920, 1080]);

        $portrait = $orientation === 'portrait';
        [$width, $height] = $portrait ? [$short, $long] : [$long, $short];

        return [
            'width_in' => $width,
            'height_in' => $height,
            'width_cm' => number_format($width * 2.54, 1),
            'height_cm' => number_format($height * 2.54, 1),
            'width_px' => $portrait ? $resHeight : $resWidth,
            'height_px' => $portrait ? $resWidth : $resHeight,
            'ppi' => (int) round(hypot($resWidth, $resHeight) / $diagonal),
        ];
    }

    /**
     * "55″ · Portrait"
     */
    public static function sizeLabel(string $size, string $orientation): string
    {
        return $size.'″ · '.(self::ORIENTATIONS[$orientation] ?? ucfirst($orientation));
    }

    /**
     * Screen sizes for a <select>: "55" => "55″".
     *
     * @return array<string, string>
     */
    public static function sizeOptions(): array
    {
        return collect(self::SCREEN_SIZES)->mapWithKeys(fn (string $size) => [$size => "{$size}″"])->all();
    }

    /**
     * Resolutions for a <select>: "1920x1080" => "Full HD (1080p)".
     *
     * @return array<string, string>
     */
    public static function resolutionOptions(): array
    {
        return collect(self::RESOLUTIONS)
            ->mapWithKeys(fn (string $name, string $value) => [$value => $name.' ('.Str::after($value, 'x').'p)'])
            ->all();
    }

    /**
     * Stored settings in words, for the project page.
     *
     * @param  array<string, mixed>  $settings
     * @return array<string, string>
     */
    public static function summary(array $settings): array
    {
        $dimensions = self::dimensions($settings['screen_size'], $settings['orientation'], $settings['resolution']);
        $onOff = fn (bool $on, array $choices) => $on ? (implode(', ', $choices) ?: 'On') : 'Off';

        return [
            'Screen' => self::sizeLabel($settings['screen_size'], $settings['orientation'])." · {$dimensions['width_cm']} × {$dimensions['height_cm']} cm",
            'Resolution' => (self::RESOLUTIONS[$settings['resolution']] ?? $settings['resolution'])." · {$dimensions['width_px']} × {$dimensions['height_px']} px · {$dimensions['ppi']} PPI",
            'WebGL rendering' => $settings['webgl'] ? 'Enabled' : 'Disabled',
            'Game board' => $onOff($settings['game_board'], $settings['games']).($settings['leaderboard'] ? ' · with leaderboard' : ''),
            'Payments' => $onOff($settings['payments'], $settings['payment_methods']),
            'Extras' => implode(', ', $settings['add_ons']) ?: 'None',
        ];
    }
}
