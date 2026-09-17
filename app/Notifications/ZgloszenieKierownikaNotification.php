<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ZgloszenieKierownika;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Dzwonek dla kadr: kierownik zgłosił zjazd, urlop albo przeniesienie. */
class ZgloszenieKierownikaNotification extends Notification
{
    use Queueable;

    public function __construct(private ZgloszenieKierownika $zgloszenie)
    {
    }

    /**
     * @return string[]
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $contact = $this->zgloszenie->contact;
        $autor = $this->zgloszenie->autor;
        $budowa = $this->zgloszenie->organization;

        return [
            'type' => 'zgloszenie_kierownika',
            'zgloszenie_id' => $this->zgloszenie->id,
            'author' => $autor ? trim($autor->first_name.' '.$autor->last_name) : 'kierownik',
            'subject' => 'Zgłoszenie od kierownika: '.$this->zgloszenie->rodzajLabel(),
            'excerpt' => ($contact ? trim($contact->last_name.' '.$contact->first_name) : '—')
                .($budowa ? ' — '.$budowa->nazwaBud : ''),
            'url' => '/zmiany-kadrowe',
        ];
    }
}
