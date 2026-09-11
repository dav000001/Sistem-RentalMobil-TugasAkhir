<?php

use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\SearchController;
use App\Http\Controllers\Web\CarController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\BookingController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\PaymentController;
use App\Http\Controllers\Web\AccountSwitcherController;
use App\Http\Controllers\Web\VendorLandingController;
use App\Http\Controllers\Web\VendorRegistrationController;
use App\Http\Controllers\Web\WishlistController;
use App\Http\Controllers\Web\CompareController;
use App\Http\Controllers\Web\CarChangeRequestController;
use App\Http\Controllers\Web\CustomerRecapController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/cars/{car:slug}', [CarController::class, 'show'])->name('cars.show');

// Compare — publik (session-based), tidak perlu login
Route::get('/compare', [CompareController::class, 'index'])->name('compare.index');
Route::post('/compare/toggle/{car}', [CompareController::class, 'toggle'])->name('compare.toggle');
Route::post('/compare/clear', [CompareController::class, 'clear'])->name('compare.clear');

// Static pages
Route::get('/tentang-kami', [\App\Http\Controllers\Web\PageController::class, 'about'])->name('about');
Route::get('/hubungi-kami', [\App\Http\Controllers\Web\PageController::class, 'contact'])->name('contact');
Route::post('/hubungi-kami', [\App\Http\Controllers\Web\PageController::class, 'sendContact'])->name('contact.send');

// Pesan saya (customer lihat balasan admin) — harus login
Route::middleware('auth')->get('/pesan-saya', [\App\Http\Controllers\Web\PageController::class, 'myMessages'])->name('contact.messages');Route::get('/faq', [\App\Http\Controllers\Web\PageController::class, 'faq'])->name('faq');
Route::get('/syarat-ketentuan', [\App\Http\Controllers\Web\PageController::class, 'terms'])->name('terms');

// Vendor Landing & Registration
Route::get('/jadi-vendor', [VendorLandingController::class, 'show'])->name('vendor.landing');
Route::get('/become-vendor', fn () => redirect()->route('vendor.landing'));

// Guest-only routes
Route::middleware('guest')->group(function () {
    Route::get('/jadi-vendor/daftar', [VendorRegistrationController::class, 'create'])->name('vendor.register');
    Route::post('/jadi-vendor/daftar', [VendorRegistrationController::class, 'store']);
    Route::get('/password-help', [\App\Http\Controllers\Auth\PasswordHelpController::class, 'create'])->name('password.help');
    Route::post('/password-help', [\App\Http\Controllers\Auth\PasswordHelpController::class, 'store'])->middleware('throttle:3,60');
});

