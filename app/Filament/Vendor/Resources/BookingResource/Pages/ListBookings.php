<?php

namespace App\Filament\Vendor\Resources\BookingResource\Pages;

use App\Filament\Vendor\Resources\BookingResource;
use App\Models\Booking;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    public function getTabs(): array
    {
        $vendor = auth('vendor')->user()?->vendor;
        $vendorId = $vendor?->id ?? 0;

        return [
            'all' => Tab::make('Semua')
                ->badge(Booking::where('vendor_id', $vendorId)->count()),

            'awaiting_vendor' => Tab::make('Menunggu Konfirmasi')
                ->badge(Booking::where('vendor_id', $vendorId)->where('status', 'awaiting_vendor')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'awaiting_vendor')),

            'car_change_request' => Tab::make('🔄 Minta Ganti Mobil')
                ->badge(Booking::where('vendor_id', $vendorId)->whereHas('carChangeRequest', fn ($q) => $q->where('status', 'pending'))->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereHas('carChangeRequest', fn ($q) => $q->where('status', 'pending'))),

            'confirmed' => Tab::make('Dikonfirmasi')
                ->badge(Booking::where('vendor_id', $vendorId)->where('status', 'confirmed')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'confirmed')),

            'ongoing' => Tab::make('Berlangsung')
                ->badge(Booking::where('vendor_id', $vendorId)->where('status', 'ongoing')->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'ongoing')),

            'completed' => Tab::make('Selesai')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')),

            'cancelled' => Tab::make('Dibatalkan')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled')),
        ];
    }
}
