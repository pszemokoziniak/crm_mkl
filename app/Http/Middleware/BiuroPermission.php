<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BiuroPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Lista ról mieszka w Role::OFFICE — dopisanie kolejnej nie wymaga
        // szukania po middleware'ach, gdzie ktoś wpisał numery na sztywno.
        if (! Auth::user()->isOffice()) {
            abort(403);
        }

        return $next($request);
    }
}
