<?php

namespace App\Cloud;

use App\Enums\CloudProduct;
use App\Models\Setting;

/**
 * What the team sets for Cloud in the admin panel: which products clients
 * can order, and the markup on InterServer's price for each.
 */
final class CloudSettings
{
    /**
     * Markup, in percent, until the team sets one.
     */
    public const DEFAULT_MARKUP = 30;

    public static function markup(CloudProduct $product): float
    {
        return (float) Setting::get("cloud.markup.{$product->value}", self::DEFAULT_MARKUP);
    }

    public static function enabled(CloudProduct $product): bool
    {
        return (bool) Setting::get("cloud.enabled.{$product->value}", true);
    }

    /**
     * The client's price for something InterServer charges $cost for.
     */
    public static function price(CloudProduct $product, float $cost): float
    {
        return round($cost * (1 + self::markup($product) / 100), 2);
    }

    /**
     * Billing periods (months) InterServer offers, with its discount.
     *
     * @return array<int, int> months => percent off
     */
    public static function periods(): array
    {
        return [1 => 0, 6 => 5, 12 => 10, 24 => 15, 36 => 20];
    }
}
