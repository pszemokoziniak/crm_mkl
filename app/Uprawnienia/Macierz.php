<?php

declare(strict_types=1);

namespace App\Uprawnienia;

use App\Enums\Role;
use App\Enums\Uprawnienie;
use App\Models\UprawnieniaRoli;
use App\Models\User;
use App\Models\ZmianaUprawnien;
use Illuminate\Support\Facades\DB;

/**
 * Kto co może: rola → lista uprawnień. Jedyne miejsce, które o tym
 * decyduje; trasy i widoki pytają `User::moze()`, nie numerów ról.
 *
 * Domyślne zakresy siedzą w kodzie (`domyslneDla`). Ekran Ustawienia →
 * Uprawnienia ról zapisuje nadpisania w tabeli `uprawnienia_rol`: wiersz
 * dla roli zastępuje jej domyślną listę w całości, brak wiersza = domyślne.
 * Admin ma zawsze wszystko i nie da się mu tego odebrać — inaczej ekran
 * mógłby zamknąć drzwi samemu sobie.
 */
final class Macierz
{
    /** @var array<int, Uprawnienie[]>|null nadpisania z bazy, raz na żądanie */
    private static ?array $nadpisania = null;

    /**
     * @return Uprawnienie[]
     */
    public static function dla(Role $rola): array
    {
        if ($rola === Role::ADMIN) {
            return Uprawnienie::cases();
        }

        return self::nadpisania()[$rola->value] ?? self::domyslneDla($rola);
    }

    public static function ma(Role $rola, Uprawnienie $uprawnienie): bool
    {
        return in_array($uprawnienie, self::dla($rola), true);
    }

    public static function jestNadpisana(Role $rola): bool
    {
        return array_key_exists($rola->value, self::nadpisania());
    }

    /**
     * @return Uprawnienie[]
     */
    public static function domyslneDla(Role $rola): array
    {
        if ($rola === Role::ADMIN) {
            return Uprawnienie::cases();
        }

        return self::domyslne()[$rola->value] ?? [];
    }

    /**
     * Zapisuje pełną listę roli. Zależności domyka sam (edycja → podgląd),
     * uprawnień "tylko admin" nie nadaje nigdy, a różnicę wobec stanu sprzed
     * zapisu odkłada do dziennika.
     *
     * @param Uprawnienie[] $uprawnienia
     */
    public static function zapisz(Role $rola, array $uprawnienia, ?User $kto): void
    {
        if ($rola === Role::ADMIN) {
            return;
        }

        $nowe = array_values(array_filter(self::domknij($uprawnienia), fn (Uprawnienie $u) => ! $u->tylkoAdmin()));
        $stare = self::dla($rola);

        DB::transaction(function () use ($rola, $nowe, $stare, $kto) {
            UprawnieniaRoli::updateOrCreate(
                ['rola' => $rola->value],
                ['uprawnienia' => self::wartosci($nowe), 'user_id' => $kto?->id],
            );
            self::zapiszZmiane($rola, $stare, $nowe, $kto, false);
        });

        self::zapomnij();
    }

    public static function przywrocDomyslne(Role $rola, ?User $kto): void
    {
        if ($rola === Role::ADMIN || ! self::jestNadpisana($rola)) {
            return;
        }

        $stare = self::dla($rola);
        $nowe = self::domyslneDla($rola);

        DB::transaction(function () use ($rola, $stare, $nowe, $kto) {
            UprawnieniaRoli::where('rola', $rola->value)->delete();
            self::zapiszZmiane($rola, $stare, $nowe, $kto, true);
        });

        self::zapomnij();
    }

