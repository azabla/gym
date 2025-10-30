<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

}
