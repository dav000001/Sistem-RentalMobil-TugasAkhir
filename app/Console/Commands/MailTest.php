<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MailTest extends Command
{
    protected $signature = 'mail:test {email : Alamat email tujuan}';
    protected $description = 'Kirim email test untuk verifikasi konfigurasi SMTP';

    public function handle(): int
    {
        $email = $this->argument('email');

        $this->info("Mengirim email test ke: {$email}");

        try {
            Mail::html(
                view('emails.test', ['appName' => config('app.name'), 'appUrl' => config('app.url')])->render(),
                function ($message) use ($email) {
                    $message->to($email)
                        ->subject('✅ Email Test — ' . config('app.name'));
                }
            );

            $this->info('✅ Email berhasil dikirim! Cek inbox Anda.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ Gagal kirim email: ' . $e->getMessage());
            $this->line('Pastikan konfigurasi SMTP di .env sudah benar.');
            return self::FAILURE;
        }
    }
}