// Auth routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    // Lupa Password
    Route::get('/forgot-password', fn () => view('auth.forgot-password'))->name('password.request');
    Route::post('/forgot-password', [\App\Http\Controllers\Auth\ForgotPasswordController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [\App\Http\Controllers\Auth\ResetPasswordController::class, 'store'])->name('password.store');
    // Google Login
    Route::get('/auth/google', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'redirect'])->name('auth.google');
    Route::get('/auth/google/callback', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'callback']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Verifikasi Email
    Route::get('/verify-email', fn () => view('auth.verify-email'))->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', [\App\Http\Controllers\Auth\EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [\App\Http\Controllers\Auth\EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')->name('verification.send');
    
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/bank', [ProfileController::class, 'updateBank'])->name('profile.bank.update');
    Route::post('/profile/verification', [ProfileController::class, 'submitVerification'])->name('profile.verification.submit');
    
    // Account Switcher
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::get('/add-account', [AccountSwitcherController::class, 'showAddForm'])->name('add-account');
        Route::post('/add-account', [AccountSwitcherController::class, 'addAccount']);
        Route::post('/switch/{accountId}', [AccountSwitcherController::class, 'switch'])->name('switch');
        Route::post('/forget/{accountId}', [AccountSwitcherController::class, 'forget'])->name('forget');
        Route::post('/logout-all', [AccountSwitcherController::class, 'logoutAll'])->name('logout-all');
    });

    // Upgrade customer to vendor
    Route::post('/jadi-vendor/upgrade', [VendorRegistrationController::class, 'upgrade'])->name('vendor.upgrade');

    // Bookings
    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/', [BookingController::class, 'index'])->name('index');
        Route::get('/create/{car:slug}', [BookingController::class, 'create'])->name('create');
        Route::post('/store/{car:slug}', [BookingController::class, 'store'])->name('store');
        Route::get('/{booking:code}', [BookingController::class, 'show'])->name('show');
        Route::post('/{booking:code}/cancel', [BookingController::class, 'cancel'])->name('cancel');
        Route::get('/{booking:code}/invoice', [BookingController::class, 'invoice'])->name('invoice');
        Route::get('/{booking:code}/pay', [PaymentController::class, 'show'])->name('pay');
        Route::post('/{booking:code}/pay-demo', [PaymentController::class, 'demoPayment'])->name('pay.demo');
        Route::post('/{booking:code}/pay-proof', [PaymentController::class, 'uploadProof'])->name('pay.proof');
        // Review — hanya untuk booking completed milik customer sendiri
        Route::get('/{booking:code}/review', [\App\Http\Controllers\Web\ReviewController::class, 'create'])->name('review');
        Route::post('/{booking:code}/review', [\App\Http\Controllers\Web\ReviewController::class, 'store'])->name('review.store');
        // Ganti Mobil — hanya bisa saat booking confirmed dan sebelum H-1
        Route::post('/{code}/car-change', [CarChangeRequestController::class, 'store'])->name('car-change.store');
        Route::post('/{code}/car-change/proof', [CarChangeRequestController::class, 'uploadProof'])->name('car-change.upload-proof');
        Route::delete('/{code}/car-change', [CarChangeRequestController::class, 'cancel'])->name('car-change.cancel');
    });

    // Laporan Rekapitulasi Customer (Personal)
    Route::prefix('recap')->name('customer.recap.')->group(function () {
        Route::get('/', [CustomerRecapController::class, 'index'])->name('index');
        Route::get('/pdf', [CustomerRecapController::class, 'exportPdf'])->name('pdf');
        Route::get('/csv', [CustomerRecapController::class, 'exportCsv'])->name('csv');
    });

    // Wishlist
    Route::prefix('wishlist')->name('wishlist.')->group(function () {
        Route::get('/', [WishlistController::class, 'index'])->name('index');
        Route::post('/toggle/{car}', [WishlistController::class, 'toggle'])->name('toggle');
        Route::delete('/remove/{car}', [WishlistController::class, 'remove'])->name('remove');
    });

    // Customer compensation charge proof upload
    Route::post('/compensation/{charge}/proof', [\App\Http\Controllers\Web\CompensationController::class, 'uploadProof'])->name('compensation.proof');

    // Customer late fee proof upload & dispute
    Route::post('/late-fee/{charge}/proof', [\App\Http\Controllers\Web\LateFeeController::class, 'uploadProof'])->name('late-fee.proof');
    Route::post('/late-fee/{charge}/dispute', [\App\Http\Controllers\Web\LateFeeController::class, 'submitDispute'])->name('late-fee.dispute');

    // Customer lapor keterlambatan (Opsi A) + konfirmasi pengembalian (Opsi B)
    Route::post('/bookings/{booking:code}/late-return/report',
        [\App\Http\Controllers\Web\LateReturnReportController::class, 'store']
    )->name('late-return.report');
    Route::post('/bookings/{booking:code}/late-return/confirm-return',
        [\App\Http\Controllers\Web\LateReturnReportController::class, 'confirmReturn']
    )->name('late-return.confirm-return');

// Portal Laporan Keterlambatan & Pengembalian khusus Sopir (akses langsung via link / HP Sopir)
Route::get('/driver/late-report/{booking:code}', [\App\Http\Controllers\Web\DriverReportController::class, 'show'])->name('driver.late-report.show');
Route::post('/driver/late-report/{booking:code}', [\App\Http\Controllers\Web\DriverReportController::class, 'store'])->name('driver.late-report.store');

    // Customer complaints
    Route::prefix('complaints')->name('complaints.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Web\ComplaintController::class, 'index'])->name('index');
        Route::get('/create/{booking}', [\App\Http\Controllers\Web\ComplaintController::class, 'create'])->name('create');
        Route::post('/store/{booking}', [\App\Http\Controllers\Web\ComplaintController::class, 'store'])->name('store');
        Route::get('/{complaint}', [\App\Http\Controllers\Web\ComplaintController::class, 'show'])->name('show');
        Route::post('/{complaint}/respond', [\App\Http\Controllers\Web\ComplaintController::class, 'addResponse'])->name('respond');
    });
});

