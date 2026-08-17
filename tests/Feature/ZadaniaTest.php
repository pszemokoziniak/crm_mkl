<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Note;
use App\Models\User;
use App\Models\Zadanie;
use App\Models\ZadanieFile;
use App\Notifications\MentionNotification;
use App\Notifications\ZadanieAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ZadaniaTest extends TestCase
{
    use RefreshDatabase;

    private User $tester;
    private User $programista;
    private User $biuro;

    protected function setUp(): void
    {
        parent::setUp();

        $accountId = Account::create(['name' => 'MKL'])->id;

        $this->tester = User::factory()->create([
            'account_id' => $accountId,
            'first_name' => 'Anna',
            'last_name' => 'Testowa',
            'email' => 'anna@example.com',
            'owner' => 3,
            'active' => 1,
        ]);

        $this->programista = User::factory()->create([
            'account_id' => $accountId,
            'first_name' => 'Piotr',
            'last_name' => 'Programista',
            'email' => 'piotr@example.com',
            'owner' => 3,
            'active' => 1,
        ]);

        $this->biuro = User::factory()->create([
            'account_id' => $accountId,
            'first_name' => 'Basia',
            'last_name' => 'Biurowa',
            'email' => 'basia@example.com',
            'owner' => 2,
            'active' => 1,
        ]);
    }

    public function test_kanban_grupuje_zgloszenia_po_statusie(): void
    {
        $this->zadanie(['title' => 'Formularz nie wysyła', 'status' => 'do_zrobienia']);
        $this->zadanie(['title' => 'Literówka w stopce', 'status' => 'test']);

        $this->actingAs($this->biuro)
            ->get('/zadania')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Zadania/Index')
                ->has('columns', 4)
                ->where('columns.0.value', 'do_zrobienia')
                ->where('columns.0.count', 1)
                ->where('columns.0.items.0.title', 'Formularz nie wysyła')
                ->where('columns.2.value', 'test')
                ->where('columns.2.count', 1)
            );
    }

    public function test_tester_zapisuje_zgloszenie_z_linkiem_i_print_screenem(): void
    {
        Storage::fake('local');
        Notification::fake();

        $response = $this->actingAs($this->tester)->post('/zadania', [
            'title' => 'Koszyk gubi pozycje',
            'description' => 'Po odświeżeniu strony koszyk jest pusty.',
            'url' => 'https://hrm.mkl.pl/koszyk',
            'status' => 'do_zrobienia',
            'priority' => 'wysoki',
            'assignee_id' => $this->programista->id,
            'screenshots' => [UploadedFile::fake()->image('screen.png')],
        ]);

        $zadanie = Zadanie::firstWhere('title', 'Koszyk gubi pozycje');

        $response->assertRedirect('/zadania/'.$zadanie->id);
        $this->assertSame('https://hrm.mkl.pl/koszyk', $zadanie->url);
        $this->assertSame($this->tester->id, $zadanie->reporter_id);

        $file = $zadanie->screenshots()->sole();
        $this->assertSame('screen.png', $file->original_name);
        // Nazwa na dysku jest losowa — ścieżki nie da się zgadnąć.
        $this->assertNotSame('zadania/'.$zadanie->id.'/screen.png', $file->path);
        Storage::disk('local')->assertExists($file->path);

        Notification::assertSentTo($this->programista, ZadanieAssignedNotification::class);
    }

    public function test_zmiana_statusu_zostawia_wpis_systemowy(): void
    {
        $zadanie = $this->zadanie(['status' => 'do_zrobienia']);

        $this->actingAs($this->programista)
            ->put('/zadania/'.$zadanie->id.'/status', ['status' => 'test'])
            ->assertRedirect();

        $this->assertSame('test', $zadanie->fresh()->status);

        $note = $zadanie->notes()->sole();
        $this->assertTrue($note->system);
        $this->assertSame('Zmiana statusu: Do zrobienia → Test', $note->body);
    }

    public function test_komentarz_z_wywolaniem_powiadamia_wspomniana_osobe(): void
    {
        Storage::fake('local');
        Notification::fake();

        $zadanie = $this->zadanie();

        $this->actingAs($this->tester)->post('/notes', [
            'type' => 'zadanie',
            'notable_id' => $zadanie->id,
            'body' => 'Sprawdź to @[Piotr Programista](user:'.$this->programista->id.') proszę.',
            'files' => [UploadedFile::fake()->image('dowod.png')],
        ])->assertRedirect();

        $note = $zadanie->notes()->sole();
        $this->assertFalse($note->system);
        $this->assertSame('dowod.png', $note->files()->sole()->original_name);

        Notification::assertSentTo($this->programista, MentionNotification::class);
        Notification::assertNotSentTo($this->tester, MentionNotification::class);
    }

    public function test_edycja_komentarza_nie_dubluje_powiadomien(): void
    {
        Notification::fake();

        $zadanie = $this->zadanie();
        $mention = '@[Piotr Programista](user:'.$this->programista->id.')';

        $this->actingAs($this->tester)->post('/notes', [
            'type' => 'zadanie',
            'notable_id' => $zadanie->id,
            'body' => 'Pytanie do '.$mention,
        ]);

        $note = $zadanie->notes()->sole();

        $this->actingAs($this->tester)
            ->put('/notes/'.$note->id, ['body' => 'Pytanie do '.$mention.' — poprawiam literówkę'])
            ->assertRedirect();

        Notification::assertSentToTimes($this->programista, MentionNotification::class, 1);
    }

    public function test_obcy_uzytkownik_nie_widzi_zgloszenia(): void
    {
        $obcy = User::factory()->create([
            'account_id' => $this->tester->account_id,
            'email' => 'obcy@example.com',
            'owner' => 3,
            'active' => 1,
        ]);

        $zadanie = $this->zadanie();

        $this->actingAs($obcy)->get('/zadania/'.$zadanie->id)->assertForbidden();

        // Na liście też go nie ma.
        $this->actingAs($obcy)
            ->get('/zadania')
            ->assertInertia(fn (Assert $page) => $page->where('columns.0.count', 0));
    }

    public function test_zalacznik_dostaje_tylko_osoba_z_dostepem(): void
    {
        Storage::fake('local');

        $zadanie = $this->zadanie();
        $file = ZadanieFile::create([
            'zadanie_id' => $zadanie->id,
            'path' => 'zadania/'.$zadanie->id.'/tajne.png',
            'original_name' => 'tajne.png',
            'mime' => 'image/png',
            'size' => 100,
            'uploaded_by' => $this->tester->id,
        ]);

        $obcy = User::factory()->create([
            'account_id' => $this->tester->account_id,
            'email' => 'ciekawski@example.com',
            'owner' => 3,
            'active' => 1,
        ]);

        $this->actingAs($obcy)
            ->get('/zadania/'.$zadanie->id.'/files/'.$file->id)
            ->assertForbidden();
    }

    public function test_print_screen_mozna_dodac_do_istniejacego_zgloszenia(): void
    {
        Storage::fake('local');

        $zadanie = $this->zadanie();

        $this->actingAs($this->programista)
            ->post('/zadania/'.$zadanie->id.'/files', [
                'screenshots' => [UploadedFile::fake()->image('po-poprawce.png')],
            ])
            ->assertRedirect();

        $file = $zadanie->screenshots()->sole();
        $this->assertSame('po-poprawce.png', $file->original_name);
        $this->assertSame($this->programista->id, $file->uploaded_by);
        Storage::disk('local')->assertExists($file->path);
    }

    public function test_obcy_uzytkownik_nie_zmieni_statusu(): void
    {
        $obcy = User::factory()->create([
            'account_id' => $this->tester->account_id,
            'email' => 'intruz@example.com',
            'owner' => 3,
            'active' => 1,
        ]);

        $zadanie = $this->zadanie(['status' => 'do_zrobienia']);

        $this->actingAs($obcy)
            ->put('/zadania/'.$zadanie->id.'/status', ['status' => 'zrobione'])
            ->assertForbidden();

        $this->assertSame('do_zrobienia', $zadanie->fresh()->status);
    }

    public function test_niepoprawny_link_i_status_nie_przechodza_walidacji(): void
    {
        $this->actingAs($this->tester)
            ->post('/zadania', [
                'title' => 'Coś nie działa',
                'url' => 'hrm.mkl.pl bez protokolu',
                'status' => 'wymyslony_status',
                'priority' => 'normalny',
            ])
            ->assertSessionHasErrors(['url', 'status']);

        $this->assertSame(0, Zadanie::count());
    }

    public function test_archiwizacja_i_przywrocenie_zgloszenia(): void
    {
        $zadanie = $this->zadanie();

        $this->actingAs($this->tester)->delete('/zadania/'.$zadanie->id)->assertRedirect('/zadania');
        $this->assertSoftDeleted($zadanie);

        $this->actingAs($this->tester)->put('/zadania/'.$zadanie->id.'/restore');
        $this->assertNull($zadanie->fresh()->deleted_at);
    }

    public function test_dyskusja_i_zalaczniki_trafiaja_do_widoku_zgloszenia(): void
    {
        $zadanie = $this->zadanie();
        Note::create([
            'user_id' => $this->biuro->id,
            'notable_type' => Zadanie::class,
            'notable_id' => $zadanie->id,
            'body' => 'Potwierdzam błąd.',
        ]);

        $this->actingAs($this->tester)
            ->get('/zadania/'.$zadanie->id)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Zadania/Show')
                ->has('notes', 1)
                ->where('notes.0.body', 'Potwierdzam błąd.')
                ->where('notes.0.author.name', 'Basia Biurowa')
                ->where('notes.0.can_edit', false)
                ->where('can.comment', true)
                ->has('mentionableUsers', 3)
            );
    }

    private function zadanie(array $attributes = []): Zadanie
    {
        return Zadanie::create(array_merge([
            'title' => 'Menu nie zwija się na mobile',
            'description' => 'Na iPhone menu zostaje otwarte.',
            'url' => 'https://hrm.mkl.pl/',
            'status' => 'do_zrobienia',
            'priority' => 'normalny',
            'reporter_id' => $this->tester->id,
            'assignee_id' => $this->programista->id,
        ], $attributes));
    }
}
