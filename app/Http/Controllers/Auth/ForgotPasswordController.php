<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        // Selalu tampilkan pesan yang sama (anti-enumeration)
        return back()->with('status', 'Jika email terdaftar, link reset password telah dikirim. Cek inbox Anda.');
    }
}
