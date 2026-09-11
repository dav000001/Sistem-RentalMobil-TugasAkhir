<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? config('app.name') }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f3f4f6; margin: 0; padding: 0; }
        .wrapper { max-width: 600px; margin: 0 auto; padding: 24px 16px; }
        .card { background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #2563eb, #1d4ed8); padding: 28px 32px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 22px; font-weight: 700; }
        .header p { color: #bfdbfe; margin: 4px 0 0; font-size: 13px; }
        .body { padding: 32px; color: #374151; line-height: 1.6; }
        .body h2 { color: #111827; font-size: 18px; margin-top: 0; }
        .btn { display: inline-block; background: #2563eb; color: #ffffff !important; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; margin: 16px 0; }
        .btn:hover { background: #1d4ed8; }
        .info-box { background: #eff6ff; border-left: 4px solid #2563eb; padding: 12px 16px; border-radius: 0 8px 8px 0; margin: 16px 0; font-size: 14px; }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 24px 0; }
        .footer { padding: 20px 32px; background: #f9fafb; border-top: 1px solid #e5e7eb; text-align: center; }
        .footer p { color: #9ca3af; font-size: 12px; margin: 4px 0; }
        .footer a { color: #6b7280; text-decoration: none; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            {{-- Header --}}
            <div class="header">
                <h1>🚗 {{ config('app.name') }}</h1>
                <p>Platform Rental Mobil Terpercaya</p>
            </div>

            {{-- Body --}}
            <div class="body">
                {{ $slot }}
            </div>

            {{-- Footer --}}
            <div class="footer">
                <p>Email ini dikirim otomatis, mohon tidak membalas.</p>
                <p>
                    <a href="{{ config('app.url') }}">{{ config('app.url') }}</a> ·
                    <a href="{{ config('app.url') }}/hubungi-kami">Hubungi Support</a>
                </p>
                <p style="margin-top: 8px; color: #d1d5db;">© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
