<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Factory\BuildTimeShiftFactory;
use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Organization;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Obsada w KCP budowy (ekran i eksport do Excela).
 *
 * Zawężenie do wyświetlanego miesiąca było zakomentowane, więc zestawienie
 * brało każdego, kto kiedykolwiek był na tej budowie — na Lausitzer Zeitz
 * dawało to 22 wiersze zamiast 12, w większości puste.
 */
class KcpBudowyTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private Organization $budowa;
    private User $biuro;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;

        $this->biuro = User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'biuro@mkl.pl',
            'owner' => 2, 'active' => 1, 'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->budowa = Organization::create([
            'account_id' => $this->accountId, 'nazwaBud' => 'Lausitzer Zeitz',
        ]);
    }

    private function pracownik(string $nazwisko, ?string $od, ?string $do): Contact
    {
        $osoba = Contact::create([
            'account_id' => $this->accountId, 'first_name' => 'Jan', 'last_name' => $nazwisko,
        ]);

        if ($od !== null) {
            ContactWorkDate::create([
                'contact_id' => $osoba->id,
                'organization_id' => $this->budowa->id,
                'start' => $od,
                'end' => $do,
            ]);
        }

        return $osoba;
    }

    private function godziny(Contact $osoba, string $dzien): void
    {
        DB::table('building_time_sheets')->insert([
            'organization_id' => $this->budowa->id,
            'contact_id' => $osoba->id,
            'work_day' => $dzien,
            'work_from' => $dzien.' 07:00:00',
            'work_to' => $dzien.' 15:00:00',
            'effective_work_time' => '08:00',
        ]);
    }

    /** @return array<int, string> nazwiska w KCP za wrzesień 2026 */
    private function obsada(): array
    {
        $shifts = (array) BuildTimeShiftFactory::create($this->budowa->id, '2026-09-15');

        return collect($shifts)
            ->map(fn ($dni) => reset($dni)->name)
            ->values()->all();
    }

    public function test_pobyt_zakonczony_przed_miesiacem_znika_z_zestawienia(): void
    {
        $this->pracownik('Obecny', '2026-09-01', '2026-09-30');
        $this->pracownik('Bylny', '2026-05-01', '2026-06-30');

        $obsada = $this->obsada();

        $this->assertContains('Obecny Jan', $obsada);
        $this->assertNotContains('Bylny Jan', $obsada, 'Zjechał z budowy trzy miesiące wcześniej.');
    }

    public function test_pobyt_zaczynajacy_sie_po_miesiacu_jeszcze_nie_wchodzi(): void
    {
        $this->pracownik('Przyszly', '2026-11-02', '2026-12-20');

        $this->assertNotContains('Przyszly Jan', $this->obsada());
    }

    public function test_pobyt_bez_daty_konca_zostaje(): void
    {
        // Na tym wykładała się poprzednia próba zawężenia: porównanie `end`
        // wprost gubiło tych, którzy nadal są na budowie.
        $this->pracownik('Nadal', '2026-08-01', null);

        $this->assertContains('Nadal Jan', $this->obsada());
    }

    public function test_wpisane_godziny_zostaja_nawet_po_zakonczeniu_pobytu(): void
    {
        // Inaczej wypełniony miesiąc zniknąłby z zestawienia razem z nazwiskiem.
        $osoba = $this->pracownik('Zjechal', '2026-05-01', '2026-06-30');
        $this->godziny($osoba, '2026-09-03');

        $this->assertContains('Zjechal Jan', $this->obsada());
    }

    public function test_usuniety_pracownik_nie_wraca_na_liste(): void
    {
        $this->pracownik('Usuniety', '2026-09-01', null)->delete();

        $this->assertNotContains('Usuniety Jan', $this->obsada());
    }

    public function test_nazwisko_stoi_przed_imieniem_niezaleznie_od_wpisow(): void
    {
        // Dzień z wpisem brał "Imię Nazwisko", dzień pusty "Nazwisko Imię",
        // więc w jednym pliku nazwiska szły w dwóch porządkach.
        $zWpisem = $this->pracownik('Kowalski', '2026-09-01', null);
        $this->godziny($zWpisem, '2026-09-01');
        $this->pracownik('Nowak', '2026-09-01', null);

        $obsada = $this->obsada();

        $this->assertContains('Kowalski Jan', $obsada);
        $this->assertContains('Nowak Jan', $obsada);
    }

    public function test_suma_godzin_w_eksporcie_jest_dodatnia(): void
    {
        // Carbon 3 zwraca różnicę ze znakiem: 07:15 minus północ dawało
        // -435 minut, więc suma godzin w pliku wychodziła ujemna.
        $osoba = $this->pracownik('Godzinowy', '2026-09-01', null);
        foreach (['2026-09-02', '2026-09-03'] as $dzien) {
            DB::table('building_time_sheets')->insert([
                'organization_id' => $this->budowa->id,
                'contact_id' => $osoba->id,
                'work_day' => $dzien,
                'work_from' => $dzien.' 07:00:00',
                'work_to' => $dzien.' 14:15:00',
                'effective_work_time' => '07:15',
            ]);
        }

        $odpowiedz = $this->actingAs($this->biuro)
            ->get('/building/'.$this->budowa->id.'/time-sheet/export?date=2026-09-15');
        $odpowiedz->assertOk();

        $arkusz = \PhpOffice\PhpSpreadsheet\IOFactory::load($odpowiedz->getFile()->getPathname())->getActiveSheet();
        $liczby = [];
        foreach ($arkusz->getRowIterator() as $wiersz) {
            foreach ($wiersz->getCellIterator() as $komorka) {
                if (is_numeric($komorka->getValue())) {
                    $liczby[] = (float) $komorka->getValue();
                }
            }
        }

        $this->assertContains(14.5, $liczby, 'Suma 2 × 7:15 = 14,5 h.');
        $this->assertNotContains(-14.5, $liczby);
    }

    public function test_eksport_budowy_bez_nikogo_daje_pusty_plik_a_nie_blad(): void
    {
        // Liczba kolumn dni brała się z pierwszego pracownika — przy pustej
        // obsadzie reset() dawał false, a count(false) kończył eksport błędem 500.
        $odpowiedz = $this->actingAs($this->biuro)
            ->get('/building/'.$this->budowa->id.'/time-sheet/export?date=2026-09-15');

        $odpowiedz->assertOk();
        $this->assertStringContainsString(
            'KCP Lausitzer Zeitz 2026-09.xlsx',
            $odpowiedz->headers->get('content-disposition')
        );

        $arkusz = \PhpOffice\PhpSpreadsheet\IOFactory::load($odpowiedz->getFile()->getPathname())->getActiveSheet();
        // Wrzesień ma 30 dni: kolumny dni od D po dwie (dzień n w kolumnie
        // 4 + 2·(n−1)), więc 30. dzień w BJ, a suma zaraz za nim, w BL.
        $this->assertSame("1\nwt", $arkusz->getCell('D7')->getValue());
        $this->assertSame("30\nśr", $arkusz->getCell('BJ7')->getValue());
        $this->assertSame('SUMA', $arkusz->getCell('BL7')->getValue());
        $this->assertNull($arkusz->getCell('B8')->getValue(), 'Bez pracowników nie ma wierszy z nazwiskami.');
    }

    public function test_plik_nazywa_sie_od_budowy_i_miesiaca(): void
    {
        $this->pracownik('Obecny', '2026-09-01', null);

        $odpowiedz = $this->actingAs($this->biuro)
            ->get('/building/'.$this->budowa->id.'/time-sheet/export?date=2026-09-15');

        $odpowiedz->assertOk();
        $this->assertStringContainsString(
            'KCP Lausitzer Zeitz 2026-09.xlsx',
            $odpowiedz->headers->get('content-disposition')
        );
    }
}
