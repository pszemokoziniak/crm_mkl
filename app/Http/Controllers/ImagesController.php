<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use League\Glide\Responses\LaravelResponseFactory;
use League\Glide\ServerFactory;

class ImagesController extends Controller
{
    /**
     * Skany dokumentów mają własną drogę (CtnDocumentsController@view), która
     * sprawdza, czy pracownik należy do pytającego. Tędy nie wychodzą wcale.
     */
    private const KATALOG_SKANOW = 'documents';

    public function show(Request $request, $path)
    {
        $path = ltrim(str_replace('\\', '/', (string) $path), '/');

        // Ścieżka z ".." wyprowadziłaby poza katalog z danymi.
        if (str_contains($path, '..')) {
            abort(404);
        }

        $localDisk = Storage::disk('local');

        // 1. Sprawdź czy plik istnieje w storage/app
        if ($localDisk->exists($path)) {
            // Pliki wgrane do systemu to dane osobowe: zdjęcia pracowników,
            // kont i sprzętu. Dotąd wychodziły stąd bez logowania — wystarczyło
            // znać ścieżkę. Pliki z public/ (logo na ekranie logowania) zostają
            // jawne, bo i tak serwuje je serwer WWW.
            if (! Auth::check()) {
                abort(404);
            }

            if (Str::startsWith($path, self::KATALOG_SKANOW.'/')) {
                abort(404);
            }

            $source = $localDisk->getDriver();
        }
        // 2. Jeśli nie, sprawdź w public/
        else if (file_exists(public_path($path))) {
            $source = Storage::build([
                'driver' => 'local',
                'root' => public_path(),
            ])->getDriver();
        }
        // 3. Jeśli nigdzie nie ma, zwróć 404
        else {
            abort(404);
        }

        // Jeśli nie ma parametrów (w, h, fit), możemy serwować plik bezpośrednio dla wydajności
        if (empty($request->all())) {
            return response()->file($localDisk->exists($path) ? $localDisk->path($path) : public_path($path));
        }

        // Użyj Glide do obróbki
        $server = ServerFactory::create([
            'response' => new LaravelResponseFactory($request),
            'source' => $source,
            'cache' => $localDisk->getDriver(),
            'cache_path_prefix' => '.glide-cache',
        ]);

        try {
            return $server->getImageResponse($path, $request->all());
        } catch (\Exception $e) {
            // W razie błędu Glide (np. brak biblioteki gd/imagick), zaserwuj oryginał
            return response()->file($localDisk->exists($path) ? $localDisk->path($path) : public_path($path));
        }
    }
}
