<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/** Przypomnienie dla kierownika: urlopy w KCP bez skanu wniosku. */
class UrlopyBezWnioskuMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param Collection<int, array<string, mixed>> $urlopy wiersze z UrlopyBezWniosku
     */
    public function __construct(public Collection $urlopy, public string $adresAplikacji)
    {
    }

    public function build(): self
    {
        return $this->subject('HRM: '.$this->urlopy->count().' '.($this->urlopy->count() === 1 ? 'urlop' : 'urlopy').' bez wniosku w KCP')
            ->view('emails.urlopy-bez-wniosku');
    }
}
