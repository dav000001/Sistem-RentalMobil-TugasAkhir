<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class PageController extends Controller
{
    public function about()
    {
        return view('pages.about');
    }

    public function contact()
    {
        return view('pages.contact');
    }

    public function sendContact(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:100'],
            'email'   => ['required', 'email', 'max:100'],
            'subject' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        // Simpan pesan ke database
        $contactMessage = \App\Models\ContactMessage::create([
            'user_id'      => auth()->id(), // null jika guest
            'sender_name'  => $validated['name'],
            'sender_email' => $validated['email'],
            'subject'      => $validated['subject'],
            'message'      => $validated['message'],
        ]);

        // Kirim notifikasi database ke admin (muncul di lonceng panel admin)
        $admins = \App\Models\User::whereDoesntHave('vendor')
            ->whereDoesntHave('customer')
            ->get();

        if ($admins->isEmpty()) {
            $admins = \App\Models\User::all();
        }

        $notification = new \App\Notifications\CustomerContactNotification(
            senderName:  $validated['name'],
            senderEmail: $validated['email'],
            subject:     $validated['subject'],
            message:     $validated['message'],
        );

        foreach ($admins as $admin) {
            $admin->notify($notification);
        }

        // Kirim email ke admin juga (jika SMTP dikonfigurasi)
        try {
            \Illuminate\Support\Facades\Mail::raw(
                "Pesan dari: {$validated['name']} <{$validated['email']}>\n" .
                "Subjek: {$validated['subject']}\n\n" .
                $validated['message'],
                function ($mail) use ($validated) {
                    $mail->to(config('mail.from.address', 'admin@rentalmobil.com'))
                         ->subject("[Hubungi Kami] {$validated['subject']} - dari {$validated['name']}")
                         ->replyTo($validated['email'], $validated['name']);
                }
            );
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Contact form mail failed: ' . $e->getMessage());
        }

        return redirect()->route('contact')->with('contact_success', true);
    }

    public function vendorSupport()    {
        // Hanya vendor yang login yang bisa akses
        $vendor = auth('vendor')->user()?->vendor;
        if (!$vendor) {
            return redirect('/vendor/login');
        }
        return view('pages.vendor-support', compact('vendor'));
    }

    public function sendVendorSupport(\Illuminate\Http\Request $request)
    {
        $vendor = auth('vendor')->user()?->vendor;
        if (!$vendor) {
            return redirect('/vendor/login');
        }

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:100'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
            'attachments.*' => ['nullable', 'image', 'max:2048'],
        ]);

        $storedPaths = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $storedPaths[] = $file->store('support-attachments', 'public');
                }
            }
        }

        // Kirim notifikasi database ke semua admin
        $adminUsers = \App\Models\User::whereHas('vendor', fn ($q) => $q->where('status', 'approved'))
            ->take(0) // tidak ada admin dari vendor
            ->get();

        // Cari user admin (yang login ke /admin)
        // Admin adalah user yang bisa login ke Filament admin panel
        // Kita kirim ke semua user yang punya akses admin
        $admins = \App\Models\User::whereDoesntHave('vendor')
            ->whereDoesntHave('customer')
            ->get();

        // Fallback: kirim ke semua user jika tidak ada admin terdeteksi
        if ($admins->isEmpty()) {
            $admins = \App\Models\User::all();
        }

        $notification = new \App\Notifications\VendorSupportMessageNotification(
            vendorName:  $vendor->business_name,
            vendorEmail: auth('vendor')->user()->email,
            subject:     $validated['subject'],
            message:     $validated['message'],
            vendorId:    $vendor->id,
            attachments: !empty($storedPaths) ? $storedPaths : null,
        );

        foreach ($admins as $admin) {
            $admin->notify($notification);
        }

        return redirect()->route('vendor.support')->with('success', 'Pertanyaan Anda berhasil dikirim ke admin. Kami akan merespons segera.');
    }

    public function faq()
    {
        return view('pages.faq');
    }

    public function terms()
    {
        return view('pages.terms');
    }

    /**
     * Halaman "Pesan Saya" — customer lihat riwayat pesan & balasan admin
     */
    public function myMessages()
    {
        $messages = \App\Models\ContactMessage::where('user_id', auth()->id())
            ->latest()
            ->get();

        // Tandai semua balasan sebagai sudah dibaca
        \App\Models\ContactMessage::where('user_id', auth()->id())
            ->whereNotNull('admin_reply')
            ->where('is_read_by_customer', false)
            ->update(['is_read_by_customer' => true]);

        return view('pages.my-messages', compact('messages'));
    }
}
