<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\UrlopyBezWnioskuMail;
use App\Models\Contact;
use App\Models\ContactWorkDate;
use App\Models\Funkcja;
use App\Models\Organization;
use App\Models\User;
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

        foreach ($this->kierownicyBudow($braki->pluck('organization_id')->unique()->all(), $dzis->toDateString()) as $userId => $orgIds) {
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

    /**
     * Kto dziś prowadzi te budowy: kierownictwo z aktywnym pobytem
     * (kierownik, inżynier) plus kierownik projektu z pola przy budowie.
     *
     * @param int[] $orgIds
     * @return array<int, int[]> user_id => budowy
     */
    private function kierownicyBudow(array $orgIds, string $dzis): array
    {
        $wynik = [];

        $pobyty = ContactWorkDate::with('contact')
            ->whereIn('organization_id', $orgIds)
            ->activeOn($dzis)
            ->whereHas('contact', fn ($q) => $q->whereIn('funkcja_id', Funkcja::idsKierownictwaBudowy())->whereNotNull('user_id'))
            ->get();
        foreach ($pobyty as $p) {
            $wynik[(int) $p->contact->user_id][] = (int) $p->organization_id;
        }

        foreach (Organization::whereIn('id', $orgIds)->whereNotNull('kierownik_projektu_id')->get(['id', 'kierownik_projektu_id']) as $o) {
            $userId = Contact::withTrashed()->where('id', $o->kierownik_projektu_id)->value('user_id');
            if ($userId) {
                $wynik[(int) $userId][] = (int) $o->id;
            }
        }

        return array_map(fn ($lista) => array_values(array_unique($lista)), $wynik);
    }
}
