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

}
