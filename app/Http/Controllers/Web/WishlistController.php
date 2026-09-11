<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    /** Halaman daftar wishlist */
    public function index()
    {
        $wishlists = Wishlist::where('user_id', auth()->id())
            ->with(['car.photos', 'car.pricing', 'car.city', 'car.vendor'])
            ->latest()
            ->get();

        // Ambil car IDs untuk eager load avg rating
        $carIds = $wishlists->pluck('car_id')->filter()->unique()->values();

        $cars = Car::whereIn('id', $carIds)
            ->with(['photos', 'pricing', 'city', 'vendor'])
            ->withAvg('reviews', 'rating')
            ->get()
            ->sortBy(fn ($c) => $carIds->search($c->id))
            ->values();

        return view('public.wishlist.index', compact('cars'));
    }

    /** Toggle wishlist (add/remove) — AJAX friendly */
    public function toggle(Car $car)
    {
        $userId = auth()->id();

        $existing = Wishlist::where('user_id', $userId)->where('car_id', $car->id)->first();

        if ($existing) {
            $existing->delete();
            $added = false;
        } else {
            Wishlist::create(['user_id' => $userId, 'car_id' => $car->id]);
            $added = true;
        }

        if (request()->wantsJson()) {
            return response()->json([
                'added'   => $added,
                'message' => $added ? 'Ditambahkan ke wishlist' : 'Dihapus dari wishlist',
                'count'   => Wishlist::where('user_id', $userId)->count(),
            ]);
        }

        return back()->with('success', $added ? '❤️ Ditambahkan ke wishlist' : 'Dihapus dari wishlist');
    }

    /** Hapus dari wishlist */
    public function remove(Car $car)
    {
        Wishlist::where('user_id', auth()->id())->where('car_id', $car->id)->delete();

        if (request()->wantsJson()) {
            return response()->json(['removed' => true]);
        }

        return back()->with('success', 'Dihapus dari wishlist');
    }
}
