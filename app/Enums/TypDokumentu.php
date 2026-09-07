<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Typy dokumentów ze słownika dokumenty_typs. Identyfikatory były wpisane
 * na sztywno w pięciu kontrolerach ('1', '2', '3'...), przez co nie dało się
 * powiedzieć z kodu, co znaczy "3".
 */
enum TypDokumentu: int
{
    case BADANIA = 1;
    case BHP = 2;
    case UPRAWNIENIA = 3;
    case A1 = 4;
    case PBIOZ = 5;

    public function label(): string
    {
        return match ($this) {
            self::BADANIA => 'Badania Lekarskie',
            self::BHP => 'Szkolenia BHP',
            self::UPRAWNIENIA => 'Uprawnienia',
            self::A1 => 'A1',
            self::PBIOZ => 'PBiOZ',
        };
    }
}
