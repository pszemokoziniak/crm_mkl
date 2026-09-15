<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Badania;
use App\Models\BadaniaTyp;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Wpis zastąpiony nowszym tego samego rodzaju to nie zaległość, tylko
 * historia. Pulpit odróżniał to od 10 września, lista wpisów na karcie
 * pracownika wciąż nie: badanie ważne do 2028 stało obok wygasłego w 2024,
 * oba tak samo, a to drugie świeciło na czerwono.
 *
 * Niczego nie kasujemy — przy kontroli liczy się, czy ktoś BYŁ przebadany
 * w danym miesiącu, a razem z wpisem zniknąłby skan.
 */
class ZastapioneWpisyTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private User $biuro;
    private Contact $pracownik;
    private BadaniaTyp $okresowe;
    private BadaniaTyp $wysokosciowe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountId = Account::create(['name' => 'MKL'])->id;

        $this->biuro = User::factory()->create([
            'account_id' => $this->accountId, 'email' => 'biuro@mkl.pl',
            'owner' => 2, 'active' => 1, 'password_changed_at' => now()->toDateTimeString(),
        ]);

        $this->pracownik = Contact::create([
            'account_id' => $this->accountId, 'first_name' => 'Jan', 'last_name' => 'Kowalski',
        ]);

        $this->okresowe = BadaniaTyp::create(['name' => 'badanie okresowe']);
        $this->wysokosciowe = BadaniaTyp::create(['name' => 'badanie wysokościowe']);
    }

    private function badanie(BadaniaTyp $typ, string $od, ?string $do): Badania
    {
        return Badania::create([
            'contact_id' => $this->pracownik->id,
            'badaniaTyp_id' => $typ->id,
            'start' => $od,
            'end' => $do,
        ]);
    }

    /**
     * Wpisy z listy, po id. Z rozwiniętą historią, bo te sprawdzenia dotyczą
     * oznaczania wpisów, a nie ich chowania — zwinięcie ma własne testy.
     *
     * @return array<int, array<string, mixed>>
     */
    private function lista(): array
    {
        return collect(
            $this->actingAs($this->biuro)
                ->get('/contacts/'.$this->pracownik->id.'/badania?historia=with')
                ->viewData('page')['props']['bads']['data']
        )->keyBy('id')->all();
    }

    public function test_starszy_wpis_tego_samego_rodzaju_jest_oznaczony(): void
    {
        // Pracownik 29 ze zgłoszenia: aktualne badanie i jego poprzednik.
        $stare = $this->badanie($this->okresowe, '2022-06-13', '2024-06-13');
        $nowe = $this->badanie($this->okresowe, '2026-06-02', '2028-06-02');

        $lista = $this->lista();

        $this->assertTrue($lista[$stare->id]['zastapione']);
        $this->assertFalse($lista[$nowe->id]['zastapione'], 'To ten wpis obowiązuje.');
    }

    public function test_inny_rodzaj_badania_niczego_nie_zastepuje(): void
    {
        // Wysokościowe nie zastępuje okresowego — to dwa różne obowiązki.
        $okresowe = $this->badanie($this->okresowe, '2022-06-13', '2024-06-13');
        $this->badanie($this->wysokosciowe, '2026-01-01', '2028-01-01');

        $this->assertFalse($this->lista()[$okresowe->id]['zastapione']);
    }

    public function test_przeterminowany_bez_nastepcy_zostaje_zalegloscia(): void
    {
        // To nie jest historia, tylko luka do uzupełnienia — ma dalej kłuć w oczy.
        $samotne = $this->badanie($this->okresowe, '2022-06-13', '2024-06-13');

        $this->assertFalse($this->lista()[$samotne->id]['zastapione']);
    }

    public function test_wpis_bez_daty_konca_nie_jest_zastepowany(): void
    {
        $bezterminowe = $this->badanie($this->okresowe, '2020-01-01', null);
        $this->badanie($this->okresowe, '2026-06-02', '2028-06-02');

        $this->assertFalse($this->lista()[$bezterminowe->id]['zastapione'], 'Nie ma czego porównać.');
    }

    public function test_nic_nie_znika_z_bazy(): void
    {
        // Sedno decyzji: oznaczamy, nie kasujemy — skan jest dowodem.
        $stare = $this->badanie($this->okresowe, '2022-06-13', '2024-06-13');
        $this->badanie($this->okresowe, '2026-06-02', '2028-06-02');

        $this->assertCount(2, $this->lista());
        $this->assertNull(Badania::find($stare->id)->deleted_at);
    }

    public function test_historia_jest_domyslnie_zwinieta(): void
    {
        $stare = $this->badanie($this->okresowe, '2022-06-13', '2024-06-13');
        $nowe = $this->badanie($this->okresowe, '2026-06-02', '2028-06-02');

        $props = $this->actingAs($this->biuro)
            ->get('/contacts/'.$this->pracownik->id.'/badania')
            ->viewData('page')['props'];

        $widoczne = collect($props['bads']['data'])->pluck('id');

        $this->assertContains($nowe->id, $widoczne);
        $this->assertNotContains($stare->id, $widoczne, 'Historia chowa się pod przyciskiem.');
        $this->assertSame(1, $props['ukrytych'], 'Przycisk musi powiedzieć, ile jest schowane.');
    }

    public function test_przycisk_rozwija_historie(): void
    {
        $stare = $this->badanie($this->okresowe, '2022-06-13', '2024-06-13');
        $this->badanie($this->okresowe, '2026-06-02', '2028-06-02');

        $props = $this->actingAs($this->biuro)
            ->get('/contacts/'.$this->pracownik->id.'/badania?historia=with')
            ->viewData('page')['props'];

        $this->assertContains($stare->id, collect($props['bads']['data'])->pluck('id'));
        $this->assertSame(0, $props['ukrytych']);
    }

    public function test_zalegly_wpis_nie_chowa_sie_nigdy(): void
    {
        // Brak do uzupełnienia musi zostać na wierzchu, nawet zwinięty widok
        // ma go pokazać.
        $samotne = $this->badanie($this->okresowe, '2022-06-13', '2024-06-13');

        $props = $this->actingAs($this->biuro)
            ->get('/contacts/'.$this->pracownik->id.'/badania')
            ->viewData('page')['props'];

        $this->assertContains($samotne->id, collect($props['bads']['data'])->pluck('id'));
        $this->assertSame(0, $props['ukrytych']);
    }

    public function test_formularz_mowi_ze_wpis_tego_rodzaju_juz_jest(): void
    {
        $this->badanie($this->okresowe, '2022-06-13', '2024-06-13');
        $this->badanie($this->wysokosciowe, '2026-01-01', null);

        $props = $this->actingAs($this->biuro)
            ->get('/contacts/'.$this->pracownik->id.'/badania/create')
            ->viewData('page')['props'];

        $istniejace = $props['istniejace'];

        $this->assertSame('2024-06-13', $istniejace[$this->okresowe->id]['do']);
        $this->assertFalse($istniejace[$this->okresowe->id]['bezterminowy']);
        $this->assertTrue($istniejace[$this->wysokosciowe->id]['bezterminowy'], 'Wpis bez daty końca obowiązuje bezterminowo.');
    }

    public function test_formularz_milczy_o_rodzajach_ktorych_nie_ma(): void
    {
        $this->badanie($this->okresowe, '2022-06-13', '2024-06-13');

        $props = $this->actingAs($this->biuro)
            ->get('/contacts/'.$this->pracownik->id.'/badania/create')
            ->viewData('page')['props'];

        $this->assertArrayNotHasKey($this->wysokosciowe->id, $props['istniejace']);
    }

    public function test_kilka_pokolen_wpisow_oznacza_wszystkie_poza_najnowszym(): void
    {
        $pierwsze = $this->badanie($this->okresowe, '2020-01-01', '2022-01-01');
        $drugie = $this->badanie($this->okresowe, '2022-01-02', '2024-01-02');
        $trzecie = $this->badanie($this->okresowe, '2024-01-03', '2026-01-03');
        $aktualne = $this->badanie($this->okresowe, '2026-01-04', '2028-01-04');

        $lista = $this->lista();

        foreach ([$pierwsze, $drugie, $trzecie] as $wpis) {
            $this->assertTrue($lista[$wpis->id]['zastapione']);
        }

        $this->assertFalse($lista[$aktualne->id]['zastapione']);
    }
}
