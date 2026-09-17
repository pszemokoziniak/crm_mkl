<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Osobisty link pracownika do strony HRM na telefon. */
class DostepPracownikaMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public string $imie, public string $adres)
    {
    }

    public function build(): self
    {
        return $this->subject('HRM MKL: Twój link do składania wniosków urlopowych')
            ->view('emails.dostep-pracownika');
    }
}
