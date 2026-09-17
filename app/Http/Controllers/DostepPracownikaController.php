<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\DostepPracownikaMail;
use App\Models\Contact;
use App\Models\DostepPracownika;
use App\Services\Sms;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Karta pracownika → zakładka "Dostęp z telefonu": wydanie osobistego
 * linku i wysłanie go mailem albo SMS-em. Link widać raz; każde wysłanie
 * wydaje nowy, poprzedni przestaje działać.
 */
class DostepPracownikaController extends Controller
{
    public function index(Contact $contact, Sms $sms): Response
    {
        $dostep = DostepPracownika::with('wydal')->where('contact_id', $contact->id)->first();

        return Inertia::render('Contacts/Dostep', [
            'pracownik' => $this->danePracownika($contact),
            'kontakt' => ['email' => $contact->email, 'phone' => $contact->phone],
            'dostep' => $dostep ? [
                'wydany' => $dostep->wydany_at?->format('d.m.Y H:i'),
                'wydal' => $dostep->wydal ? trim($dostep->wydal->first_name.' '.$dostep->wydal->last_name) : null,
                'ma_pin' => $dostep->maPin(),
                'ostatnie_wejscie' => $dostep->ostatnie_wejscie_at?->format('d.m.Y H:i'),
                'wyslany_mail' => $dostep->wyslany_mail_at?->format('d.m.Y H:i'),
                'wyslany_sms' => $dostep->wyslany_sms_at?->format('d.m.Y H:i'),
            ] : null,
            'sms_dostepny' => $sms->skonfigurowany(),
            // Pełny link tylko raz, zaraz po wydaniu — potem w bazie jest sam skrót.
            'nowy_link' => session('nowy_link'),
        ]);
    }

    public function wydaj(Contact $contact): RedirectResponse
    {
        $token = DostepPracownika::wydaj($contact, Auth::user());

        return Redirect::back()->with('nowy_link', (new DostepPracownika())->adres($token))
            ->with('success', 'Wydano nowy link. Poprzedni przestał działać.');
    }

    public function mail(Contact $contact): RedirectResponse
    {
        abort_if(! $contact->email, 422, 'Pracownik nie ma adresu e-mail w kartotece.');

        $token = DostepPracownika::wydaj($contact, Auth::user());
        Mail::to($contact->email)->send(new DostepPracownikaMail($contact->first_name, (new DostepPracownika())->adres($token)));
        DostepPracownika::where('contact_id', $contact->id)->update(['wyslany_mail_at' => now()]);

        return Redirect::back()->with('success', 'Link wysłany na '.$contact->email.'. Poprzedni link przestał działać.');
    }

    public function sms(Contact $contact, Sms $sms): RedirectResponse
    {
        abort_if(! $contact->phone, 422, 'Pracownik nie ma numeru telefonu w kartotece.');
        abort_if(! $sms->skonfigurowany(), 422, 'Wysyłka SMS nie jest skonfigurowana.');

        $token = DostepPracownika::wydaj($contact, Auth::user());
        try {
            $sms->wyslij($contact->phone, 'HRM MKL: Twoj link do wnioskow urlopowych: '.(new DostepPracownika())->adres($token).' Przy pierwszym wejsciu ustaw PIN. Nie przesylaj dalej.');
        } catch (\RuntimeException $e) {
            return Redirect::back()->with('error', $e->getMessage());
        }
        DostepPracownika::where('contact_id', $contact->id)->update(['wyslany_sms_at' => now()]);

        return Redirect::back()->with('success', 'SMS z linkiem wysłany na '.$contact->phone.'. Poprzedni link przestał działać.');
    }

    public function uniewaznij(Contact $contact): RedirectResponse
    {
        DostepPracownika::where('contact_id', $contact->id)->delete();

        return Redirect::back()->with('success', 'Dostęp unieważniony — link i PIN przestały działać.');
    }
}
