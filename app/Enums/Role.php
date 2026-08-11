<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Role użytkownika. Odpowiada polu `users.owner`.
 */
enum Role: int
{
    case ADMIN = 1;
    case BIURO = 2;
    case KIEROWNIK = 3;

    /** Role z pełnym dostępem (biuro) — widzą wszystkie budowy. */
    public const OFFICE = [self::ADMIN, self::BIURO];

    /**
     * @return int[]
     */
    public static function officeValues(): array
    {
        return [self::ADMIN->value, self::BIURO->value];
    }
}
