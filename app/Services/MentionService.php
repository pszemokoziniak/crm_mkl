<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Note;
use App\Models\User;
use App\Notifications\MentionNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Obsługa wywołań @ w komentarzach.
 *
 * Wzmianka zapisywana jest w formacie @[Imię Nazwisko](user:ID) — dzięki ID
 * powiadomienie trafia do właściwej osoby także po zmianie nazwiska.
 */
class MentionService
{
    /**
     * Powiadamia osoby wywołane w komentarzu.
     * Przy edycji podaj $previousBody — powiadomimy tylko nowo wywołanych.
     */
    public function notify(Note $note, string $subject, string $url, ?string $previousBody = null): void
    {
        $ids = $this->extractUserIds($note->body);

        if ($previousBody !== null) {
            $ids = array_values(array_diff($ids, $this->extractUserIds($previousBody)));
        }

        // Autor nie dostaje powiadomienia o własnej wzmiance.
        $ids = array_values(array_filter($ids, fn (int $id) => $id !== (int) Auth::id()));

        if ($ids === []) {
            return;
        }

        try {
            $recipients = User::whereIn('id', $ids)->where('active', true)->get();

            if ($recipients->isEmpty()) {
                return;
            }

            Notification::send(
                $recipients,
                new MentionNotification($note, Auth::user(), $subject, $url)
            );
        } catch (\Throwable $e) {
            Log::warning('Nie udało się wysłać powiadomienia o wzmiance: '.$e->getMessage(), [
                'note_id' => $note->id,
            ]);
        }
    }

    /**
     * Wyciąga ID użytkowników wywołanych w treści.
     *
     * @return int[]
     */
    public function extractUserIds(string $body): array
    {
        if (! preg_match_all('/@\[[^\]]+\]\(user:(\d+)\)/u', $body, $matches)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $matches[1])));
    }

    /**
     * Lista osób do podpowiedzi po wpisaniu @.
     *
     * @return Collection<int, array{id: int, label: string}>
     */
    public function mentionableUsers(): Collection
    {
        return User::where('active', true)
            ->orderByName()
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (User $user) => [
                'id' => $user->id,
                'label' => trim($user->first_name.' '.$user->last_name),
            ])
            ->values();
    }
}
