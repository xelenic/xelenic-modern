<?php

namespace App\Enums;

/**
 * What a client can ask Xelenic to build. Each type asks a few extra
 * questions of its own; the answers are stored in the project's details.
 */
enum ProjectType: string
{
    case KioskApp = 'kiosk-app';
    case SoftwareSolution = 'software-solution';
    case HireEngineers = 'hire-engineers';
    case Ecommerce = 'ecommerce';
    case MobileApp = 'mobile-app';
    case GrowBusiness = 'grow-business';

    public function label(): string
    {
        return match ($this) {
            self::KioskApp => 'Kiosk App',
            self::SoftwareSolution => 'Software Solution',
            self::HireEngineers => 'Hire Our Engineers',
            self::Ecommerce => 'Ecommerce',
            self::MobileApp => 'Mobile App Development',
            self::GrowBusiness => 'Grow Your Business',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::KioskApp => 'AI-powered self-service kiosks for ordering, check-in and payments.',
            self::SoftwareSolution => 'CRM, ERP, POS, automation and payroll built around how you work.',
            self::HireEngineers => 'Add experienced Xelenic engineers to your team, full-time or part-time.',
            self::Ecommerce => 'An online store that is fast, secure and easy to manage.',
            self::MobileApp => 'iOS and Android apps for your customers or your team.',
            self::GrowBusiness => 'Technology and marketing strategy to bring in more customers.',
        };
    }

    /**
     * The <x-icon> name shown for this type.
     */
    public function icon(): string
    {
        return match ($this) {
            self::KioskApp => 'kiosk',
            self::SoftwareSolution => 'monitor',
            self::HireEngineers => 'code',
            self::Ecommerce => 'cart',
            self::MobileApp => 'phone',
            self::GrowBusiness => 'trending',
        };
    }

    /**
     * The extra, optional questions for this type. `type` is one of number,
     * text, url, select or checkboxes; select and checkboxes list their options.
     *
     * @return array<int, array{name: string, label: string, type: string, options?: array<int, string>, placeholder?: string}>
     */
    public function questions(): array
    {
        return match ($this) {
            self::KioskApp => [
                ['name' => 'kiosk_count', 'label' => 'How many kiosks do you need?', 'type' => 'number'],
                ['name' => 'features', 'label' => 'What should the kiosks do?', 'type' => 'checkboxes', 'options' => [
                    'Self-ordering', 'Check-in & queueing', 'Wayfinding & maps', 'AI assistant', 'Loyalty & rewards',
                ]],
            ],
            self::SoftwareSolution => [
                ['name' => 'systems', 'label' => 'Which systems do you need?', 'type' => 'checkboxes', 'options' => [
                    'CRM', 'ERP', 'POS', 'Business automation', 'Payroll', 'Something custom',
                ]],
                ['name' => 'team_size', 'label' => 'How many people will use it?', 'type' => 'select', 'options' => [
                    '1–10', '11–50', '51–200', 'More than 200',
                ]],
            ],
            self::HireEngineers => [
                ['name' => 'engineers', 'label' => 'How many engineers do you need?', 'type' => 'number'],
                ['name' => 'skills', 'label' => 'Which skills should they have?', 'type' => 'text', 'placeholder' => 'e.g. Laravel, React, Flutter'],
                ['name' => 'engagement', 'label' => 'How would you like to work together?', 'type' => 'select', 'options' => [
                    'Full-time', 'Part-time', 'For one project',
                ]],
            ],
            self::Ecommerce => [
                ['name' => 'starting_point', 'label' => 'Where are you starting from?', 'type' => 'select', 'options' => [
                    'A brand-new store', 'Moving from Shopify', 'Moving from WooCommerce', 'Improving our current store', 'Not sure yet',
                ]],
                ['name' => 'product_count', 'label' => 'Roughly how many products?', 'type' => 'select', 'options' => [
                    'Under 100', '100–1,000', 'More than 1,000',
                ]],
            ],
            self::MobileApp => [
                ['name' => 'platforms', 'label' => 'Which platforms?', 'type' => 'checkboxes', 'options' => [
                    'iOS', 'Android',
                ]],
                ['name' => 'design', 'label' => 'Do you have designs already?', 'type' => 'select', 'options' => [
                    'Yes, ready to build', 'Some sketches or ideas', 'No, we need design too',
                ]],
            ],
            self::GrowBusiness => [
                ['name' => 'goals', 'label' => 'What are your main goals?', 'type' => 'checkboxes', 'options' => [
                    'More leads', 'More online sales', 'Marketing automation', 'Better analytics & reporting', 'Brand awareness',
                ]],
                ['name' => 'website', 'label' => 'Your current website', 'type' => 'url', 'placeholder' => 'https://'],
            ],
        };
    }
}
