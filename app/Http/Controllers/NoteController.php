<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\Zadanie;
use App\Services\DocumentService;
use App\Services\MentionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Komentarze pod zgłoszeniami. Relacja jest polimorficzna, więc ten sam
 * kontroler obsłuży w przyszłości dyskusję pod innym modelem — wystarczy
 * dopisać wpis do TYPE_MAP.
 */
class NoteController extends Controller
{
    private const TYPE_MAP = [
        'zadanie' => Zadanie::class,
    ];

    public function __construct(
        private DocumentService $documentService,
        private MentionService $mentionService,
    ) {
    }

    public function store(): RedirectResponse
    {
        $data = Validator::make(Request::all(), [
            'type' => 'required|in:'.implode(',', array_keys(self::TYPE_MAP)),
            'notable_id' => 'required|integer',
            'body' => 'required|string|max:10000',
            'files' => 'nullable|array|max:10',
            'files.*' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf',
        ], [
            'body.required' => 'Treść komentarza jest wymagana.',
            'files.*.max' => 'Plik jest zbyt duży (max 10 MB).',
            'files.*.mimes' => 'Dozwolone formaty: jpg, png, gif, webp, pdf.',
        ])->validate();

        $notable = $this->findNotable($data['type'], (int) $data['notable_id']);

        if (! $notable) {
            return Redirect::back()->with('error', 'Nie znaleziono zgłoszenia.');
        }

        $this->authorize('comment', $notable);

        $note = $notable->notes()->create([
            'user_id' => Auth::id(),
            'body' => $data['body'],
        ]);

        $this->storeFiles(Request::file('files'), $notable, $note);

        $this->mentionService->notify($note, $notable->title, $this->notableUrl($notable));

        return Redirect::back()->with('success', 'Komentarz dodany.');
    }

    public function update(Note $note): RedirectResponse
    {
        if ($note->system || (int) $note->user_id !== (int) Auth::id()) {
            return Redirect::back()->with('error', 'Możesz edytować tylko swoje komentarze.');
        }

        $data = Validator::make(Request::only('body'), [
            'body' => 'required|string|max:10000',
        ])->validate();

        $previousBody = $note->body;
        $note->update(['body' => $data['body']]);

        $notable = $note->notable;

        if ($notable) {
            // Powiadamiamy tylko nowo wywołane osoby — bez dublowania.
            $this->mentionService->notify(
                $note,
                $notable->title,
                $this->notableUrl($notable),
                $previousBody
            );
        }

        return Redirect::back()->with('success', 'Komentarz poprawiony.');
    }

    public function destroy(Note $note): RedirectResponse
    {
        $user = Auth::user();

        if ($note->system || ((int) $note->user_id !== (int) $user->id && ! $user->isOffice())) {
            return Redirect::back()->with('error', 'Możesz usunąć tylko swoje komentarze.');
        }

        foreach ($note->files as $file) {
            $this->documentService->deleteZadanieFile($file);
        }

        $note->delete();

        return Redirect::back()->with('success', 'Komentarz usunięty.');
    }

    private function findNotable(string $type, int $id): ?Zadanie
    {
        /** @var class-string<Zadanie> $modelClass */
        $modelClass = self::TYPE_MAP[$type];

        return $modelClass::withTrashed()->find($id);
    }

    private function notableUrl(Zadanie $notable): string
    {
        return '/zadania/'.$notable->id;
    }

    /**
     * @param  array<int, UploadedFile>|UploadedFile|null  $files
     */
    private function storeFiles($files, Zadanie $notable, Note $note): void
    {
        if (empty($files)) {
            return;
        }

        foreach (is_array($files) ? $files : [$files] as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            try {
                $this->documentService->storeZadanieFile($file, $notable->id, $note->id);
            } catch (\Throwable $e) {
                Log::error('Nie udało się zapisać załącznika komentarza: '.$e->getMessage(), [
                    'note_id' => $note->id,
                ]);
            }
        }
    }
}
