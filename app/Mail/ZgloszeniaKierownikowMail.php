<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/** E-mail do kadr o nowych zgłoszeniach od kierowników — jeden na przebieg, nie na zgłoszenie. */
class ZgloszeniaKierownikowMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param Collection<int, \App\Models\ZgloszenieKierownika> $zgloszenia
     */
    public function __construct(public Collection $zgloszenia, public string $adresZakladki)
    {
    }

    public function build(): self
    {
        $ile = $this->zgloszenia->count();
        $pierwsze = $this->zgloszenia->first();
        $temat = $ile === 1 && $pierwsze && $pierwsze->contact
            ? 'Zgłoszenie od kierownika: '.trim($pierwsze->contact->last_name.' '.$pierwsze->contact->first_name).' — '.$pierwsze->rodzajLabel()
            : 'Zgłoszenia od kierowników do obsłużenia: '.$ile;

        return $this->subject($temat)->view('emails.zgloszenia-kierownikow');
    }
}
