<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\DostepPracownikaMail;
use App\Models\Account;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\DostepPracownika;
use App\Models\Funkcja;
use App\Models\Organization;
use App\Models\User;
use App\Models\WniosekUrlopowy;
use App\Models\ZgloszenieKierownika;
use App\Notifications\WniosekUrlopowyNotification;
use App\Notifications\ZgloszenieKierownikaNotification;
use App\Services\UrlopyBezWniosku;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Strona pracownika na telefon: osobisty link + PIN, wniosek urlopowy,
 * zatwierdzenie przez kierownika, zgłoszenie do kadr, wydanie linku z karty.
 */
class PortalPracownikaTest extends TestCase
{
    use RefreshDatabase;

    private int $accountId;
    private Organization $budowa;
    private Contact $pracownik;
    private User $kierownik;
    private User $biuro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->accountId = Account::create(['name' => 'MKL'])->id;
        Funkcja::create(['id' => Funkcja::KIEROWNIK, 'name' => 'Kierownik Budowy', 'kierownictwo' => true, 'rola_budowy' => Funkcja::ROLA_KIEROWNIK]);

        $this->budowa = Organization::create(['account_id' => $this->accountId, 'nazwaBud' => 'Zeitz']);
        $this->pracownik = Contact::create(['account_id' => $this->accountId, 'first_name' => 'Jan', 'last_name' => 'Kowalski', 'email' => 'jan@example.com', 'phone' => '600 100 200']);
        ContactWorkDate::create(['contact_id' => $this->pracownik->id, 'organization_id' => $this->budowa->id, 'start' => now()->subMonth()->toDateString(), 'end' => null]);

        $this->kierownik = $this->user(3, 'kb@mkl.pl');
        $szef = Contact::create(['account_id' => $this->accountId, 'first_name' => 'Adam', 'last_name' => 'Szef', 'funkcja_id' => Funkcja::KIEROWNIK, 'user_id' => $this->kierownik->id]);
        ContactWorkDate::create(['contact_id' => $szef->id, 'organization_id' => $this->budowa->id, 'start' => now()->subMonth()->toDateString(), 'end' => null]);

