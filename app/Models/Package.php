<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property numeric $price
 * @property string $duration_unit
 * @property string|null $image
 * @property array<array-key, mixed>|null $features
 * @property string $status
 * @property string|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Addon> $addons
 * @property-read int|null $addons_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Member> $members
 * @property-read int|null $members_count
 * @method static \Database\Factories\PackageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereDurationUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereFeatures($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereImage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Package whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Package extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'id',
        'name',
        'description',
        'price',
        'duration_unit', 
        'image', 
        'features', 
        'status', 
    ];
    /**
     * Get the company that owns the package.
     */

    /* repeaters in Filament expect array data, 
    but the database might be storing it as a JSON string without proper casting.
    */
    protected $casts = [   
    'features' => 'array', // Assuming features is stored as JSON
      ];
    /**
     * Get the members associated with the package.
     */
    public function members()
    {
        return $this->hasMany(Member::class);
    }


    /* 
    If you already saved packages before adding the cast, 
    their features might be malformed (e.g., plain strings like "wifi" instead of JSON arrays).

    always must be return an array, because repeaters in Filament expect array data
    */
    public function getFeaturesAttribute($value)
    {
        if (is_array($value)){
            return $value;
        }

        if(empty($value)){
            return [];
        }

        if (is_string($value)){
            $decoded = json_decode($value, true);


            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)){
                return $decoded;
            }
        }
    }

    public function addons()
    {
        return $this->belongsToMany(Addon::class, 'package_addon')
                    ->withPivot('price_override')
                    ->withTimestamps();
    }

}
