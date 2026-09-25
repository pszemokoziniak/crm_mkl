<?php
// Test dymny HRM: wybrane strony GET (tylko odczyt) jako admin, przez kernel
// w kontenerze. Wynik: status i rozmiar, do porównania przed/po zmianie PHP
// albo Laravela. Uruchomienie: docker exec -u www-data hrm-app php .docker/prod/test-dymny.php
use Illuminate\Http\Request;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$admin = App\Models\User::all()->first(fn ($u) => $u->isAdmin());
$bud = App\Models\Organization::tylkoBudowy()->whereHas('contactWorkDates')->orderByDesc('id')->first()
    ?? App\Models\Organization::tylkoBudowy()->orderByDesc('id')->first();
$kontakt = App\Models\Contact::orderByDesc('id')->first();
$dzis = now()->format('Y-m-d');

$uris = ['/', 'badaniaTyp', 'baza-wiedzy', 'bhpTyp', 'budowy', 'budowy/create', 'contacts', 'contacts/create',
    'crm/klienci', 'dokumentyTyp', 'funkcja', 'grupy-sprzetu', 'jezykTyp', 'kierownicy', 'krajTyp', 'logowania',
    'narzedzia', 'narzedzia/create', 'narzedziaTyp', 'position', 'prognoza', 'prognoza/building', 'reports',
    'reports/koniecUprawinien', 'shiftStatusTyp', 'statystyki', 'tools', 'typy-kosztow', 'uprawnienia-rol',
    'uprawnieniaTyp', 'users', 'ustawienia', 'zadania', 'zmiany-kadrowe',
    'contacts/'.$kontakt->id.'/edit',
    'building/time-sheet/general-report?date='.$dzis,
    'building/time-sheet/month-report?date='.$dzis];
foreach (['a1', 'edit', 'kierownictwo', 'klient', 'koszty', 'narzedzia', 'narzedzia/create', 'prognoza'] as $s) {
    $uris[] = 'budowy/'.$bud->id.'/'.$s;
}
$uris[] = 'pracownicy/'.$bud->id;

$kernel = app(Illuminate\Contracts\Http\Kernel::class);
echo 'PHP ', PHP_VERSION, "\n";
foreach ($uris as $u) {
    app('auth')->guard('web')->setUser($admin);
    $req = Request::create('/'.ltrim($u, '/'), 'GET');
    $resp = $kernel->handle($req);
    if (method_exists($resp, 'getFile')) {
        $size = $resp->getFile()->getSize();
        // Odpowiedź nie jest wysyłana, więc deleteFileAfterSend nie zadziała —
        // plik eksportu (nazwiska pracowników) sprzątamy sami.
        if (str_starts_with($resp->getFile()->getPathname(), storage_path('app/export/'))) {
            @unlink($resp->getFile()->getPathname());
        }
    } else {
        ob_start();
        $resp->sendContent();
        $size = strlen(ob_get_clean());
    }
    printf("%-45s %d %8d\n", preg_replace('/\d{4}-\d\d-\d\d/', 'DZIS', preg_replace('#/\d+(/|$)#', '/ID$1', $u)), $resp->getStatusCode(), $size);
}