        $this->biuro = $this->user(2, 'biuro@mkl.pl');
    }

    private function user(int $owner, string $email): User
    {
        return User::factory()->create([
            'account_id' => $this->accountId, 'email' => $email,
            'owner' => $owner, 'active' => 1, 'password_changed_at' => now()->toDateTimeString(),
        ]);
    }

    /** Wydany link z ustawionym PIN-em i otwartą sesją. */
    private function zalogowanyToken(string $pin = '1234'): string
    {
        $token = DostepPracownika::wydaj($this->pracownik, $this->biuro);
        $this->post("/u/$token/pin", ['pin' => $pin, 'pin_confirmation' => $pin])->assertRedirect("/u/$token");

        return $token;
    }

    public function test_link_widac_raz_a_w_bazie_jest_tylko_skrot(): void
    {
        $odp = $this->actingAs($this->biuro)->post('/contacts/'.$this->pracownik->id.'/dostep')->assertRedirect();
        $link = session('nowy_link');
        $this->assertStringStartsWith(config('app.url').'/u/', $link);
        $token = substr($link, strrpos($link, '/') + 1);
        $this->assertSame(48, strlen($token));
        $this->assertSame(DostepPracownika::skrot($token), DostepPracownika::sole()->token_hash);
        $this->assertStringNotContainsString($token, DostepPracownika::sole()->token_hash);

        $this->get("/u/$token")->assertOk()->assertInertia(fn ($p) => $p->component('Portal/Pin')->where('ustawianie', true));
        $this->get('/u/'.str_repeat('x', 48))->assertOk()->assertInertia(fn ($p) => $p->component('Portal/Brak'));
    }

    public function test_pin_ustawiany_raz_zly_pin_blokuje_po_pieciu_probach(): void
    {
        $token = DostepPracownika::wydaj($this->pracownik, $this->biuro);

        $this->post("/u/$token/pin", ['pin' => '12', 'pin_confirmation' => '12'])->assertSessionHasErrors('pin');
        $this->post("/u/$token/pin", ['pin' => '1234', 'pin_confirmation' => '1234'])->assertRedirect("/u/$token");
        $this->get("/u/$token")->assertInertia(fn ($p) => $p->component('Portal/Wnioski')->where('pracownik.budowa', 'Zeitz'));

        // Nowa przeglądarka: PIN wymagany, link sam nie wystarcza.
        $this->flushSession();
        $this->get("/u/$token")->assertInertia(fn ($p) => $p->component('Portal/Pin')->where('ustawianie', false));
        foreach (range(1, 5) as $i) {
            $this->post("/u/$token/pin", ['pin' => '9999'])->assertSessionHasErrors('pin');
        }
        $this->post("/u/$token/pin", ['pin' => '1234'])->assertSessionHasErrors('pin');
        $this->assertTrue(DostepPracownika::sole()->jestZablokowany());
        $this->get("/u/$token")->assertInertia(fn ($p) => $p->where('zablokowany', true));
    }

    public function test_nowy_link_uniewaznia_stary_a_pin_zostaje(): void
    {
        $stary = $this->zalogowanyToken();
        $this->flushSession();
        $nowy = DostepPracownika::wydaj($this->pracownik, $this->biuro);

        $this->get("/u/$stary")->assertInertia(fn ($p) => $p->component('Portal/Brak'));
        $this->post("/u/$nowy/pin", ['pin' => '1234'])->assertRedirect("/u/$nowy");
    }

    public function test_pracownik_sklada_wniosek_kierownik_dostaje_dzwonek(): void
    {
        Notification::fake();
        $token = $this->zalogowanyToken();

        $this->post("/u/$token/wniosek", ['rodzaj' => 'UW', 'od' => now()->addDays(10)->toDateString(), 'do' => now()->addDays(14)->toDateString(), 'uwaga' => 'Wesele'])
            ->assertRedirect("/u/$token");

        $w = WniosekUrlopowy::sole();
        $this->assertSame('zlozony', $w->status);
        $this->assertSame($this->pracownik->id, (int) $w->contact_id);
        Notification::assertSentTo($this->kierownik, WniosekUrlopowyNotification::class);

        $this->get("/u/$token")->assertInertia(fn ($p) => $p->has('wnioski', 1)->where('wnioski.0.status_label', 'czeka na kierownika'));

        // Wstecz i "do" przed "od" odpadają; bez sesji nie da się złożyć.
        $this->post("/u/$token/wniosek", ['rodzaj' => 'UW', 'od' => now()->subDay()->toDateString(), 'do' => now()->toDateString()])->assertSessionHasErrors('od');
        $this->flushSession();
        $this->post("/u/$token/wniosek", ['rodzaj' => 'UW', 'od' => now()->addDay()->toDateString(), 'do' => now()->addDay()->toDateString()])->assertForbidden();
    }

    public function test_kierownik_zatwierdza_i_powstaje_zgloszenie_do_kadr_liczone_jako_wniosek(): void
    {
        Notification::fake();
        $kadry = $this->user(6, 'kadry@mkl.pl');
        $token = $this->zalogowanyToken();
        $od = now()->addDays(10)->toDateString();
        $do = now()->addDays(12)->toDateString();
        $this->post("/u/$token/wniosek", ['rodzaj' => 'UW', 'od' => $od, 'do' => $do]);
        $w = WniosekUrlopowy::sole();

        // Pulpit kierownika ma wniosek; cudzy kierownik go nie rozpatrzy.
        $this->assertCount(1, $this->actingAs($this->kierownik)->get('/')->viewData('page')['props']['wnioski_urlopowe']);
        $this->actingAs($this->user(3, 'obcy@mkl.pl'))->put('/wnioski-urlopowe/'.$w->id, ['status' => 'zatwierdzony'])->assertForbidden();

        $this->actingAs($this->kierownik)->put('/wnioski-urlopowe/'.$w->id, ['status' => 'zatwierdzony', 'odpowiedz' => 'OK'])->assertRedirect();

        $w->refresh();
        $this->assertSame('zatwierdzony', $w->status);
        $this->assertSame($this->kierownik->id, (int) $w->rozpatrzyl_id);
        $z = ZgloszenieKierownika::sole();
        $this->assertSame('urlop', $z->rodzaj);
        $this->assertSame($w->id, (int) $z->wniosek_id);
        $this->assertSame($this->budowa->id, (int) $z->organization_id);
        $this->assertStringContainsString('Wniosek z telefonu', $z->uwaga);
        Notification::assertSentTo($kadry, ZgloszenieKierownikaNotification::class);

        // Dni z wniosku nie są "urlopem bez wniosku", choć skanu nie ma.
        $uw = DB::table('shift_status')->insertGetId(['title' => 'Urlop Wypoczynkowy', 'code' => 'UW']);
        DB::table('building_time_sheets')->insert(['organization_id' => $this->budowa->id, 'contact_id' => $this->pracownik->id, 'work_day' => $od, 'shift_status_id' => $uw]);
        $this->assertCount(0, app(UrlopyBezWniosku::class)->dla(null, $od, $do));

        // Drugi raz nie da się rozpatrzyć; pracownik widzi decyzję.
        $this->actingAs($this->kierownik)->put('/wnioski-urlopowe/'.$w->id, ['status' => 'odrzucony'])->assertStatus(422);
        $this->get("/u/$token")->assertInertia(fn ($p) => $p->where('wnioski.0.status_label', 'zatwierdzony')->where('wnioski.0.odpowiedz', 'OK'));
    }

    public function test_karta_pracownika_wysyla_link_mailem_i_uniewaznia(): void
    {
        Mail::fake();
        $this->actingAs($this->kierownik)->get('/contacts/'.$this->pracownik->id.'/dostep')->assertForbidden();

        $this->actingAs($this->biuro)->get('/contacts/'.$this->pracownik->id.'/dostep')->assertOk()
            ->assertInertia(fn ($p) => $p->component('Contacts/Dostep')->where('dostep', null)->where('sms_dostepny', false));

        $this->actingAs($this->biuro)->post('/contacts/'.$this->pracownik->id.'/dostep/mail')->assertRedirect();
        Mail::assertSent(DostepPracownikaMail::class, fn ($m) => $m->hasTo('jan@example.com') && str_contains($m->adres, '/u/'));
        $this->assertNotNull(DostepPracownika::sole()->wyslany_mail_at);

        $this->actingAs($this->biuro)->post('/contacts/'.$this->pracownik->id.'/dostep/sms')->assertStatus(422);

        $this->actingAs($this->biuro)->delete('/contacts/'.$this->pracownik->id.'/dostep')->assertRedirect();
        $this->assertSame(0, DostepPracownika::count());
    }
}
