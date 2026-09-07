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

    /** Model wpisu, do którego można przypiąć dokument tego typu. */
    public function modelWpisu(): string
    {
        return match ($this) {
            self::BADANIA => \App\Models\Badania::class,
            self::BHP => \App\Models\Bhp::class,
            self::UPRAWNIENIA => \App\Models\Uprawnienia::class,
            self::A1 => \App\Models\A1::class,
            self::PBIOZ => \App\Models\Pbioz::class,
        };
    }

    /**
     * Opis wpisu na liście wyboru: rodzaj i okres ważności, żeby przy kilku
     * badaniach tego samego typu dało się wskazać właściwe.
     */
    public function opisWpisu($wpis): string
    {
        $rodzaj = match ($this) {
            self::BADANIA => optional($wpis->badaniaTyp)->name,
            self::BHP => optional($wpis->bhpTyp)->name,
            self::UPRAWNIENIA => optional($wpis->uprawnieniaTyp)->name,
            self::A1 => optional($wpis->kraj)->name,
            self::PBIOZ => $wpis->name,
        };

        $okres = trim(($wpis->start ?? '?').' → '.($wpis->end ?? '?'));

        return trim(($rodzaj ? $rodzaj.' · ' : '').$okres);
    }

    /** Relacja do dociągnięcia, żeby opis nie robił zapytania na wiersz. */
    public function relacjaRodzaju(): ?string
    {
        return match ($this) {
            self::BADANIA => 'badaniaTyp',
            self::BHP => 'bhpTyp',
            self::UPRAWNIENIA => 'uprawnieniaTyp',
            self::A1 => 'kraj',
            self::PBIOZ => null,
        };
    }

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
