<?php

declare(strict_types=1);

namespace App\Enums;

enum PostPillar: string
{
    case MAINPAGE = 'mainpage';
    case HEALTH_EMPLOYMENT = 'health_employment';
    case HEALTH_ECOSYSTEMS = 'health_ecosystems';
    case HEALTH_ENTREPRENEURSHIP = 'health_entrepreneurship';

    public function label(): string
    {
        return match ($this) {
            self::MAINPAGE => __('Main Page'),
            self::HEALTH_EMPLOYMENT => __('Health Employment'),
            self::HEALTH_ECOSYSTEMS => __('Health Ecosystems'),
            self::HEALTH_ENTREPRENEURSHIP => __('Health Entrepreneurship'),
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case) => $case->value, self::cases());
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
