<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Car;
use Illuminate\Http\Request;

class CompareController extends Controller
{
    const MAX_COMPARE = 3;

    /** Halaman perbandingan */
    public function index(Request $request)
    {
        // Baca dari session (session-based compare)
        $ids = session('compare_ids', []);
        $ids = array_values(array_unique(array_filter($ids)));
        $ids = array_slice($ids, 0, self::MAX_COMPARE);

        $cars = collect();
        if (!empty($ids)) {
            $cars = Car::whereIn('id', $ids)
                ->with(['photos', 'pricing', 'city', 'vendor', 'category'])
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->get()
                ->sortBy(fn ($c) => array_search($c->id, $ids))
                ->values();
        }

        return view('public.compare.index', compact('cars', 'ids'));
    }

    /** Tambah/hapus dari compare list (session-based) */
    public function toggle(Car $car)
    {
        $ids = session('compare_ids', []);

        if (in_array($car->id, $ids)) {
            $ids = array_values(array_filter($ids, fn ($id) => $id !== $car->id));
            $added = false;
        } else {
            if (count($ids) >= self::MAX_COMPARE) {
                if (request()->wantsJson()) {
                    return response()->json(['error' => 'Maksimal ' . self::MAX_COMPARE . ' mobil untuk dibandingkan'], 422);
                }
                return back()->with('error', 'Maksimal ' . self::MAX_COMPARE . ' mobil untuk dibandingkan');
            }
            $ids[] = $car->id;
            $added = true;
        }

        session(['compare_ids' => $ids]);

        if (request()->wantsJson()) {
            return response()->json([
                'added'   => $added,
                'count'   => count($ids),
                'ids'     => $ids,
                'message' => $added ? 'Ditambahkan ke perbandingan' : 'Dihapus dari perbandingan',
            ]);
        }

        return back()->with('success', $added ? 'Ditambahkan ke perbandingan' : 'Dihapus dari perbandingan');
    }

    /** Hapus semua dari compare */
    public function clear()
    {
        session()->forget('compare_ids');

        if (request()->wantsJson()) {
            return response()->json(['cleared' => true]);
        }

        return back();
    }
}
