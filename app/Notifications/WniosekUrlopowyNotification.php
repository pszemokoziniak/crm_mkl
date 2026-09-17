<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\WniosekUrlopowy;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/** Dzwonek dla kierownika: pracownik złożył wniosek urlopowy z telefonu. */
class WniosekUrlopowyNotification extends Notification
{
    use Queueable;

    public function __construct(private WniosekUrlopowy $wniosek)
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
        $c = $this->wniosek->contact;

        return [
            'type' => 'wniosek_urlopowy',
            'wniosek_id' => $this->wniosek->id,
            'author' => $c ? trim($c->last_name.' '.$c->first_name) : 'pracownik',
            'subject' => 'Wniosek urlopowy do zatwierdzenia',
            'excerpt' => $this->wniosek->rodzajLabel().' '.$this->wniosek->od->format('d.m').'–'.$this->wniosek->do->format('d.m.Y'),
            'url' => '/',
        ];
    }
}
