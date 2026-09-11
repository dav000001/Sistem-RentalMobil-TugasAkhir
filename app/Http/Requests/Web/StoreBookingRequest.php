<?php

namespace App\Http\Requests\Web;

use App\Models\Car;
use App\Services\CarAvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $customer = auth()->user()?->customer;
        return $customer && $customer->verification_status === 'verified';
    }

    public function rules(): array
    {
        return [
            'start_at'        => ['required', 'date', 'after:now'],
            'end_at'          => ['required', 'date', 'after:start_at'],
            'with_driver'     => ['nullable', 'boolean'],
            'passenger_count' => ['nullable', 'integer', 'min:1', 'max:100'],
            'pickup_location' => ['required', 'string', 'max:500'],
            'dropoff_location'=> ['nullable', 'string', 'max:500'],
            'notes'           => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $car      = $this->route('car');
            $customer = auth()->user()?->customer;
            if (!$car instanceof Car || !$customer) return;

            // Cek apakah mobil sedang unavailable
            if ($car->isCurrentlyUnavailable()) {
                $validator->errors()->add('start_at', 'Mobil ini sedang tidak tersedia untuk disewa.');
                return;
            }

            $startAt = $this->date('start_at');
            $endAt   = $this->date('end_at');
            if (!$startAt || !$endAt) return;

            // ── Validasi batas durasi jika tidak tersedia bulanan ──────────
            if (!$car->is_monthly_available) {
                $days = $startAt->diffInDays($endAt);
                if ($days > 7) {
                    $validator->errors()->add(
                        'end_at',
                        'Mobil ini hanya tersedia untuk sewa maksimal 7 hari. Silakan pilih tanggal selesai yang sesuai.'
                    );
                    return;
                }
            }

            // Cek duplikat — customer sudah punya booking aktif untuk mobil ini pada tanggal yang sama
            $duplicate = $customer->bookings()
                ->where('car_id', $car->id)
                ->whereIn('status', ['awaiting_payment', 'awaiting_vendor', 'confirmed', 'ongoing'])
                ->where(function ($q) use ($startAt, $endAt) {
                    $q->whereBetween('start_at', [$startAt, $endAt])
                      ->orWhereBetween('end_at', [$startAt, $endAt])
                      ->orWhere(function ($q2) use ($startAt, $endAt) {
                          $q2->where('start_at', '<=', $startAt)
                             ->where('end_at', '>=', $endAt);
                      });
                })
                ->exists();

            if ($duplicate) {
                $validator->errors()->add(
                    'start_at',
                    'Anda sudah memiliki pesanan aktif untuk mobil ini pada tanggal tersebut.'
                );
                return;
            }

            // Cek ketersediaan tanggal via service
            $service = app(CarAvailabilityService::class);
            if (!$service->isAvailable($car, $startAt, $endAt)) {
                $validator->errors()->add(
                    'start_at',
                    'Mobil sudah dipesan pada tanggal yang Anda pilih. Silakan pilih tanggal lain.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'start_at.after'          => 'Tanggal mulai harus setelah sekarang.',
            'end_at.after'            => 'Tanggal selesai harus setelah tanggal mulai.',
            'pickup_location.required'=> 'Lokasi penjemputan wajib diisi.',
        ];
    }
}
