<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use App\Models\Zadanie;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Użytkownik został przypisany do zgłoszenia.
 */
class ZadanieAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private Zadanie $zadanie,
        private User $author,
    ) {
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
        return [
            'type' => 'assignment',
            'zadanie_id' => $this->zadanie->id,
            'author' => $this->author->first_name.' '.$this->author->last_name,
            'subject' => $this->zadanie->title,
            'excerpt' => 'Przypisano Ci zgłoszenie',
            'url' => '/zadania/'.$this->zadanie->id,
        ];
    }
}
