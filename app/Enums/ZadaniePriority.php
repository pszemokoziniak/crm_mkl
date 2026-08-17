<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Priorytet zgłoszenia. Odpowiada polu `zadania.priority`.
 */
enum ZadaniePriority: string
{
    case NISKI = 'niski';
    case NORMALNY = 'normalny';
    case WYSOKI = 'wysoki';

    public function label(): string
    {
        return match ($this) {
            self::NISKI => 'Niski',
            self::NORMALNY => 'Normalny',
            self::WYSOKI => 'Wysoki',
        };
    }

    /**
     * @return self[]
     */
    public static function ordered(): array
    {
        return [self::WYSOKI, self::NORMALNY, self::NISKI];
    }

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(fn (self $priority) => $priority->value, self::ordered());
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $priority) => ['value' => $priority->value, 'label' => $priority->label()],
            self::ordered()
        );
    }
}
