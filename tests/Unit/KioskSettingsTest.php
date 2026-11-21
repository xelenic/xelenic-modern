<?php

use App\Support\KioskSettings;

test('screen dimensions follow the diagonal, orientation and resolution', function () {
    expect(KioskSettings::dimensions('55', 'landscape', '3840x2160'))
        ->width_cm->toBe('121.8')
        ->height_cm->toBe('68.5')
        ->width_px->toBe(3840)
        ->height_px->toBe(2160)
        ->ppi->toBe(80);

    expect(KioskSettings::dimensions('55', 'portrait', '3840x2160'))
        ->width_cm->toBe('68.5')
        ->height_cm->toBe('121.8')
        ->width_px->toBe(2160)
        ->height_px->toBe(3840);

    expect(KioskSettings::dimensions('21.5', 'portrait', '1920x1080'))
        ->width_cm->toBe('26.8')
        ->height_cm->toBe('47.6')
        ->ppi->toBe(102);
});

test('switching a section off drops its choices', function () {
    $settings = KioskSettings::fromInput([
        'screen_size' => '32',
        'orientation' => 'landscape',
        'resolution' => '1920x1080',
        'webgl' => '1',
        'game_board' => '0',
        'games' => ['Quiz'],
        'leaderboard' => '1',
        'payments' => '1',
        'payment_methods' => ['Cash'],
    ]);

    expect($settings)
        ->webgl->toBeTrue()
        ->game_board->toBeFalse()
        ->games->toBe([])
        ->leaderboard->toBeFalse()
        ->payments->toBeTrue()
        ->payment_methods->toBe(['Cash'])
        ->add_ons->toBe([]);
});

test('the summary leaves out features that are off', function () {
    $screenOnly = KioskSettings::fromInput(['screen_size' => '32', 'orientation' => 'landscape', 'resolution' => '1920x1080']);

    expect(array_keys(KioskSettings::summary($screenOnly)))->toBe(['Screen', 'Resolution']);

    $withPayments = KioskSettings::fromInput([...$screenOnly, 'payments' => '1', 'payment_methods' => ['Cash']]);

    expect(KioskSettings::summary($withPayments))->toHaveKey('Payments', 'Cash')
        ->not->toHaveKey('WebGL rendering');
});
