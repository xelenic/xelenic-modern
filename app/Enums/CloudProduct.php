<?php

namespace App\Enums;

/**
 * What a client can order under Cloud, each resold from InterServer.
 */
enum CloudProduct: string
{
    case Vps = 'vps';
    case Hosting = 'hosting';
    case Domain = 'domain';
    case Dedicated = 'dedicated';

    /**
     * Its brand name in Xelenic Cloud.
     */
    public function label(): string
    {
        return match ($this) {
            self::Vps => 'Vplates',
            self::Hosting => 'HostBuckets',
            self::Domain => 'Domain',
            self::Dedicated => 'DedicServer',
        };
    }

    /**
     * What it is, shown with the brand name.
     */
    public function kind(): string
    {
        return match ($this) {
            self::Vps => 'Cloud VPS',
            self::Hosting => 'Web hosting',
            self::Domain => 'Domain names',
            self::Dedicated => 'Dedicated servers',
        };
    }

    public function blurb(): string
    {
        return match ($this) {
            self::Vps => 'Linux or Windows virtual servers you size by the slice, ready in minutes.',
            self::Hosting => 'Managed hosting with cPanel or DirectAdmin for websites and email.',
            self::Domain => 'Register a new domain name for your brand or project.',
            self::Dedicated => 'Ready-built physical servers for heavy workloads, deployed fast.',
        };
    }

    /**
     * The <x-icon> for it.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Vps => 'cloud',
            self::Hosting => 'globe',
            self::Domain => 'link',
            self::Dedicated => 'monitor',
        };
    }

    /**
     * Its icon tile's colours.
     */
    public function tint(): string
    {
        return match ($this) {
            self::Vps => 'bg-sky-50 text-sky-600 ring-sky-100',
            self::Hosting => 'bg-violet-50 text-violet-600 ring-violet-100',
            self::Domain => 'bg-emerald-50 text-emerald-600 ring-emerald-100',
            self::Dedicated => 'bg-amber-50 text-amber-600 ring-amber-100',
        };
    }

    /**
     * How its billing period is worded: VPS, hosting and servers bill monthly;
     * domains, yearly.
     */
    public function periodUnit(): string
    {
        return $this === self::Domain ? 'year' : 'month';
    }
}
