<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Wiadomość do kadr o zmianach czekających w zakładce "Zmiany kadrowe".
 * Jedna wiadomość na paczkę — przeniesienie ekipy to jeden e-mail,
 * a nie osobny dla każdej osoby.
 */
class ZmianyKadroweMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  Collection<int, \App\Models\ZmianaKadrowa>  $zmiany
     */
    public function __construct(
        public Collection $zmiany,
        public string $autor,
        public string $adresZakladki
    ) {
    }

    public function build(): self
    {
        $ile = $this->zmiany->pluck('contact_id')->unique()->count();

        $temat = $ile === 1
            ? 'Zmiana kadrowa do obsłużenia: '.$this->nazwiskoPierwszej()
            : 'Zmiany kadrowe do obsłużenia: '.$ile.' '.$this->odmienOsoby($ile);

        return $this->subject($temat)
            ->view('emails.zmiany-kadrowe');
    }

    private function nazwiskoPierwszej(): string
    {
        $contact = optional($this->zmiany->first())->contact;

        return $contact ? trim($contact->last_name.' '.$contact->first_name) : 'pracownik';
    }

    private function odmienOsoby(int $ile): string
    {
        $reszta = $ile % 10;
        $dziesiatki = $ile % 100;

        if ($reszta >= 2 && $reszta <= 4 && ($dziesiatki < 12 || $dziesiatki > 14)) {
            return 'osoby';
        }

        return 'osób';
    }
}
