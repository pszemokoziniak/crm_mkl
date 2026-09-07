<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Role użytkownika. Odpowiada polu `users.owner`.
 *
 * Uwaga na nazwy: KIEROWNIK to kierownik budowy (widzi tylko swoje budowy),
 * KIEROWNICTWO to kierownictwo firmy — inna rola, mimo podobnej nazwy.
 * Kierownictwo ma na razie dokładnie ten sam zakres co biuro.
 */
enum Role: int
{
    case ADMIN = 1;
    case BIURO = 2;
    case KIEROWNIK = 3;
    case KIEROWNICTWO = 4;

    /** Role z pełnym dostępem (biurowym) — widzą wszystkie budowy. */
    public const OFFICE = [self::ADMIN, self::BIURO, self::KIEROWNICTWO];

    /**
     * @return int[]
     */
    public static function officeValues(): array
    {
        return array_map(fn (self $rola) => $rola->value, self::OFFICE);
    }

    /**
     * Wszystkie dopuszczalne wartości pola owner — do walidacji formularza.
     *
     * @return int[]
     */
    public static function values(): array
    {
        return array_map(fn (self $rola) => $rola->value, self::cases());
    }

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::BIURO => 'Biuro',
            self::KIEROWNIK => 'Kierownik budowy',
            self::KIEROWNICTWO => 'Kierownictwo',
        };
    }
}