// Vendor onboarding (accessible to all auth users with vendor profile)
Route::middleware('auth')->prefix('vendor')->name('vendor.')->group(function () {
    Route::get('/onboarding', [App\Http\Controllers\Web\VendorOnboardingController::class, 'show'])->name('onboarding');
    Route::post('/onboarding/profile', [App\Http\Controllers\Web\VendorOnboardingController::class, 'updateProfile'])->name('onboarding.profile');
    Route::post('/onboarding/documents', [App\Http\Controllers\Web\VendorOnboardingController::class, 'uploadDocument'])->name('onboarding.documents');
    Route::post('/onboarding/submit', [App\Http\Controllers\Web\VendorOnboardingController::class, 'submitForReview'])->name('onboarding.submit');
});

// Vendor Plans — guard vendor (login via Filament panel)
Route::middleware('auth:vendor')->prefix('vendor')->name('vendor.')->group(function () {
    // Kalender ketersediaan mobil
    Route::get('/cars/{car}/calendar', [\App\Http\Controllers\Vendor\CarCalendarController::class, 'show'])->name('cars.calendar');
    Route::post('/cars/{car}/calendar/block', [\App\Http\Controllers\Vendor\CarCalendarController::class, 'block'])->name('cars.calendar.block');
    Route::delete('/cars/{car}/calendar/unblock', [\App\Http\Controllers\Vendor\CarCalendarController::class, 'destroy'])->name('cars.calendar.unblock');

    Route::get('/plans', [\App\Http\Controllers\Vendor\PlanController::class, 'index'])->name('plans.index');
    Route::post('/plans/upgrade', [\App\Http\Controllers\Vendor\PlanController::class, 'upgrade'])->name('plans.upgrade');
    // Billing & Subscription (lock-in system)
    Route::get('/billing', [\App\Http\Controllers\Vendor\BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/purchase', [\App\Http\Controllers\Vendor\BillingController::class, 'purchase'])->name('billing.purchase');
    Route::get('/billing/payment/{subscription:uuid}', [\App\Http\Controllers\Vendor\BillingController::class, 'payment'])->name('billing.payment');
    Route::post('/billing/payment/{subscription:uuid}/proof', [\App\Http\Controllers\Vendor\BillingController::class, 'uploadPaymentProof'])->name('billing.upload-proof');
    Route::post('/billing/upgrade-request', [\App\Http\Controllers\Vendor\BillingController::class, 'storeUpgradeRequest'])->name('billing.upgrade-request');
    // Komplain dari customer
    Route::get('/complaints', [\App\Http\Controllers\Vendor\ComplaintController::class, 'index'])->name('complaints.index');
    Route::get('/complaints/{complaint}', [\App\Http\Controllers\Vendor\ComplaintController::class, 'show'])->name('complaints.show');
    Route::post('/complaints/{complaint}/respond', [\App\Http\Controllers\Vendor\ComplaintController::class, 'respond'])->name('complaints.respond');
    // Laporan vendor ke admin
    Route::get('/my-reports', [\App\Http\Controllers\Vendor\MyReportController::class, 'index'])->name('my-reports.index');
    Route::get('/my-reports/{complaint}', [\App\Http\Controllers\Vendor\MyReportController::class, 'show'])->name('my-reports.show');
    Route::post('/my-reports/{complaint}/respond', [\App\Http\Controllers\Vendor\MyReportController::class, 'addResponse'])->name('my-reports.respond');
    // Bantuan / Support ke Admin
    Route::get('/support', [\App\Http\Controllers\Web\PageController::class, 'vendorSupport'])->name('support');
    Route::post('/support', [\App\Http\Controllers\Web\PageController::class, 'sendVendorSupport'])->name('support.send');
    // Laporan Keuangan Vendor
    Route::get('/reports', [\App\Http\Controllers\Vendor\ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export-pdf', [\App\Http\Controllers\Vendor\ReportController::class, 'exportPdf'])->name('reports.export-pdf');
    Route::get('/reports/export-csv', [\App\Http\Controllers\Vendor\ReportController::class, 'exportCsv'])->name('reports.export-csv');

    // Rekap Transaksi (halaman Filament) — export via controller agar bisa download file
    Route::get('/recap/export-pdf', [\App\Http\Controllers\Vendor\RecapExportController::class, 'exportPdf'])->name('recap.export-pdf');
    Route::get('/recap/export-csv', [\App\Http\Controllers\Vendor\RecapExportController::class, 'exportCsv'])->name('recap.export-csv');
});

// Public API — ketersediaan mobil untuk datepicker customer
Route::get('/api/cars/{car}/availability', function (\App\Models\Car $car, \Illuminate\Http\Request $request) {
    $service = app(\App\Services\CarAvailabilityService::class);
    $from = \Carbon\Carbon::parse($request->get('from', now()->toDateString()));
    $to   = \Carbon\Carbon::parse($request->get('to', now()->addMonths(3)->toDateString()));
    return response()->json([
        'unavailable' => $service->getUnavailableDates($car, $from, $to),
    ]);
})->name('api.cars.availability');

// Public API — cek ketersediaan berdasarkan rentang tanggal spesifik (untuk form booking)
Route::get('/api/cars/{car}/check-availability', function (\App\Models\Car $car, \Illuminate\Http\Request $request) {
    $request->validate([
        'start' => 'required|date',
        'end'   => 'required|date|after:start',
    ]);

    $service   = app(\App\Services\CarAvailabilityService::class);
    $start     = \Carbon\Carbon::parse($request->start);
    $end       = \Carbon\Carbon::parse($request->end);
    $available = !$car->isCurrentlyUnavailable() && $service->isAvailable($car, $start, $end);

    return response()->json([
        'available' => $available,
        'car_id'    => $car->id,
    ]);
})->name('api.cars.check-availability');

// Public API — daftar sopir tersedia untuk rentang tanggal (untuk form booking)
Route::get('/api/cars/{car}/available-drivers', function (\App\Models\Car $car, \Illuminate\Http\Request $request) {
    $request->validate([
        'start' => 'required|date',
        'end'   => 'required|date|after:start',
    ]);

    $start   = \Carbon\Carbon::parse($request->start);
    $end     = \Carbon\Carbon::parse($request->end);
    $vendorId = $car->vendor_id;

    $drivers = \App\Models\Driver::where('vendor_id', $vendorId)
        ->where('status', 'active')
        ->get()
        ->filter(fn ($d) => $d->isAvailableOn($start, $end))
        ->values()
        ->map(fn ($d) => [
            'id'               => $d->id,
            'name'             => $d->name,
            'phone'            => $d->phone,
            'experience_years' => $d->experience_years,
            'rating'           => $d->rating,
        ]);

    return response()->json(['drivers' => $drivers]);
})->name('api.cars.available-drivers');

// Midtrans webhook (no CSRF)
Route::post('/webhooks/midtrans', [PaymentController::class, 'midtransWebhook'])
    ->name('webhooks.midtrans')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Admin - View vendor documents (private storage)
Route::middleware('auth:admin')->group(function () {
    Route::get('/admin/vendor-documents/{document}/preview', [\App\Http\Controllers\Admin\VendorDocumentController::class, 'preview'])->name('admin.vendor-documents.preview');
    Route::get('/admin/vendor-documents/{document}/download', [\App\Http\Controllers\Admin\VendorDocumentController::class, 'download'])->name('admin.vendor-documents.download');

    // Laporan Keuangan
    Route::get('/admin/reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('admin.reports.index');

    // Rekap Rekapitulasi — export via controller
    Route::get('/admin/recap/vendor-pdf',    [\App\Http\Controllers\Admin\RecapExportController::class, 'vendorPdf'])->name('admin.recap.vendor-pdf');
    Route::get('/admin/recap/vendor-csv',    [\App\Http\Controllers\Admin\RecapExportController::class, 'vendorCsv'])->name('admin.recap.vendor-csv');
    Route::get('/admin/recap/customer-pdf',  [\App\Http\Controllers\Admin\RecapExportController::class, 'customerPdf'])->name('admin.recap.customer-pdf');
    Route::get('/admin/recap/customer-csv',  [\App\Http\Controllers\Admin\RecapExportController::class, 'customerCsv'])->name('admin.recap.customer-csv');

    // Admin complaints (dari customer)
    Route::prefix('admin/complaints')->name('admin.complaints.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ComplaintController::class, 'index'])->name('index');
        Route::get('/{complaint}', [\App\Http\Controllers\Admin\ComplaintController::class, 'show'])->name('show');
        Route::post('/{complaint}/forward', [\App\Http\Controllers\Admin\ComplaintController::class, 'forward'])->name('forward');
        Route::post('/{complaint}/resolve', [\App\Http\Controllers\Admin\ComplaintController::class, 'resolve'])->name('resolve');
        Route::post('/{complaint}/reject', [\App\Http\Controllers\Admin\ComplaintController::class, 'reject'])->name('reject');
        Route::post('/{complaint}/respond', [\App\Http\Controllers\Admin\ComplaintController::class, 'addResponse'])->name('respond');
    });

    // Admin vendor-reports (laporan dari vendor)
    Route::prefix('admin/vendor-reports')->name('admin.vendor-reports.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\VendorReportController::class, 'index'])->name('index');
        Route::get('/{complaint}', [\App\Http\Controllers\Admin\VendorReportController::class, 'show'])->name('show');
        Route::post('/{complaint}/resolve', [\App\Http\Controllers\Admin\VendorReportController::class, 'resolve'])->name('resolve');
        Route::post('/{complaint}/reject', [\App\Http\Controllers\Admin\VendorReportController::class, 'reject'])->name('reject');
        Route::post('/{complaint}/respond', [\App\Http\Controllers\Admin\VendorReportController::class, 'addResponse'])->name('respond');
    });

    // Admin compensation charge
    Route::post('/admin/compensation/store', [\App\Http\Controllers\Admin\CompensationController::class, 'store'])->name('admin.compensation.store');
});

// Recommendations click tracking (auth customer only)
Route::middleware('auth')->post(
    '/recommendations/{recommendation}/clicked',
    function (\App\Models\BookingRecommendation $recommendation) {
        // Pastikan recommendation milik customer yang login
        if ($recommendation->customer_id === auth()->user()->customer?->id) {
            $recommendation->markAsClicked();
        }
        return response()->json(['success' => true]);
    }
)->name('recommendations.clicked');

// ─────────────────────────────────────────────────────────────
// GPS Tracking & Emergency API routes (JSON responses)
// ─────────────────────────────────────────────────────────────
Route::middleware('auth')->prefix('api/v1')->group(function () {

    // GPS validation utility
    Route::post('/gps/validate', [\App\Http\Controllers\Api\LiveTrackingController::class, 'validateGps'])
        ->name('api.gps.validate');

    // Live tracking per booking
    Route::prefix('bookings/{booking}')->group(function () {
        Route::post('/tracking/record',  [\App\Http\Controllers\Api\LiveTrackingController::class, 'recordTrackingPoint'])
            ->name('api.tracking.record');
        Route::get('/tracking',          [\App\Http\Controllers\Api\LiveTrackingController::class, 'getTrackingData'])
            ->name('api.tracking.get');
        Route::post('/tracking/toggle',  [\App\Http\Controllers\Api\LiveTrackingController::class, 'toggleTracking'])
            ->name('api.tracking.toggle');
        Route::get('/tracking/journey',  [\App\Http\Controllers\Api\LiveTrackingController::class, 'getTrackingJourney'])
            ->name('api.tracking.journey');

        // Emergency
        Route::post('/emergency',        [\App\Http\Controllers\Api\EmergencyController::class, 'createEmergencyReport'])
            ->name('api.emergency.create');
        Route::get('/emergency',         [\App\Http\Controllers\Api\EmergencyController::class, 'getEmergencyReports'])
            ->name('api.emergency.list');

        // Smart routing
        Route::post('/routing/recommendations', [\App\Http\Controllers\Api\EmergencyController::class, 'getRoutingRecommendations'])
            ->name('api.routing.recommendations');
    });

    // Emergency management (vendor & admin)
    Route::prefix('emergency')->group(function () {
        Route::put('/{emergency}/status', [\App\Http\Controllers\Api\EmergencyController::class, 'updateEmergencyStatus'])
            ->name('api.emergency.update-status');
        Route::get('/stats',              [\App\Http\Controllers\Api\EmergencyController::class, 'getEmergencyStats'])
            ->name('api.emergency.stats');
    });
});

