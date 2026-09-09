<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Funkcja extends Model
{
    public const KIEROWNIK = 1;
    public const INZYNIER = 6;

    /** Stanowisko opiekuna kontraktu — szukamy po nazwie ze słownika, nie po id. */
    public const NAZWA_KIEROWNIK_PROJEKTU = 'Kierownik Projektu';

    /** Kolumny listy budów, do których stanowisko może kierować osobę. */
    public const ROLA_KIEROWNIK = 'kierownik';
    public const ROLA_INZYNIER = 'inzynier';
    public const ROLA_KIEROWNIK_PROJEKTU = 'kierownik_projektu';

    /** @var array<string, string> podpisy do słownika i formularzy */
    public const ROLE_BUDOWY = [
        self::ROLA_KIEROWNIK => 'Kierownik budowy',
        self::ROLA_INZYNIER => 'Inżynier',
        self::ROLA_KIEROWNIK_PROJEKTU => 'Kierownik projektu',
    ];

    protected $fillable = [
        'name',
        'kierownictwo',
        'rola_budowy',
    ];

    protected $casts = [
        'kierownictwo' => 'boolean',
    ];

    public function contact()
    {
        return $this->hasMany(Contact::class);
    }

    public function funkcjas()
    {
        return $funkcjas = Funkcja::all();
    }

    /**
     * Stanowiska, które mogą wejść do kierownictwa budowy.
     * Lista jest w bazie (znacznik przy funkcji), nie w kodzie — biuro
     * zmienia ją samo w Ustawieniach.
     *
     * @return int[]
     */
    /**
     * Id stanowiska "Kierownik Projektu" ze słownika (/funkcja).
     * Po nazwie, bo id bywa inne na produkcji i lokalnie.
     */
    public static function kierownikProjektuId(): ?int
    {
        return static::where('name', static::NAZWA_KIEROWNIK_PROJEKTU)->value('id');
    }

    public static function kierownictwoIds(): array
    {
        return static::where('kierownictwo', true)->pluck('id')->all();
    }

    /**
     * Stanowiska wchodzące w kierownictwo budowy — obie kolumny naraz.
     *
     * Tego zbioru używa zakres dostępu kierownika: kto może wejść na budowę
     * i zobaczyć jej ludzi. Kierownik projektu jest poza nim celowo — jego
     * budowy biorą się z pola przy budowie, nie z kierownictwa.
     *
     * @return int[]
     */
    public static function idsKierownictwaBudowy(): array
    {
        return static::whereIn('rola_budowy', [self::ROLA_KIEROWNIK, self::ROLA_INZYNIER])
            ->pluck('id')->all();
    }

    /**
     * Stanowiska kierujące do danej kolumny listy budów.
     *
     * Zwracamy identyfikatory, bo zapytania o kolumny i tak filtrują po
     * `contacts.funkcja_id`. Pusta lista znaczy, że nikt nie jest przypisany —
     * kolumna wyjdzie pusta, zamiast pokazać przypadkowych ludzi.
     *
     * @return int[]
     */
    public static function idsDlaRoli(string $rola): array
    {
        return static::where('rola_budowy', $rola)->pluck('id')->all();
    }
}
