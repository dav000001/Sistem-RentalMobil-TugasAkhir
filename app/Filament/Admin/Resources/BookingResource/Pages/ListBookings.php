<?php

namespace App\Filament\Admin\Resources\BookingResource\Pages;

use App\Filament\Admin\Resources\BookingResource;
use Filament\Resources\Pages\ListRecords;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    public function getTabs(): array
    {
        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Semua')
                ->badge(\App\Models\Booking::count()),

            'awaiting_payment' => \Filament\Schemas\Components\Tabs\Tab::make('Menunggu Bayar')
                ->badge(\App\Models\Booking::where('status', 'awaiting_payment')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'awaiting_payment')),

            'car_change_payment' => \Filament\Schemas\Components\Tabs\Tab::make('💳 Bukti Selisih Mobil')
                ->badge(\App\Models\Booking::whereHas('carChangeRequest', fn ($q) => $q->whereNotNull('additional_payment_proof')->whereNull('additional_payment_at'))->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn ($query) => $query->whereHas('carChangeRequest', fn ($q) => $q->whereNotNull('additional_payment_proof')->whereNull('additional_payment_at'))),

            'confirmed' => \Filament\Schemas\Components\Tabs\Tab::make('Dikonfirmasi')
                ->badge(\App\Models\Booking::where('status', 'confirmed')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'confirmed')),

            'ongoing' => \Filament\Schemas\Components\Tabs\Tab::make('Berlangsung')
                ->badge(\App\Models\Booking::where('status', 'ongoing')->count())
                ->badgeColor('primary')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'ongoing')),

            'completed' => \Filament\Schemas\Components\Tabs\Tab::make('Selesai')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'completed')),

            'cancelled' => \Filament\Schemas\Components\Tabs\Tab::make('Dibatalkan')
                ->modifyQueryUsing(fn ($query) => $query->where('status', 'cancelled')),
        ];
    }
}
