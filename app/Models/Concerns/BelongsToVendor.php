<?php

namespace App\Models\Concerns;

use App\Scopes\VendorScope;

trait BelongsToVendor
{
    public static function bootBelongsToVendor(): void
    {
        static::addGlobalScope(new VendorScope());

        // Auto-set vendor_id saat creating jika belum di-set
        static::creating(function ($model) {
            if (!$model->vendor_id) {
                // Coba dari guard vendor (Filament panel)
                if (auth('vendor')->check()) {
                    $model->vendor_id = auth('vendor')->user()?->vendor?->id;
                }
                // Fallback ke guard web
                elseif (auth()->check()) {
                    $model->vendor_id = auth()->user()?->vendor?->id;
                }
            }
        });
    }
}
