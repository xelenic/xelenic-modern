<?php

namespace App\Cloud;

use App\Cloud\Products\Dedicated;
use App\Cloud\Products\Domain;
use App\Cloud\Products\Hosting;
use App\Cloud\Products\Vps;
use App\Enums\CloudProduct;

/**
 * The Product that handles each kind of cloud service.
 */
final class Products
{
    public static function for(CloudProduct $product): Product
    {
        return app(match ($product) {
            CloudProduct::Vps => Vps::class,
            CloudProduct::Hosting => Hosting::class,
            CloudProduct::Domain => Domain::class,
            CloudProduct::Dedicated => Dedicated::class,
        });
    }
}
