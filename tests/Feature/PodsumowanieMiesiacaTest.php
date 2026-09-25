<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Tests\TestCase;

/**
 * Raport miesięczny: zakładka "Podsumowanie".
 *
 * Raport dawał samą sumę godzin, więc urlopy, zwolnienia i odbiory godzin
 * trzeba było zliczać z kratek dzień po dniu, a kadry i tak musiały szukać
 * po budowach, na których pracownik był wcześniej w tym samym miesiącu.
 */
class PodsumowanieMiesiacaTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private User $biuro;
    private Organization $budowa;
    private int $urlopId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;

        $this->biuro = User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'biuro@mkl.pl',
            'owner' => 2, 'active' => 1, 'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->budowa = Organization::create([
            'account_id' => $this->accountId, 'nazwaBud' => 'Siemens Łódź',
        ]);

        $this->urlopId = DB::table('shift_status')->insertGetId([
            'code' => 'UW', 'title' => 'Urlop Wypoczynkowy',
        ]);
    }

    private function pracownik(string $nazwisko): Contact
    {
        $osoba = Contact::create([
            'account_id' => $this->accountId, 'first_name' => 'Jan', 'last_name' => $nazwisko,
        ]);

        ContactWorkDate::create([
            'contact_id' => $osoba->id,
            'organization_id' => $this->budowa->id,
            'start' => '2026-09-01',
            'end' => null,
        ]);

        return $osoba;
    }

    private function wpis(Contact $osoba, string $dzien, ?string $czas, ?int $status = null): void
    {
        DB::table('building_time_sheets')->insert([
            'organization_id' => $this->budowa->id,
            'contact_id' => $osoba->id,
            'work_day' => $dzien,
            'effective_work_time' => $czas,
            'shift_status_id' => $status,
        ]);
    }

    private function podsumowanie(): Worksheet
    {
        $odpowiedz = $this->actingAs($this->biuro)
            ->get('/building/time-sheet/general-report?date=2026-09-15');

        $odpowiedz->assertOk();

        return IOFactory::load($this->sciezkaPliku($odpowiedz))->getSheetByName('Podsumowanie');
    }

    /** Eksport schodzi jako gotowy plik, nie jako strumień. */
    private function sciezkaPliku($odpowiedz): string
    {
        return $odpowiedz->baseResponse->getFile()->getPathname();
    }

    /** @return array<int, array<int, mixed>> wiersze z nagłówkiem w [0] */
    private function tabela(Worksheet $arkusz): array
    {
        return array_values(array_filter(
            $arkusz->rangeToArray('A3:N30'),
            fn ($wiersz) => trim((string) $wiersz[1]) !== ''
        ));
    }

    public function test_podsumowanie_liczy_dni_pracy_i_godziny(): void
    {
        $osoba = $this->pracownik('Kielak');
        $this->wpis($osoba, '2026-09-01', '08:30');
        $this->wpis($osoba, '2026-09-02', '08:30');
        $this->wpis($osoba, '2026-09-03', '10:00');

        $tabela = $this->tabela($this->podsumowanie());
        $naglowek = $tabela[0];
        $wiersz = $tabela[1];

        $this->assertSame('Nazwisko', $naglowek[1]);
        $this->assertSame('Dni pracy', $naglowek[4]);
        $this->assertSame('Godziny', $naglowek[5]);

        $this->assertSame('Kielak', $wiersz[1]);
        $this->assertSame('Siemens Łódź', $wiersz[3], 'Widać, gdzie ten miesiąc został przepracowany.');
        $this->assertSame(3, (int) $wiersz[4]);
        $this->assertEqualsWithDelta(27.0, (float) $wiersz[5], 0.01);
    }

    public function test_kazdy_rodzaj_nieobecnosci_ma_wlasna_kolumne(): void
    {
        $osoba = $this->pracownik('Nowak');
        $this->wpis($osoba, '2026-09-01', '08:00');
        $this->wpis($osoba, '2026-09-02', null, $this->urlopId);
        $this->wpis($osoba, '2026-09-03', null, $this->urlopId);

        $tabela = $this->tabela($this->podsumowanie());
        $kolumnaUW = array_search('UW', $tabela[0], true);

        $this->assertNotFalse($kolumnaUW, 'Kolumna powstaje dla rodzajów, które w tym miesiącu padły.');
        $this->assertSame(2, (int) $tabela[1][$kolumnaUW]);
        $this->assertSame(1, (int) $tabela[1][4], 'Urlop nie jest dniem pracy.');
    }

    public function test_pominiety_pracownik_na_wypelnianej_budowie_jest_widoczny(): void
    {
        // Dotąd taka osoba w ogóle nie trafiała do raportu i brak wypełnionego
        // KCP wyglądał jak brak pracownika.
        $wypelniony = $this->pracownik('Kielak');
        $this->wpis($wypelniony, '2026-09-01', '08:00');
        $this->pracownik('Zapomniany');

        $tabela = $this->tabela($this->podsumowanie());
        $wiersz = collect($tabela)->firstWhere(1, 'Zapomniany');
        $kolumnaUwag = array_search('Uwagi', $tabela[0], true);

        $this->assertNotNull($wiersz);
        $this->assertSame(0, (int) $wiersz[4]);
        $this->assertStringContainsString('brak wpisów', (string) $wiersz[$kolumnaUwag]);
    }

    public function test_budowa_bez_zadnego_wpisu_idzie_do_notki_a_nie_do_tabeli(): void
    {
        // Inaczej raport za wrzesień otwierał się 150 nazwiskami z budów,
        // na których KCP w ogóle nie ruszono, i gubił wiersze, o które chodzi.
        $wypelniony = $this->pracownik('Kielak');
        $this->wpis($wypelniony, '2026-09-01', '08:00');

        $pusta = Organization::create([
            'account_id' => $this->accountId, 'nazwaBud' => 'Valmet Ortofta',
        ]);
        $ktos = Contact::create([
            'account_id' => $this->accountId, 'first_name' => 'Jan', 'last_name' => 'Nietkniety',
        ]);
        ContactWorkDate::create([
            'contact_id' => $ktos->id, 'organization_id' => $pusta->id,
            'start' => '2026-09-01', 'end' => null,
        ]);

        $arkusz = $this->podsumowanie();
        $tekst = implode(' ', array_map(
            fn ($wiersz) => implode(' ', array_map(fn ($k) => (string) $k, $wiersz)),
            $arkusz->rangeToArray('A1:N60')
        ));

        $this->assertNull(collect($this->tabela($arkusz))->firstWhere(1, 'Nietkniety'));
        $this->assertStringContainsString('Budowy bez żadnego wpisu', $tekst);
        $this->assertStringContainsString('Valmet Ortofta', $tekst);
    }

    public function test_wiersz_razem_sumuje_wszystkich(): void
    {
        $a = $this->pracownik('Kielak');
        $b = $this->pracownik('Wołoszka');
        $this->wpis($a, '2026-09-01', '08:00');
        $this->wpis($b, '2026-09-01', '08:30');

        $tabela = $this->tabela($this->podsumowanie());
        $razem = collect($tabela)->firstWhere(1, 'RAZEM');

        $this->assertNotNull($razem);
        $this->assertSame(2, (int) $razem[4]);
        $this->assertEqualsWithDelta(16.5, (float) $razem[5], 0.01);
    }

    public function test_kierownik_nie_pobiera_raportu_wszystkich_budow(): void
    {
        // Dlatego odnośnik z ekranu KCP budowy widzi tylko biuro: raport
        // obejmuje wszystkie budowy, także cudze.
        $kierownik = User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'kierownik@mkl.pl',
            'owner' => 3, 'active' => 1, 'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->actingAs($kierownik)
            ->get('/building/time-sheet/general-report?date=2026-09-15')
            ->assertForbidden();
    }

    public function test_plik_ma_obie_zakladki_i_otwiera_sie_na_podsumowaniu(): void
    {
        $osoba = $this->pracownik('Kielak');
        $this->wpis($osoba, '2026-09-01', '08:00');

        $odpowiedz = $this->actingAs($this->biuro)
            ->get('/building/time-sheet/general-report?date=2026-09-15');

        $skoroszyt = IOFactory::load($this->sciezkaPliku($odpowiedz));

        $this->assertSame(['Dni miesiąca', 'Podsumowanie'], $skoroszyt->getSheetNames());
        $this->assertSame('Podsumowanie', $skoroszyt->getActiveSheet()->getTitle());

        // Suma godzin w siatce dni ma być liczbą, żeby dało się ją zsumować.
        $this->assertIsFloat((float) $skoroszyt->getSheetByName('Dni miesiąca')->getCell('A3')->getValue());
        $this->assertSame(8.0, (float) $skoroszyt->getSheetByName('Dni miesiąca')->getCell('A3')->getValue());
    }

    public function test_dni_miesiaca_pokazuje_numer_budowy_ukosnik_i_kolory(): void
    {
        $b504 = Organization::create(['account_id' => $this->accountId, 'nazwaBud' => 'Bud 504']);
        $b507 = Organization::create(['account_id' => $this->accountId, 'nazwaBud' => 'Bud 507']);
        DB::table('organizations')->where('id', $b504->id)->update(['numerBud' => '504']);
        DB::table('organizations')->where('id', $b507->id)->update(['numerBud' => '507']);

        $osoba = Contact::create(['account_id' => $this->accountId, 'first_name' => 'Jan', 'last_name' => 'Kowalski']);
        $wpis = function ($org, string $dzien, ?string $czas, ?int $status = null) use ($osoba) {
            DB::table('building_time_sheets')->insert([
                'organization_id' => $org, 'contact_id' => $osoba->id,
                'work_day' => $dzien, 'effective_work_time' => $czas, 'shift_status_id' => $status,
            ]);
        };

        $wpis($b504->id, '2026-09-01', '08:00');               // dzień 1 → 504
        $wpis($b504->id, '2026-09-02', '04:00');               // dzień 2 → 504/507
        $wpis($b507->id, '2026-09-02', '04:00');
        $wpis($b504->id, '2026-09-03', null, $this->urlopId);  // dzień 3 → UW
        // dzień 4 bez wpisu → pusty (szary)

        $odpowiedz = $this->actingAs($this->biuro)->get('/building/time-sheet/general-report?date=2026-09-15');
        $odpowiedz->assertOk();
        $arkusz = IOFactory::load($this->sciezkaPliku($odpowiedz))->getSheetByName('Dni miesiąca');

        $this->assertSame('504', (string) $arkusz->getCell('D3')->getValue());
        $this->assertEqualsCanonicalizing(['504', '507'], explode('/', (string) $arkusz->getCell('E3')->getValue()));
        $this->assertSame('UW', (string) $arkusz->getCell('F3')->getValue());
        $this->assertSame('', (string) $arkusz->getCell('G3')->getValue());

        $this->assertStringContainsStringIgnoringCase('ffff00', $arkusz->getStyle('F3')->getFill()->getStartColor()->getARGB());
        $this->assertStringContainsStringIgnoringCase('d9d9d9', $arkusz->getStyle('G3')->getFill()->getStartColor()->getARGB());
        $barwaPracy = strtolower($arkusz->getStyle('D3')->getFill()->getStartColor()->getARGB());
        $this->assertStringNotContainsString('ffff00', $barwaPracy);
        $this->assertStringNotContainsString('d9d9d9', $barwaPracy);
    }
}

