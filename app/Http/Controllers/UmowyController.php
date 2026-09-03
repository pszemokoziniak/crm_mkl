<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Generowanie umów i aneksów z danych pracownika.
 * Wersja pierwsza: jeden szablon, podgląd do druku (zapis do PDF przez
 * przeglądarkę) i pobranie pliku .doc otwieranego w Wordzie.
 */
class UmowyController extends Controller
{
    private const RODZAJE = [
        'umowa' => 'Umowa o pracę',
        'aneks' => 'Aneks do umowy o pracę',
        'oddelegowanie' => 'Oddelegowanie do pracy na budowie',
    ];

    /** Formularz: dane z systemu wypełnione, reszta do uzupełnienia. */
    public function formularz(Contact $contact): InertiaResponse
    {
        $pobyt = $this->pobytDoUmowy($contact);

        return Inertia::render('Umowy/Formularz', [
            'contact' => [
                'id' => $contact->id,
                'imie_nazwisko' => trim($contact->first_name.' '.$contact->last_name),
                'pesel' => $contact->pesel,
                'adres' => $contact->address,
                'stanowisko' => optional($contact->funkcja)->name,
            ],
            'rodzaje' => collect(self::RODZAJE)->map(fn ($label, $value) => [
                'value' => $value,
                'label' => $label,
            ])->values(),
            // Parametry z adresu mają pierwszeństwo — tak wchodzi się tu
            // z rejestru zmian kadrowych, z danymi konkretnego przeniesienia.
            'domyslne' => [
                'rodzaj' => Request::query('rodzaj') ?: ($pobyt ? 'aneks' : 'umowa'),
                'budowa' => Request::query('budowa') ?: optional(optional($pobyt)->organization)->nazwaBud,
                'od' => Request::query('od') ?: (optional($pobyt)->start ?? Carbon::today()->toDateString()),
                'do' => Request::query('do') ?: optional($pobyt)->end,
                'miejsce' => 'Grudziądz',
                'data_zawarcia' => Carbon::today()->toDateString(),
                'stanowisko' => Request::query('stanowisko') ?: optional($contact->funkcja)->name,
                'uwagi' => Request::query('uwagi'),
            ],
            'budowy' => Organization::orderBy('nazwaBud')->get(['id', 'nazwaBud'])->map->only('id', 'nazwaBud'),
        ]);
    }

    /** Podgląd gotowy do druku — z niego użytkownik zapisuje PDF. */
    public function podglad(Contact $contact): Response
    {
        return response($this->render($contact, true))
            ->header('Content-Type', 'text/html; charset=utf-8');
    }

    /** Plik .doc: HTML w opakowaniu, które Word otwiera i pozwala edytować. */
    public function doc(Contact $contact): Response
    {
        $nazwa = Str::slug($this->dane($contact)['tytul'].'-'.$contact->last_name.'-'.$contact->first_name).'.doc';

        return response("\xEF\xBB\xBF".$this->render($contact, false, true))
            ->header('Content-Type', 'application/msword; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="'.$nazwa.'"');
    }

    private function render(Contact $contact, bool $pokazPasek = false, bool $doWorda = false): string
    {
        // ltrim: bez pustych linii na starcie edytory rozpoznają plik jako HTML.
        return ltrim(view('umowy.umowa', $this->dane($contact) + [
            'pokazPasek' => $pokazPasek,
            'logo' => $this->logo($doWorda),
            'doWorda' => $doWorda,
        ])->render());
    }

    /**
     * @return array<string, mixed>
     */
    private function dane(Contact $contact): array
    {
        $rodzaj = Request::query('rodzaj', 'umowa');
        $pobyt = $this->pobytDoUmowy($contact);

        $od = Request::query('od') ?: optional($pobyt)->start;
        $do = Request::query('do') ?: optional($pobyt)->end;
        $budowa = Request::query('budowa') ?: optional(optional($pobyt)->organization)->nazwaBud;

        return [
            'tytul' => self::RODZAJE[$rodzaj] ?? self::RODZAJE['umowa'],
            'pracodawca' => Request::query('pracodawca', 'MKL-BAU Sp. z o.o.'),
            'miejsce' => Request::query('miejsce', 'Grudziądz'),
            'dataZawarcia' => Request::query('data_zawarcia') ?: Carbon::today()->toDateString(),
            'pracownik' => [
                'imie_nazwisko' => trim($contact->first_name.' '.$contact->last_name),
                'pesel' => $contact->pesel,
                'adres' => $contact->address,
            ],
            'stanowisko' => Request::query('stanowisko') ?: optional($contact->funkcja)->name,
            'budowa' => $budowa,
            'od' => $od ?: '—',
            'do' => $do ?: 'czas nieokreślony',
            'wynagrodzenie' => Request::query('wynagrodzenie'),
            'wstep' => $this->wstep($rodzaj),
            'pozostaleWarunki' => Request::query('warunki')
                ?: 'Pozostałe warunki umowy pozostają bez zmian. W sprawach nieuregulowanych zastosowanie mają przepisy Kodeksu pracy.',
            'uwagi' => Request::query('uwagi'),
            'wygenerowano' => Carbon::now()->format('d.m.Y H:i'),
        ];
    }

    /**
     * W podglądzie logo wklejamy jako dane — zawsze się pokaże, także bez sieci.
     * Do Worda idzie zwykły odnośnik, bo edytory tekstu nie rozumieją base64
     * i pokazują w jego miejscu ramkę z surowym napisem "data:image...".
     */
    private function logo(bool $doWorda): ?string
    {
        $sciezka = public_path('img/MKL-BAU.png');

        if (! is_file($sciezka)) {
            return null;
        }

        // Adres z konfiguracji, nie z bieżącego żądania — plik trafia do Worda
        // i musi wskazywać na serwer, a nie na localhost.
        return $doWorda
            ? rtrim((string) config('app.url'), '/').'/img/MKL-BAU.png'
            : 'data:image/png;base64,'.base64_encode((string) file_get_contents($sciezka));
    }

    private function wstep(string $rodzaj): string
    {
        return [
            'umowa' => 'Strony zawierają umowę o pracę na warunkach określonych poniżej.',
            'aneks' => 'Strony zgodnie zmieniają warunki dotychczasowej umowy o pracę w zakresie określonym poniżej.',
            'oddelegowanie' => 'Pracodawca kieruje pracownika do wykonywania pracy na wskazanej budowie, na warunkach określonych poniżej.',
        ][$rodzaj] ?? 'Strony zawierają umowę o pracę na warunkach określonych poniżej.';
    }

    /** Pobyt, z którego bierzemy budowę i termin — trwający, a jak nie ma, to ostatni. */
    private function pobytDoUmowy(Contact $contact): ?ContactWorkDate
    {
        $dzis = Carbon::today()->toDateString();

        // withTrashed: budowa może być już zarchiwizowana, a jej nazwa i tak
        // powinna trafić do dokumentu.
        return ContactWorkDate::with(['organization' => fn ($query) => $query->withTrashed()])
            ->where('contact_id', $contact->id)
            ->orderByRaw('CASE WHEN start <= ? AND (end IS NULL OR end >= ?) THEN 0 ELSE 1 END', [$dzis, $dzis])
            ->orderByDesc('start')
            ->first();
    }
}
