<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class VendorScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Scope ini HANYA aktif di konteks vendor panel (/vendor/*)
        // Tidak boleh aktif di:
        //   - Public routes (/, /search, /cars/*, /bookings/*)
        //   - Admin routes (/admin/*)
        //   - Customer routes

        // Cek apakah request berasal dari vendor panel
        if (!$this->isVendorPanelRequest()) {
            return;
        }

        // Guard vendor aktif → filter ke vendor_id miliknya
        $vendor = auth('vendor')->user()?->vendor;
        if ($vendor) {
            $builder->where($model->getTable() . '.vendor_id', $vendor->id);
        }
    }

    private function isVendorPanelRequest(): bool
    {
        // Tidak ada request context (job, console, seeder) → skip
        if (!app()->bound('request')) {
            return false;
        }

        try {
            $request = request();
            $path = $request->getPathInfo();

            // Hanya aktif jika path dimulai dengan /vendor/
            // dan guard vendor sedang aktif
            return str_starts_with($path, '/vendor/')
                && auth('vendor')->check();
        } catch (\Throwable) {
            return false;
        }
    }
}
