<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\KomentarzWniosku;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Dzwonek: pracownik dopisał coś przy swoim wniosku urlopowym. */
class KomentarzWnioskuNotification extends Notification
{
    use Queueable;

    public function __construct(private KomentarzWniosku $komentarz)
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
        $w = $this->komentarz->wniosek;
        $c = $w?->contact;

        return [
            'type' => 'komentarz_wniosku',
            'wniosek_id' => $w?->id,
            'author' => $c ? trim($c->last_name.' '.$c->first_name) : 'pracownik',
            'subject' => 'Wiadomość przy wniosku urlopowym',
            'excerpt' => \Illuminate\Support\Str::limit($this->komentarz->tresc, 80),
            'url' => '/',
        ];
    }
}
