<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordHelpRequest;
use App\Models\User;
use App\Notifications\NewPasswordHelpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class PasswordHelpController extends Controller
{
    public function create()
    {
        return view('auth.password-help');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email'         => ['required', 'email'],
            'business_name' => ['nullable', 'string', 'max:120'],
            'whatsapp'      => ['nullable', 'string', 'max:20'],
            'reason'        => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        $help = PasswordHelpRequest::create([
            'user_id'       => $user?->id,
            'email'         => $validated['email'],
            'business_name' => $validated['business_name'] ?? null,
            'whatsapp'      => $validated['whatsapp'] ?? null,
            'reason'        => $validated['reason'],
        ]);

        // Kirim notifikasi ke semua admin (masuk ke lonceng Filament)
        $admins = User::whereDoesntHave('vendor')->whereDoesntHave('customer')->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new NewPasswordHelpRequest($help));
        }

        // Pesan selalu sama (anti-enumeration)
        return back()->with('success', 'Permintaan Anda telah kami terima. Admin akan segera memproses dalam 1×24 jam kerja.');
    }
}
