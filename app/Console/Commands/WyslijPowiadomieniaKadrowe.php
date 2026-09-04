<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\ZmianyKadroweMail;
use App\Models\User;
use App\Models\ZmianaKadrowa;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Wysyła kadrom e-mail o zmianach czekających w zakładce.
 *
 * Czekamy, aż paczka przestanie rosnąć — przeniesienie ekipy to kilka
 * kolejnych czynności (skrócenie na budowie A, dodanie na budowie B),
 * a kadry mają dostać jedną wiadomość z kompletem, nie pięć urywków.
 */
class WyslijPowiadomieniaKadrowe extends Command
{
    protected $signature = 'kadry:powiadom-mailem
                            {--minuty=30 : Ile minut ciszy oznacza, że paczka jest zamknięta}';

    protected $description = 'Wysyła kadrom e-mail o zamkniętych paczkach zmian kadrowych';

    public function handle(): int
    {
        $odbiorcy = User::where('powiadomienia_kadrowe', true)
            ->where('active', true)
            ->whereNull('deleted_at')
            ->get();

        if ($odbiorcy->isEmpty()) {
            $this->info('Nikt nie ma włączonych powiadomień e-mail — nic nie wysyłam.');

            return self::SUCCESS;
        }

        $granica = Carbon::now()->subMinutes((int) $this->option('minuty'));

        $paczki = ZmianaKadrowa::whereNull('mail_wyslany_at')
            ->selectRaw('paczka, max(created_at) as ostatnia')
            ->groupBy('paczka')
            ->havingRaw('max(created_at) <= ?', [$granica])
            ->pluck('paczka');

        if ($paczki->isEmpty()) {
            $this->info('Brak zamkniętych paczek do wysłania.');

            return self::SUCCESS;
        }

        foreach ($paczki as $paczka) {
            $this->wyslij($paczka, $odbiorcy);
        }

        return self::SUCCESS;
    }

    private function wyslij(string $paczka, $odbiorcy): void
    {
        $zmiany = ZmianaKadrowa::with(['contact', 'budowaZ', 'budowaDo', 'autor'])
            ->where('paczka', $paczka)
            ->orderBy('id')
            ->get();

        if ($zmiany->isEmpty()) {
            return;
        }

        $autorModel = optional($zmiany->first())->autor;
        $autor = $autorModel ? trim($autorModel->first_name.' '.$autorModel->last_name) : 'system';
        $adres = rtrim(config('app.url'), '/').'/zmiany-kadrowe';

        try {
            foreach ($odbiorcy as $odbiorca) {
                Mail::to($odbiorca->email)->send(new ZmianyKadroweMail($zmiany, $autor, $adres));
            }

            // Znaczymy dopiero po wysłaniu — gdy poczta nie zadziała,
            // spróbujemy ponownie przy następnym przebiegu.
            ZmianaKadrowa::where('paczka', $paczka)->update(['mail_wyslany_at' => Carbon::now()]);

            $this->info('Wysłano paczkę '.$paczka.' ('.$zmiany->count().' zmian) do '.$odbiorcy->count().' odbiorców.');
        } catch (\Throwable $e) {
            Log::warning('Nie udało się wysłać e-maila o zmianach kadrowych: '.$e->getMessage(), [
                'paczka' => $paczka,
            ]);

            $this->error('Paczka '.$paczka.' — błąd wysyłki: '.$e->getMessage());
        }
    }
}
