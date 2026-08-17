<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ZadaniePriority;
use App\Enums\ZadanieStatus;
use App\Http\Requests\StoreZadanieRequest;
use App\Models\Note;
use App\Models\User;
use App\Models\Zadanie;
use App\Models\ZadanieFile;
use App\Notifications\ZadanieAssignedNotification;
use App\Services\DocumentService;
use App\Services\MentionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ZadaniaController extends Controller
{
    public function __construct(
        private DocumentService $documentService,
        private MentionService $mentionService,
    ) {
    }

    public function index(): Response
    {
        $user = Auth::user();
        $filters = Request::all('search', 'status', 'priority', 'assignee', 'trashed', 'view');
        $view = ($filters['view'] ?? null) === 'lista' ? 'lista' : 'kanban';

        $query = Zadanie::visibleTo($user)
            ->with(['reporter:id,first_name,last_name', 'assignee:id,first_name,last_name'])
            ->withCount(['notes', 'screenshots'])
            ->filter($filters)
            ->orderByRaw("CASE priority WHEN 'wysoki' THEN 1 WHEN 'normalny' THEN 2 ELSE 3 END")
            ->orderByRaw('deadline IS NULL, deadline')
            ->orderByDesc('id');

        return Inertia::render('Zadania/Index', [
            'filters' => array_merge($filters, ['view' => $view]),
            'statuses' => ZadanieStatus::options(),
            'priorities' => ZadaniePriority::options(),
            'users' => $this->assignableUsers(),
            'columns' => $view === 'kanban' ? $this->kanbanColumns($query) : null,
            'zadania' => $view === 'lista'
                ? $query->paginate(20)->withQueryString()->through(fn (Zadanie $zadanie) => $this->card($zadanie))
                : null,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Zadanie::class);

        return Inertia::render('Zadania/Create', [
            'statuses' => ZadanieStatus::options(),
            'priorities' => ZadaniePriority::options(),
            'users' => $this->assignableUsers(),
        ]);
    }

    public function store(StoreZadanieRequest $request): RedirectResponse
    {
        $this->authorize('create', Zadanie::class);

        $zadanie = Zadanie::create($request->safe()->except('screenshots') + [
            'reporter_id' => Auth::id(),
        ]);

        $this->storeUploadedFiles($request->file('screenshots'), $zadanie);

        if ($zadanie->assignee_id && (int) $zadanie->assignee_id !== (int) Auth::id()) {
            $this->notifyAssignee($zadanie);
        }

        return Redirect::route('zadania.show', $zadanie)->with('success', 'Zgłoszenie dodane.');
    }

    public function show(Zadanie $zadanie): Response
    {
        $this->authorize('view', $zadanie);

        $zadanie->load([
            'reporter:id,first_name,last_name',
            'assignee:id,first_name,last_name',
            'screenshots',
            'notes.author:id,first_name,last_name',
            'notes.files',
        ]);

        $user = Auth::user();

        return Inertia::render('Zadania/Show', [
            'zadanie' => $this->card($zadanie) + [
                'description' => $zadanie->description,
                'created_at' => $zadanie->created_at?->format('d.m.Y H:i'),
                'updated_at' => $zadanie->updated_at?->format('d.m.Y H:i'),
                'screenshots' => $zadanie->screenshots->map(fn (ZadanieFile $file) => $this->file($file)),
            ],
            'notes' => $zadanie->notes->map(fn (Note $note) => $this->note($note, $user)),
            'statuses' => ZadanieStatus::options(),
            'priorities' => ZadaniePriority::options(),
            'mentionableUsers' => $this->mentionService->mentionableUsers(),
            'can' => [
                'update' => $user->can('update', $zadanie),
                'updateStatus' => $user->can('updateStatus', $zadanie),
                'comment' => $user->can('comment', $zadanie),
                'delete' => $user->can('delete', $zadanie),
            ],
        ]);
    }

    public function edit(Zadanie $zadanie): Response
    {
        $this->authorize('update', $zadanie);

        $zadanie->load('screenshots');

        return Inertia::render('Zadania/Edit', [
            'zadanie' => [
                'id' => $zadanie->id,
                'title' => $zadanie->title,
                'description' => $zadanie->description,
                'url' => $zadanie->url,
                'status' => $zadanie->status,
                'priority' => $zadanie->priority,
                'assignee_id' => $zadanie->assignee_id,
                'deadline' => $zadanie->deadline?->format('Y-m-d'),
                'deleted_at' => $zadanie->deleted_at?->format('Y-m-d'),
                'screenshots' => $zadanie->screenshots->map(fn (ZadanieFile $file) => $this->file($file)),
            ],
            'statuses' => ZadanieStatus::options(),
            'priorities' => ZadaniePriority::options(),
            'users' => $this->assignableUsers(),
        ]);
    }

    public function update(StoreZadanieRequest $request, Zadanie $zadanie): RedirectResponse
    {
        $this->authorize('update', $zadanie);

        $previousStatus = $zadanie->status;
        $previousAssignee = (int) $zadanie->assignee_id;

        $zadanie->update($request->safe()->except('screenshots'));

        $this->storeUploadedFiles($request->file('screenshots'), $zadanie);

        if ($zadanie->status !== $previousStatus) {
            $this->logStatusChange($zadanie, $previousStatus);
        }

        if ($zadanie->assignee_id && (int) $zadanie->assignee_id !== $previousAssignee
            && (int) $zadanie->assignee_id !== (int) Auth::id()) {
            $this->notifyAssignee($zadanie);
        }

        return Redirect::route('zadania.show', $zadanie)->with('success', 'Zgłoszenie zaktualizowane.');
    }

    /** Zmiana statusu bez wchodzenia w formularz (dropdown i drag&drop na kanbanie). */
    public function updateStatus(Zadanie $zadanie): RedirectResponse
    {
        $this->authorize('updateStatus', $zadanie);

        $data = Validator::make(Request::only('status'), [
            'status' => ['required', Rule::in(ZadanieStatus::values())],
        ])->validate();

        if ($data['status'] === $zadanie->status) {
            return Redirect::back();
        }

        $previousStatus = $zadanie->status;
        $zadanie->update(['status' => $data['status']]);
        $this->logStatusChange($zadanie, $previousStatus);

        return Redirect::back()->with('success', 'Status zmieniony na: '.$zadanie->statusLabel());
    }

    public function destroy(Zadanie $zadanie): RedirectResponse
    {
        $this->authorize('delete', $zadanie);

        $zadanie->delete();

        return Redirect::route('zadania.index')->with('success', 'Zgłoszenie zarchiwizowane.');
    }

    public function restore(Zadanie $zadanie): RedirectResponse
    {
        $this->authorize('restore', $zadanie);

        $zadanie->restore();

        return Redirect::back()->with('success', 'Zgłoszenie przywrócone.');
    }

    /** Dodanie print screenów do istniejącego zgłoszenia. */
    public function storeFiles(Zadanie $zadanie): RedirectResponse
    {
        $this->authorize('update', $zadanie);

        Validator::make(Request::only('screenshots'), [
            'screenshots' => 'required|array|max:10',
            'screenshots.*' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf',
        ])->validate();

        $this->storeUploadedFiles(Request::file('screenshots'), $zadanie);

        return Redirect::back()->with('success', 'Dodano załącznik.');
    }

    public function showFile(Zadanie $zadanie, ZadanieFile $file): BinaryFileResponse
    {
        $this->authorize('view', $zadanie);

        if ((int) $file->zadanie_id !== (int) $zadanie->id) {
            abort(404);
        }

        $absolutePath = storage_path('app/'.$file->path);

        if (! is_file($absolutePath)) {
            abort(404);
        }

        // Obrazy pokazujemy w przeglądarce, resztę oddajemy do pobrania.
        return $file->is_image
            ? response()->file($absolutePath)
            : response()->download($absolutePath, $file->original_name);
    }

    public function destroyFile(Zadanie $zadanie, ZadanieFile $file): RedirectResponse
    {
        $this->authorize('update', $zadanie);

        if ((int) $file->zadanie_id !== (int) $zadanie->id) {
            abort(404);
        }

        $this->documentService->deleteZadanieFile($file);

        return Redirect::back()->with('success', 'Załącznik usunięty.');
    }

    /**
     * Kolumny kanbana — wszystkie widoczne zgłoszenia pogrupowane po statusie.
     *
     * @return array<int, array{value: string, label: string, count: int, items: Collection}>
     */
    private function kanbanColumns(Builder $query): array
    {
        $items = $query->get();

        return array_map(function (ZadanieStatus $status) use ($items) {
            $inColumn = $items->where('status', $status->value)->values();

            return [
                'value' => $status->value,
                'label' => $status->label(),
                'count' => $inColumn->count(),
                'items' => $inColumn->map(fn (Zadanie $zadanie) => $this->card($zadanie)),
            ];
        }, ZadanieStatus::ordered());
    }

    /**
     * @return array<string, mixed>
     */
    private function card(Zadanie $zadanie): array
    {
        return [
            'id' => $zadanie->id,
            'title' => $zadanie->title,
            'url' => $zadanie->url,
            'status' => $zadanie->status,
            'status_label' => $zadanie->statusLabel(),
            'priority' => $zadanie->priority,
            'deadline' => $zadanie->deadline?->format('Y-m-d'),
            'deleted_at' => $zadanie->deleted_at?->format('Y-m-d'),
            'notes_count' => $zadanie->notes_count ?? $zadanie->notes()->count(),
            'screenshots_count' => $zadanie->screenshots_count ?? $zadanie->screenshots()->count(),
            'reporter' => $this->person($zadanie->reporter),
            'assignee' => $this->person($zadanie->assignee),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function note(Note $note, User $user): array
    {
        return [
            'id' => $note->id,
            'body' => $note->body,
            'system' => $note->system,
            'author' => $this->person($note->author),
            'created_at' => $note->created_at?->format('d.m.Y H:i'),
            'updated_at' => $note->updated_at?->format('d.m.Y H:i'),
            'edited' => $note->updated_at?->ne($note->created_at) ?? false,
            'files' => $note->files->map(fn (ZadanieFile $file) => $this->file($file)),
            'can_edit' => ! $note->system && (int) $note->user_id === (int) $user->id,
            'can_delete' => ! $note->system && ((int) $note->user_id === (int) $user->id || $user->isOffice()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function file(ZadanieFile $file): array
    {
        return [
            'id' => $file->id,
            'name' => $file->original_name,
            'is_image' => $file->is_image,
            'size' => $file->size,
            'url' => route('zadania.files.show', ['zadanie' => $file->zadanie_id, 'file' => $file->id]),
        ];
    }

    /**
     * @return array{id: int, name: string}|null
     */
    private function person(?User $user): ?array
    {
        return $user ? ['id' => $user->id, 'name' => trim($user->first_name.' '.$user->last_name)] : null;
    }

    /**
     * @return Collection<int, array{id: int, name: string}>
     */
    private function assignableUsers(): Collection
    {
        return User::where('active', true)
            ->orderByName()
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn (User $user) => ['id' => $user->id, 'name' => trim($user->first_name.' '.$user->last_name)])
            ->values();
    }

    /**
     * @param  array<int, UploadedFile>|UploadedFile|null  $files
     */
    private function storeUploadedFiles($files, Zadanie $zadanie, ?int $noteId = null): void
    {
        if (empty($files)) {
            return;
        }

        foreach (is_array($files) ? $files : [$files] as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            try {
                $this->documentService->storeZadanieFile($file, $zadanie->id, $noteId);
            } catch (\Throwable $e) {
                Log::error('Nie udało się zapisać załącznika zgłoszenia: '.$e->getMessage(), [
                    'zadanie_id' => $zadanie->id,
                ]);
            }
        }
    }

    /** Zmiana statusu zostaje w wątku jako wpis systemowy — widać kto i kiedy. */
    private function logStatusChange(Zadanie $zadanie, ?string $previousStatus): void
    {
        $from = ZadanieStatus::tryFrom((string) $previousStatus)?->label() ?? $previousStatus;

        $zadanie->notes()->create([
            'user_id' => Auth::id(),
            'body' => 'Zmiana statusu: '.$from.' → '.$zadanie->statusLabel(),
            'system' => true,
        ]);
    }

    private function notifyAssignee(Zadanie $zadanie): void
    {
        try {
            $zadanie->assignee?->notify(new ZadanieAssignedNotification($zadanie, Auth::user()));
        } catch (\Throwable $e) {
            Log::warning('Nie udało się powiadomić o przypisaniu zgłoszenia: '.$e->getMessage(), [
                'zadanie_id' => $zadanie->id,
            ]);
        }
    }
}
