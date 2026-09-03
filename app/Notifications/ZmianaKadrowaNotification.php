<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ZmianaKadrowa;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sygnał dla kadr: pojawiła się zmiana pobytu do obsłużenia.
 * Wysyłamy raz na paczkę zmian, nie raz na pracownika.
 */
class ZmianaKadrowaNotification extends Notification
{
    use Queueable;

    public function __construct(private ZmianaKadrowa $zmiana)
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
        $contact = $this->zmiana->contact;
        $autor = $this->zmiana->autor;

        return [
            'type' => 'zmiana_kadrowa',
            'paczka' => $this->zmiana->paczka,
            'author' => $autor ? trim($autor->first_name.' '.$autor->last_name) : 'system',
            'subject' => 'Zmiana pobytu do obsłużenia',
            'excerpt' => $contact
                ? trim($contact->last_name.' '.$contact->first_name).' — '.$this->zmiana->typLabel()
                : $this->zmiana->typLabel(),
            'url' => '/zmiany-kadrowe',
        ];
    }
}
