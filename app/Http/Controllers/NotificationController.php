<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;

class NotificationController extends Controller
{
    /** Oznacza powiadomienie jako przeczytane i przenosi tam, gdzie prowadzi. */
    public function read(string $id): RedirectResponse
    {
        $notification = Auth::user()->notifications()->whereKey($id)->first();

        if (! $notification) {
            return Redirect::back();
        }

        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;

        return $url ? Redirect::to($url) : Redirect::back();
    }

    public function readAll(): RedirectResponse
    {
        Auth::user()->unreadNotifications->markAsRead();

        return Redirect::back();
    }
}
