<?php

namespace App\Auth;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class CustomLogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        $role = $request->role;

        if ($role) {
            return Redirect('/admin/login');
        }

        return Redirect('/login');
    }
}
