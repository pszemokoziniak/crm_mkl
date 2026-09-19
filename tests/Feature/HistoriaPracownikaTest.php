<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HistoriaPracownikaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Dzień KCP siedzi w bazie jako datetime; historia ma pokazywać samą
     * datę, a nie „2026-09-01 00:00:00”.
     */
    public function test_historia_pokazuje_daty_bez_godziny(): void
    {
        $accountId = Account::create(['name' => 'MKL'])->id;
        $budowa = Organization::create(['account_id' => $accountId, 'nazwaBud' => 'Budowa']);
        $pracownik = Contact::create(['account_id' => $accountId, 'first_name' => 'Jan', 'last_name' => 'Kowalski']);
        $biuro = User::factory()->create([
            'account_id' => $accountId, 'owner' => 2, 'active' => 1, 'password_changed_at' => now()->toDateTimeString(),
        ]);

        foreach (['2026-09-01 00:00:00', '2026-09-30 00:00:00'] as $dzien) {
            DB::table('building_time_sheets')->insert([
                'organization_id' => $budowa->id, 'contact_id' => $pracownik->id,
                'work_day' => $dzien, 'effective_work_time' => '08:00',
            ]);
        }

        $props = $this->actingAs($biuro)
            ->get('/contacts/'.$pracownik->id.'/history')
            ->assertOk()
            ->viewData('page')['props'];

        $this->assertSame('2026-09-01', $props['history'][0]['start']);
        $this->assertSame('2026-09-30', $props['history'][0]['end']);
        $this->assertEquals(16, $props['history'][0]['hours']);
    }
}
