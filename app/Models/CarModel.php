<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CarModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'car_brand_id',
        'name',
        'slug',
        'source',
        'is_verified',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(CarBrand::class, 'car_brand_id');
    }

    public function cars(): HasMany
    {
        return $this->hasMany(Car::class);
    }

    /**
     * Cari atau buat model baru dari input manual vendor.
     */
    public static function findOrCreateFromInput(int $brandId, string $name): self
    {
        $slug = Str::slug($name);

        $existing = self::where('car_brand_id', $brandId)->where('slug', $slug)->first();
        if ($existing) {
            return $existing;
        }

        return self::create([
            'car_brand_id' => $brandId,
            'name'         => trim($name),
            'slug'         => $slug,
            'source'       => 'vendor_input',
            'is_verified'  => false,
        ]);
    }
}
