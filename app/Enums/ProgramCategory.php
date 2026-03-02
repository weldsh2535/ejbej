<?php

namespace App\Enums;

enum ProgramCategory: string
{
    case HEALTH_EMPLOYMENT = 'health_employment';
    case HEALTH_ENTREPRENEURSHIP = 'health_entrepreneurship';
    case HEALTH_ECOSYSTEMS = 'health_ecosystems';
    case UNCATEGORIZED = 'uncategorized';

    public function label(): string
    {
        return match ($this) {
            self::HEALTH_EMPLOYMENT => __('Health Employment'),
            self::HEALTH_ENTREPRENEURSHIP => __('Health Entrepreneurship'),
            self::HEALTH_ECOSYSTEMS => __('Health Ecosystems'),
            self::UNCATEGORIZED => __('Uncategorized'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $category) => [$category->value => $category->label()])
            ->toArray();
    }
}
