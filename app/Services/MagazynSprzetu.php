<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Narzedzia;
use App\Models\ToolWorkDate;
use App\Services\DocumentService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

/**
 * Jeden opis stanu magazynu dla wszystkich widoków sprzętu.
 *
 * Sprzęt liczymy sztukami: jeden rekord to jedna maszyna albo jeden kontener,
 * ze swoim numerem seryjnym i badaniami. O tym, czy sztuka jest wolna,
 * decyduje wyłącznie przypisanie do budowy — nie licznik w kolumnie, bo ten
 * potrafił się rozjechać, gdy przypisanie kończyło się datą.
 */
class MagazynSprzetu
{
    /** Relacje potrzebne do opisania sztuk — bez nich sypie się N+1. */
    public function relacje(): array
    {
        return [
            'typ',
            'files' => fn ($query) => $query->where('type', 'photo')->orderBy('id'),
            'toolWorkDates.organization',
        ];
    }

    /**
     * Dwa poziomy: kategoria (np. "Kontener") zbiera modele, a te — sztuki.
     * Sprzęt bez kategorii pokazuje się wprost jako swój model.
     *
     * @param  Collection<int, Narzedzia>  $sztuki
     * @return array<int, array<string, mixed>>
     */
    public function grupy(Collection $sztuki, ?string $dzien = null): array
    {
        $dzien = $dzien ?? Carbon::today()->toDateString();

        return $sztuki
            ->groupBy(fn (Narzedzia $n) => optional($n->typ)->kategoria ?: 'model:'.$this->nazwaModelu($n))
            ->map(function ($wKategorii, $klucz) use ($dzien) {
                $modele = $wKategorii
                    ->groupBy(fn (Narzedzia $n) => $this->nazwaModelu($n))
                    ->map(fn ($grupa, $nazwa) => $this->model($grupa, $nazwa, $dzien))
                    ->sortBy('nazwa', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values();

                $bezKategorii = str_starts_with((string) $klucz, 'model:');

                return [
                    'klucz' => (string) $klucz,
                    'nazwa' => $bezKategorii ? $modele->first()['nazwa'] : (string) $klucz,
                    'ma_modele' => ! $bezKategorii,
                    'photo' => $modele->firstWhere('photo', '!=', null)['photo'] ?? null,
                    'sztuk' => $modele->sum('sztuk'),
                    'dostepne' => $modele->sum('dostepne'),
                    'na_budowie' => $modele->sum('na_budowie'),
                    'badania_uwaga' => $modele->sum('badania_uwaga'),
                    'badania_po_terminie' => $modele->sum('badania_po_terminie'),
                    'badania_wkrotce' => $modele->sum('badania_wkrotce'),
                    'modele' => $modele,
                ];
            })
            ->sortBy('nazwa', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /** Przypisanie, które jeszcze trwa — stare wpisy bez dat liczą się jako trwające. */
    public function trwajacePrzypisanie(Narzedzia $narzedzia, ?string $dzien = null): ?ToolWorkDate
    {
        $dzien = $dzien ?? Carbon::today()->toDateString();

        return $narzedzia->toolWorkDates
            ->filter(fn (ToolWorkDate $t) => (int) $t->narzedzia_nb > 0)
            ->first(fn (ToolWorkDate $t) => $t->end === null || (string) $t->end >= $dzien);
    }

    /**
     * Część dat badań to zaślepki z importu — traktujemy je jak brak daty.
     */
    public function dataBadan(Narzedzia $narzedzia): ?string
    {
        $data = $narzedzia->waznosc_badan;

        if (! $data || $data->year < 2000 || $data->year > 2100) {
            return null;
        }

        return $data->format('Y-m-d');
    }

    public function statusBadan(Narzedzia $narzedzia, ?string $dzien = null): string
    {
        $dzien = $dzien ?? Carbon::today()->toDateString();
        $data = $this->dataBadan($narzedzia);

        if (! $data) {
            return 'brak';
        }

        if ($data < $dzien) {
            return 'po_terminie';
        }

        return $data <= Carbon::parse($dzien)->addDays(30)->toDateString() ? 'wkrotce' : 'wazne';
    }

    /**
     * @return array<string, mixed>
     */
    public function sztuka(Narzedzia $narzedzia, ?string $dzien = null): array
    {
        $dzien = $dzien ?? Carbon::today()->toDateString();
        $pobyt = $this->trwajacePrzypisanie($narzedzia, $dzien);

        return [
            'id' => $narzedzia->id,
            'numer_seryjny' => $narzedzia->numer_seryjny ?: null,
            'waznosc_badan' => $this->dataBadan($narzedzia),
            'badania_status' => $this->statusBadan($narzedzia, $dzien),
            'photo' => $this->miniaturka($narzedzia),
            'budowa' => $pobyt && $pobyt->organization ? [
                'przypisanie_id' => $pobyt->id,
                'id' => $pobyt->organization->id,
                'nazwaBud' => $pobyt->organization->nazwaBud,
                'od' => $pobyt->start ? (string) $pobyt->start : null,
                'do' => $pobyt->end ? (string) $pobyt->end : null,
            ] : null,
        ];
    }

    /** Miniaturka pierwszego zdjęcia sprzętu — skalowana przez Glide. */
    public function miniaturka(Narzedzia $narzedzia): ?string
    {
        $photo = $narzedzia->files->first();

        if (! $photo) {
            return null;
        }

        return URL::route('image', [
            'path' => DocumentService::toolFilePath($narzedzia->id, $photo->filename),
            'w' => 96,
            'h' => 96,
            'fit' => 'crop',
        ]);
    }

    private function nazwaModelu(Narzedzia $narzedzia): string
    {
        return optional($narzedzia->typ)->name ?: (string) $narzedzia->name;
    }

    /**
     * @param  Collection<int, Narzedzia>  $grupa
     * @return array<string, mixed>
     */
    private function model(Collection $grupa, string $nazwa, string $dzien): array
    {
        $opisane = $grupa->map(fn (Narzedzia $n) => $this->sztuka($n, $dzien))->values();

        return [
            'klucz' => 'model:'.$nazwa,
            'nazwa' => $nazwa,
            'photo' => $this->miniaturka($grupa->first(fn (Narzedzia $n) => $n->files->isNotEmpty()) ?? $grupa->first()),
            'sztuk' => $opisane->count(),
            'na_budowie' => $opisane->where('budowa', '!=', null)->count(),
            'dostepne' => $opisane->whereNull('budowa')->count(),
            // Rozdzielone, bo "po terminie" i "kończy się" to dwie różne pilności.
            'badania_po_terminie' => $opisane->where('badania_status', 'po_terminie')->count(),
            'badania_wkrotce' => $opisane->where('badania_status', 'wkrotce')->count(),
            'badania_uwaga' => $opisane->whereIn('badania_status', ['po_terminie', 'wkrotce'])->count(),
            'sztuki' => $opisane,
        ];
    }
}
