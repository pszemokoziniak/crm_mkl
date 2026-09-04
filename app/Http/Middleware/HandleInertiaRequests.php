<?php

namespace App\Http\Middleware;

use App\Http\Controllers\PodszywanieController;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    public function version(Request $request)
    {
        return parent::version($request);
    }

    /**
     * Defines the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function share(Request $request)
    {
        return array_merge(parent::share($request), [
            'auth' => function () use ($request) {
                return [
                    'user' => $request->user() ? [
                        'id' => $request->user()->id,
                        'first_name' => $request->user()->first_name,
                        'last_name' => $request->user()->last_name,
                        'email' => $request->user()->email,
                        'owner' => $request->user()->owner,
                        'account' => [
                            'id' => $request->user()->account->id,
                            'name' => $request->user()->account->name,
                        ],
                    ] : null,
                ];
            },
            'flash' => function () use ($request) {
                return [
                    'success' => $request->session()->get('success'),
                    'error' => $request->session()->get('error'),
                ];
            },
            'permissions' => auth()->user()->permissions ?? [],
            // Pasek "pracujesz jako…" — żeby admin nie zapomniał, że siedzi
            // na cudzym koncie.
            'podszywanie' => function () use ($request) {
                if (! $request->user() || ! $request->session()->has(PodszywanieController::KLUCZ_SESJI)) {
                    return null;
                }

                return ['kto' => $request->user()->name];
            },
            // Dzwonek w nagłówku — wywołania @ i przypisania zgłoszeń.
            'notifications' => function () use ($request) {
                if (! $request->user()) {
                    return ['unread' => 0, 'items' => []];
                }

                return [
                    'unread' => $request->user()->unreadNotifications()->count(),
                    'items' => $request->user()->notifications()
                        ->latest()
                        ->limit(10)
                        ->get()
                        ->map(fn ($notification) => [
                            'id' => $notification->id,
                            'read' => $notification->read_at !== null,
                            'created_at' => $notification->created_at?->format('d.m.Y H:i'),
                        ] + (array) $notification->data),
                ];
            },
        ]);
    }
}
