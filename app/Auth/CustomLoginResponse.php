<?php

namespace App\Auth;

use App\Providers\RouteServiceProvider;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class CustomLoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        $role = $request->user()->admin_status;

        if ($role) {
            return redirect()->intended(RouteServiceProvider::ADMIN_HOME);
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }
}
