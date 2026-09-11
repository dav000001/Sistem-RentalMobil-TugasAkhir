<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class OwnedByVendor implements ValidationRule
{
    public function __construct(private string $modelClass) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Resolve vendor dari guard yang aktif
        $vendor = auth('vendor')->user()?->vendor
            ?? auth()->user()?->vendor;

        if (!$vendor) {
            $fail('Vendor tidak terdeteksi.');
            return;
        }

        $exists = $this->modelClass::withoutGlobalScopes()
            ->where('id', $value)
            ->where('vendor_id', $vendor->id)
            ->exists();

        if (!$exists) {
            // Pesan netral — tidak bocorkan info ownership
            $fail('Resource yang dipilih tidak ditemukan.');
        }
    }
}
