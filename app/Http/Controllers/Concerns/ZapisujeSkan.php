<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Enums\TypDokumentu;
use App\Services\DocumentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Wgrywanie skanu prosto z formularza wpisu (badanie, szkolenie, A1, PBIOZ,
 * uprawnienie). Dotąd dokument dodawało się osobno w zakładce Dokumenty
 * i nic nie łączyło go z konkretnym wpisem.
 */
trait ZapisujeSkan
{
    protected function zapiszSkan(Request $request, Model $wpis, TypDokumentu $typ): void
    {
        $plik = $request->file('skan');

        if (! $plik) {
            return;
        }

        // Nazwa bierze się z typu i daty ważności, żeby na liście dokumentów
        // dało się poznać, czego dotyczy, bez otwierania pliku.
        $nazwa = trim($typ->label().' '.($wpis->end ?? ''));

        app(DocumentService::class)->storeCtnDocument(
            $plik,
            (int) $wpis->contact_id,
            $nazwa,
            (string) $typ->value,
            $wpis
        );
    }
}
