<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\UrlopyBezWnioskuMail;
use App\Models\User;
use App\Services\KierownicyBudowy;
use App\Services\UrlopyBezWniosku;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Raz w tygodniu przypomina kierownikom o urlopach w KCP bez skanu
 * wniosku, gdy brak trwa dłużej niż tydzień. Miękko: bez blokady zapisu,
 * bo kierownik wypełnia KCP wieczorem z telefonu i blokada skończyłaby
 * się wpisywaniem innego kodu.
 */
class PrzypomnijOWnioskachUrlopowych extends Command
{
    protected $signature = 'kadry:przypomnij-o-wnioskach {--dni=7 : Od ilu dni brak wniosku, żeby przypominać}';

    protected $description = 'Wysyła kierownikom przypomnienie o urlopach w KCP bez wniosku';

    public function handle(UrlopyBezWniosku $urlopy): int
    {
        $dzis = Carbon::today();
        $granica = $dzis->copy()->subDays((int) $this->option('dni'))->toDateString();

        $braki = $urlopy->dla(null, $dzis->copy()->subMonths(2)->startOfMonth()->toDateString(), $dzis->toDateString())
            ->filter(fn (array $u) => $u['od'] <= $granica);

        if ($braki->isEmpty()) {
            $this->info('Brak urlopów bez wniosku starszych niż '.$this->option('dni').' dni.');

            return self::SUCCESS;
        }

        $adres = rtrim(config('app.url'), '/');
        $wyslano = 0;

        foreach (app(KierownicyBudowy::class)->dlaBudow($braki->pluck('organization_id')->unique()->all(), $dzis->toDateString()) as $userId => $orgIds) {
            $user = User::find($userId);
            $jego = $braki->filter(fn (array $u) => in_array($u['organization_id'], $orgIds, true))->values();
            if (! $user || ! $user->active || $jego->isEmpty()) {
                continue;
            }

            try {
                Mail::to($user->email)->send(new UrlopyBezWnioskuMail($jego, $adres));
                $wyslano++;
            } catch (\Throwable $e) {
                Log::warning('Nie udało się wysłać przypomnienia o wnioskach urlopowych: '.$e->getMessage(), ['user_id' => $userId]);
            }
        }

        $this->info('Wysłano '.$wyslano.' przypomnień.');

        return self::SUCCESS;
    }
}
