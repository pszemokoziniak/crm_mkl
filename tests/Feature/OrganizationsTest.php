<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Lista budów: wyświetlanie, wyszukiwanie i filtr archiwum.
 * Lista mieszka pod /budowy, a wiersz niesie numer i nazwę projektu —
 * dawnych pól adresowych (phone, region, postal_code) już nie ma.
 */
class OrganizationsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Organization $apple;
    private Organization $microsoft;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'account_id' => Account::create(['name' => 'Acme Corporation'])->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'johndoe@example.com',
            'owner' => 1,
            'active' => 1,
        ]);

        $this->apple = Organization::create([
            'account_id' => 0,
            'name' => 'Apple',
            'nazwaBud' => 'Apple Park',
            'numerBud' => '100',
            'city' => 'Toronto',
        ]);

        $this->microsoft = Organization::create([
            'account_id' => 0,
            'name' => 'Microsoft',
            'nazwaBud' => 'Microsoft Campus',
            'numerBud' => '200',
            'city' => 'Redmond',
        ]);
    }

    public function test_can_view_organizations()
    {
        $this->actingAs($this->user)
            ->get('/budowy?sort=nazwaBud&direction=asc')
            ->assertInertia(fn (Assert $assert) => $assert
                ->component('Organizations/Index')
                ->has('organizations.data', 2)
                ->has('organizations.data.0', fn (Assert $assert) => $assert
                    ->where('id', $this->apple->id)
                    ->where('name', 'Apple')
                    ->where('nazwaBud', 'Apple Park')
                    ->where('numerBud', '100')
                    ->where('deleted_at', null)
                    ->etc()
                )
                ->has('organizations.data.1', fn (Assert $assert) => $assert
                    ->where('id', $this->microsoft->id)
                    ->where('name', 'Microsoft')
                    ->where('nazwaBud', 'Microsoft Campus')
                    ->where('deleted_at', null)
                    ->etc()
                )
            );
    }

    public function test_can_search_for_organizations()
    {
        $this->actingAs($this->user)
            ->get('/budowy?search=Apple')
            ->assertInertia(fn (Assert $assert) => $assert
                ->component('Organizations/Index')
                ->where('filters.search', 'Apple')
                ->has('organizations.data', 1)
                ->has('organizations.data.0', fn (Assert $assert) => $assert
                    ->where('id', $this->apple->id)
                    ->where('nazwaBud', 'Apple Park')
                    ->where('deleted_at', null)
                    ->etc()
                )
            );
    }

    public function test_cannot_view_deleted_organizations()
    {
        $this->microsoft->delete();

        $this->actingAs($this->user)
            ->get('/budowy')
            ->assertInertia(fn (Assert $assert) => $assert
                ->component('Organizations/Index')
                ->has('organizations.data', 1)
                ->where('organizations.data.0.nazwaBud', 'Apple Park')
            );
    }

    public function test_can_filter_to_view_deleted_organizations()
    {
        $this->microsoft->delete();

        $this->actingAs($this->user)
            ->get('/budowy?trashed=with&sort=nazwaBud&direction=asc')
            ->assertInertia(fn (Assert $assert) => $assert
                ->component('Organizations/Index')
                ->has('organizations.data', 2)
                ->where('organizations.data.0.nazwaBud', 'Apple Park')
                ->where('organizations.data.1.nazwaBud', 'Microsoft Campus')
            );
    }
}
