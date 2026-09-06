<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Controllers\LogowaniaController;
use App\Models\Logowanie;
use Illuminate\Console\Command;

/**
 * Rejestr logowań rośnie z każdym wejściem, więc starsze wpisy kasujemy.
 * Trzymamy rok — tyle wystarcza, żeby sprawdzić, kto i kiedy korzystał
 * z systemu, a jednocześnie nie zbieramy danych bez potrzeby.
 */
class PosprzatajRejestrLogowan extends Command
{
    protected $signature = 'logowania:posprzataj';

    protected $description = 'Kasuje wpisy rejestru logowań starsze niż rok';

    public function handle(): int
    {
        $granica = now()->subMonths(LogowaniaController::MIESIACE_PRZECHOWYWANIA);

        $usuniete = Logowanie::where('created_at', '<', $granica)->delete();

        $this->info('Usunięto wpisów starszych niż '.$granica->toDateString().': '.$usuniete);

        return self::SUCCESS;
    }
}
