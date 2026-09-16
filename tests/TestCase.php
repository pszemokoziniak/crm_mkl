<?php

namespace Tests;

use App\Uprawnienia\Macierz;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Macierz trzyma nadpisania z bazy w statycznej pamięci na czas
        // żądania; w jednym procesie testów bez tego przeciekałyby między testami.
        Macierz::zapomnij();
    }
}
