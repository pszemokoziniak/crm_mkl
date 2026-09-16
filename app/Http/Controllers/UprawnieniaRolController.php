<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\Uprawnienie;
use App\Models\ZmianaUprawnien;
use App\Uprawnienia\Macierz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Ustawienia → Uprawnienia ról: macierz rola × uprawnienie do odhaczania.
 * Admin jest pokazany, ale zablokowany — ma wszystko z definicji.
 */
class UprawnieniaRolController extends Controller
{
    public function index(): Response
    {
        $obszary = [];
        foreach (Uprawnienie::cases() as $u) {
            $obszary[$u->obszar()][] = [
                'id' => $u->value,
                'etykieta' => $u->etykieta(),
                'tylkoAdmin' => $u->tylkoAdmin(),
                'wymaga' => array_map(fn (Uprawnienie $w) => $w->value, $u->wymaga()),
            ];
        }

        $role = [];
        foreach ([Role::BIURO, Role::KADRY, Role::KIEROWNICTWO, Role::KIEROWNIK, Role::KIEROWNIK_PROJEKTU, Role::ADMIN] as $rola) {
            $role[] = [
                'id' => $rola->value,
                'nazwa' => $rola->label(),
                'edytowalna' => $rola !== Role::ADMIN,
                'nadpisana' => Macierz::jestNadpisana($rola),
                'uprawnienia' => array_map(fn (Uprawnienie $u) => $u->value, Macierz::dla($rola)),
                'domyslne' => array_map(fn (Uprawnienie $u) => $u->value, Macierz::domyslneDla($rola)),
            ];
        }

        $historia = ZmianaUprawnien::with('autor')->latest('id')->limit(30)->get()->map(fn (ZmianaUprawnien $z) => [
            'kiedy' => $z->created_at?->format('d.m.Y H:i'),
            'kto' => $z->autor ? trim($z->autor->first_name.' '.$z->autor->last_name) : '—',
            'rola' => Role::tryFrom((int) $z->rola)?->label() ?? (string) $z->rola,
            'przywrocenie' => $z->przywrocenie,
            'dodane' => $this->etykiety($z->dodane),
            'odebrane' => $this->etykiety($z->odebrane),
        ]);

        return Inertia::render('Uprawnienia/Index', [
            'obszary' => collect($obszary)->map(fn ($lista, $nazwa) => ['nazwa' => $nazwa, 'uprawnienia' => $lista])->values(),
            'role' => $role,
            'historia' => $historia,
        ]);
    }

    public function update(int $rola): RedirectResponse
    {
        $rolaEnum = $this->rolaDoEdycji($rola);

        $dane = Request::validate([
            'uprawnienia' => ['present', 'array'],
            'uprawnienia.*' => ['string', Rule::in(Uprawnienie::values())],
        ]);

        Macierz::zapisz(
            $rolaEnum,
            array_map(fn (string $v) => Uprawnienie::from($v), $dane['uprawnienia']),
            Auth::user(),
        );

        return Redirect::route('uprawnieniaRol')->with('success', 'Uprawnienia roli '.$rolaEnum->label().' zapisane.');
    }

    public function reset(int $rola): RedirectResponse
    {
        $rolaEnum = $this->rolaDoEdycji($rola);

        Macierz::przywrocDomyslne($rolaEnum, Auth::user());

        return Redirect::route('uprawnieniaRol')->with('success', 'Rola '.$rolaEnum->label().' wróciła do domyślnych uprawnień.');
    }

    private function rolaDoEdycji(int $rola): Role
    {
        $rolaEnum = Role::tryFrom($rola);
        abort_if($rolaEnum === null, 404);
        abort_if($rolaEnum === Role::ADMIN, 422, 'Administrator ma zawsze wszystkie uprawnienia.');

        return $rolaEnum;
    }

    /**
     * @param string[]|null $wartosci
     * @return string[]
     */
    private function etykiety(?array $wartosci): array
    {
        return array_values(array_filter(array_map(function (string $v) {
            $u = Uprawnienie::tryFrom($v);

            return $u ? $u->obszar().': '.$u->etykieta() : null;
        }, $wartosci ?? [])));
    }
}