    /**
     * Dokłada wszystko, co podane uprawnienia zakładają (przechodnio).
     *
     * @param Uprawnienie[] $uprawnienia
     * @return Uprawnienie[]
     */
    public static function domknij(array $uprawnienia): array
    {
        $wynik = [];
        $kolejka = array_values($uprawnienia);
        while ($kolejka) {
            $u = array_shift($kolejka);
            if (in_array($u, $wynik, true)) {
                continue;
            }
            $wynik[] = $u;
            foreach ($u->wymaga() as $w) {
                $kolejka[] = $w;
            }
        }

        // W kolejności z enumu, żeby porównania list nie zależały od kolejności klikania.
        return array_values(array_filter(Uprawnienie::cases(), fn (Uprawnienie $u) => in_array($u, $wynik, true)));
    }

    /** Czyści pamięć podręczną nadpisań — po zapisie i w testach. */
    public static function zapomnij(): void
    {
        self::$nadpisania = null;
    }

    /**
     * @return array<int, Uprawnienie[]>
     */
    private static function nadpisania(): array
    {
        if (self::$nadpisania === null) {
            self::$nadpisania = [];
            foreach (UprawnieniaRoli::all() as $wiersz) {
                self::$nadpisania[(int) $wiersz->rola] = array_values(array_filter(
                    array_map(fn ($v) => Uprawnienie::tryFrom((string) $v), $wiersz->uprawnienia ?? []),
                ));
            }
        }

        return self::$nadpisania;
    }

    /**
     * @param Uprawnienie[] $stare
     * @param Uprawnienie[] $nowe
     */
    private static function zapiszZmiane(Role $rola, array $stare, array $nowe, ?User $kto, bool $przywrocenie): void
    {
        $dodane = array_values(array_filter($nowe, fn (Uprawnienie $u) => ! in_array($u, $stare, true)));
        $odebrane = array_values(array_filter($stare, fn (Uprawnienie $u) => ! in_array($u, $nowe, true)));

        if (! $dodane && ! $odebrane && ! $przywrocenie) {
            return;
        }

        ZmianaUprawnien::create([
            'rola' => $rola->value,
            'user_id' => $kto?->id,
            'dodane' => self::wartosci($dodane),
            'odebrane' => self::wartosci($odebrane),
            'przywrocenie' => $przywrocenie,
            'created_at' => now(),
        ]);
    }

    /**
     * @param Uprawnienie[] $lista
     * @return string[]
     */
    private static function wartosci(array $lista): array
    {
        return array_map(fn (Uprawnienie $u) => $u->value, $lista);
    }

    /**
     * Zakresy sprzed wprowadzenia tej warstwy: biuro, kadry i kierownictwo —
     * to samo; kierownik budowy i kierownik projektu — to samo.
     *
     * @return array<int, Uprawnienie[]>
     */
    private static function domyslne(): array
    {
        $kierownik = [
            Uprawnienie::BUDOWY_PODGLAD,
            Uprawnienie::KARTOTEKI_PODGLAD,
            Uprawnienie::DOKUMENTY_PODGLAD,
            Uprawnienie::NIEOBECNOSCI_PODGLAD,
            Uprawnienie::KCP_WPISYWANIE,
            Uprawnienie::SPRZET_PODGLAD,
            Uprawnienie::PROGNOZA_PODGLAD,
            Uprawnienie::RAPORT_TERMINOW,
            Uprawnienie::STATYSTYKI,
        ];

        // Biuro: wszystko poza tym, co należy do administratora systemu.
        $biuro = array_values(array_filter(
            Uprawnienie::cases(),
            fn (Uprawnienie $u) => ! $u->tylkoAdmin() && ! in_array($u, [
                Uprawnienie::SLOWNIKI_SYSTEMOWE,
                Uprawnienie::BAZA_WIEDZY_PISANIE,
            ], true),
        ));

        return [
            Role::BIURO->value => $biuro,
            Role::KIEROWNICTWO->value => $biuro,
            Role::KADRY->value => $biuro,
            Role::KIEROWNIK->value => $kierownik,
            Role::KIEROWNIK_PROJEKTU->value => $kierownik,
        ];
    }
}
