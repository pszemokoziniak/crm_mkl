<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Note;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

/**
 * Ktoś wywołał użytkownika przez @ w komentarzu.
 */
class MentionNotification extends Notification
{
    use Queueable;

    public function __construct(
        private Note $note,
        private User $author,
        private string $subject,
        private string $url,
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
            'type' => 'mention',
            'note_id' => $this->note->id,
            'author' => $this->author->first_name.' '.$this->author->last_name,
            'subject' => $this->subject,
            'excerpt' => Str::limit($this->plainBody(), 120),
            'url' => $this->url,
        ];
    }

    /** Usuwa znaczniki wzmianek, żeby w dzwonku był czytelny tekst. */
    private function plainBody(): string
    {
        return trim(preg_replace('/@\[([^\]]+)\]\(user:\d+\)/u', '@$1', $this->note->body) ?? $this->note->body);
    }
}
