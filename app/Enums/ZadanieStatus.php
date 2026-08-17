<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Status zgłoszenia testowego. Odpowiada polu `zadania.status`.
 */
enum ZadanieStatus: string
{
    case DO_ZROBIENIA = 'do_zrobienia';
    case W_TOKU = 'w_toku';
    case TEST = 'test';
    case ZROBIONE = 'zrobione';

    public function label(): string
    {
        return match ($this) {
            self::DO_ZROBIENIA => 'Do zrobienia',
            self::W_TOKU => 'W toku',
            self::TEST => 'Test',
            self::ZROBIONE => 'Zrobione',
        };
    }

    /**
     * Kolejność kolumn na kanbanie.
     *
     * @return self[]
     */
    public static function ordered(): array
    {
        return [self::DO_ZROBIENIA, self::W_TOKU, self::TEST, self::ZROBIONE];
    }

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(fn (self $status) => $status->value, self::ordered());
    }

    /**
     * Lista dla frontu: [['value' => 'w_toku', 'label' => 'W toku'], ...]
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status) => ['value' => $status->value, 'label' => $status->label()],
            self::ordered()
        );
    }
}
