<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetAppIndexView
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        $user = $request->user();

        if (! $user->admin_status && ! $user->hasVerifiedEmail()) {
            return Redirect('/email/verify');
        }

        if ($user->admin_status) {
            $request->attributes->set('view', 'admin.admin-application-list');
        } else {
            $request->attributes->set('view', 'user.user-application-list');
        }

        return $next($request);
    }
}
