<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Addon extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'is_recurring',
        'is_active',
    ];

    protected $casts = [
        'is_recurring' => 'boolean',
        'is_active'    => 'boolean',
    ];

    public function packages()
    {
        return $this->belongsToMany(Package::class, 'package_addon')
                    ->withPivot('price_override')
                    ->withTimestamps();
    }

    public function members()
    {
        return $this->belongsToMany(Member::class, 'member_addon')
                    ->withPivot('starts_at', 'ends_at')
                    ->withTimestamps();
    }
}