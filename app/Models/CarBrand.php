<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CarBrand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'source',
        'is_verified',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    public function carModels(): HasMany
    {
        return $this->hasMany(CarModel::class);
    }

    public function cars(): HasMany
    {
        return $this->hasMany(Car::class);
    }

    /**
     * Cari atau buat brand baru dari input manual vendor.
     */
    public static function findOrCreateFromInput(string $name): self
    {
        $slug = Str::slug($name);

        // Cek duplikat berdasarkan slug
        $existing = self::where('slug', $slug)->first();
        if ($existing) {
            return $existing;
        }

        return self::create([
            'name'        => trim($name),
            'slug'        => $slug,
            'source'      => 'vendor_input',
            'is_verified' => false,
        ]);
    }
}
