<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Models\CtnDocument;
use App\Enums\TypDokumentu;
use App\Models\Contact;
use App\Models\DokumentyTyp;
use App\Services\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CtnDocumentsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('CtnDocuments/Index', [
            'filters' => Request::all('search', 'trashed'),
            'contactId' => (int) Request::route('contact_id'),
            'pracownik' => $this->nazwaPracownika((int) Request::route('contact_id')),
            'userOwner' => Auth::user()->owner,
            // "trashed=with" pokazuje też kosz — inaczej nie da się niczego
            // przywrócić, bo usunięty dokument nigdzie się nie pojawia.
            'documents' => CtnDocument::with('dokumentytyp')
                ->where('contact_id', Request::route('contact_id'))
                ->when(Request::input('trashed') === 'with', fn ($q) => $q->withTrashed())
                ->orderByDesc('id')
                ->paginate(10)
                ->withQueryString()
        ]);
    }

    public function create(): Response
    {
        // Trasa podaje samo id, nie model — dociągamy pracownika do nagłówka.
        $contactId = (int) Request::route('contact_id');

        return Inertia::render('CtnDocuments/Create', [
            'pracownik' => $this->danePracownika(Contact::withTrashed()->find($contactId)),
            'contactId' => $contactId,
            'dokumentyTyps' => DokumentyTyp::all(),
            // Wpisy pracownika w rozbiciu na typ dokumentu — żeby dało się
            // przypiąć skan do konkretnego badania, a nie tylko do worka
            // "badania tego człowieka".
            'wpisy' => $this->wpisyDoPrzypisania($contactId),
        ]);
    }

    public function store(DocumentService $documentService, StoreDocumentRequest $request, int $contactId): RedirectResponse
    {
        $redirect = Redirect::route('documents.index', ['contact_id' => $contactId]);

        $wpis = $this->wskazanyWpis($contactId, Request::get('typ'), Request::get('zrodlo_id'));

        try {
            foreach (Request::file('documents') as $file) {
                $documentService->storeCtnDocument(
                    $file,
                    $contactId,
                    (string) Request::get('name'),
                    // Formularz przysyła tekst, ale przez API bywa liczba —
                    // serwis oczekuje stringa, więc rzutujemy tutaj.
                    (string) Request::get('typ'),
                    $wpis
                );
            }
        } catch (\Exception $e) {
            Log::info('Error while storing document: ' . $e->getMessage());
            return $redirect->with('error', 'Nie udało się dodać dokumentu');
        }
        return $redirect->with('success', 'Dodano dokument');
    }

    public function view(int $contactId, int $documentId): BinaryFileResponse
    {
        // Dokument musi należeć do pracownika z adresu. Bez tego wystarczyło
        // podać id własnego pracownika i dowolne id dokumentu, żeby pobrać
        // cudzy skan — a od teraz wchodzą tu też kierownicy budów.
        $document = CtnDocument::query()
            ->where('id', $documentId)
            ->where('contact_id', $contactId)
            ->first();

        if (! $document) {
            abort(404);
        }

        $sciezka = storage_path('app/'.$document->path);

        if (! is_file($sciezka)) {
            abort(404);
        }

        return response()->download($sciezka, $document->filename ?: basename($sciezka));
    }

    public function delete(int $id, int $documentId): RedirectResponse
    {
        $this->doKosza($id, $documentId);

        return Redirect::route('documents.index', ['contact_id' => $id])
            ->with('success', 'Dokument przeniesiony do kosza.');
    }

    public function deleteLek(int $id, int $documentId): RedirectResponse
    {
        $this->doKosza($id, $documentId);

        return Redirect::route('badania.index', ['contact' => $id])
            ->with('success', 'Dokument przeniesiony do kosza.');
    }

    public function deleteBhp(int $id, int $documentId): RedirectResponse
    {
        $this->doKosza($id, $documentId);

        return Redirect::route('bhp.index', ['contact' => $id])
            ->with('success', 'Dokument przeniesiony do kosza.');
    }

    public function deleteUpr(int $id, int $documentId): RedirectResponse
    {
        $this->doKosza($id, $documentId);

        return Redirect::route('uprawnienia.index', ['contact' => $id])
            ->with('success', 'Dokument przeniesiony do kosza.');
    }

    public function deleteA1(int $id, int $documentId): RedirectResponse
    {
        $this->doKosza($id, $documentId);

        return Redirect::route('a1.index', ['contact' => $id])
            ->with('success', 'Dokument przeniesiony do kosza.');
    }

    public function deletePbioz(int $id, int $documentId): RedirectResponse
    {
        $this->doKosza($id, $documentId);

        return Redirect::route('pbioz.index', ['contact' => $id])
            ->with('success', 'Dokument przeniesiony do kosza.');
    }

    public function restore(int $id, int $documentId): RedirectResponse
    {
        $document = CtnDocument::withTrashed()
            ->where('id', $documentId)
            ->where('contact_id', $id)
            ->first();

        if (! $document) {
            abort(404);
        }

        $document->restore();

        return Redirect::back()->with('success', 'Dokument przywrócony.');
    }

    /**
     * Kasowanie dokumentu w pięciu miejscach było pięć razy przepisane
     * i wszędzie miało te same trzy usterki:
     *
     * - $document->path czytano poza sprawdzeniem null, więc brakujący
     *   dokument kończył się błędem zamiast cichym pominięciem,
     * - Storage::delete() dostawało ścieżkę bezwzględną, a oczekuje ścieżki
     *   względem dysku — plik NIGDY nie znikał, tylko wiersz z bazy,
     * - nie sprawdzano, czy dokument należy do pracownika z adresu.
     *
     * Plik zostaje teraz na dysku świadomie: bez niego przywrócenie
     * z kosza dałoby wiersz bez treści.
     */
    /**
     * @return array<int, array<int, array{id: int, etykieta: string}>>
     */
    private function wpisyDoPrzypisania(int $contactId): array
    {
        $wynik = [];

        foreach (TypDokumentu::cases() as $typ) {
            $zapytanie = $typ->modelWpisu()::where('contact_id', $contactId);

            if ($relacja = $typ->relacjaRodzaju()) {
                $zapytanie->with($relacja);
            }

            $wynik[$typ->value] = $zapytanie
                ->orderByRaw('`end` IS NULL, `end` DESC')
                ->get()
                ->map(fn ($wpis) => [
                    'id' => $wpis->id,
                    'etykieta' => $typ->opisWpisu($wpis),
                ])
                ->values()
                ->all();
        }

        return $wynik;
    }

    /** Nagłówek ma pokazywać, czyją kartę się ogląda — trasa podaje samo id. */
    private function nazwaPracownika(int $contactId): string
    {
        $c = Contact::withTrashed()->find($contactId);

        return $c ? trim($c->last_name.' '.$c->first_name) : '';
    }

    /**
     * Wpis wskazany na formularzu. Sprawdzamy, że należy do TEGO pracownika
     * i jest tego typu, co wybrany — inaczej dałoby się podpiąć dokument
     * pod cudze badanie, podmieniając id w formularzu.
     */
    private function wskazanyWpis(int $contactId, $typ, $zrodloId)
    {
        if (! $zrodloId || ! $typ) {
            return null;
        }

        $rodzaj = TypDokumentu::tryFrom((int) $typ);

        if (! $rodzaj) {
            return null;
        }

        return $rodzaj->modelWpisu()::where('id', $zrodloId)
            ->where('contact_id', $contactId)
            ->first();
    }

    private function doKosza(int $contactId, int $documentId): void
    {
        $document = CtnDocument::query()
            ->where('id', $documentId)
            ->where('contact_id', $contactId)
            ->first();

        if (! $document) {
            abort(404);
        }

        $document->delete();
    }
}
