<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property numeric $price
 * @property bool $is_recurring
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Member> $members
 * @property-read int|null $members_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Package> $packages
 * @property-read int|null $packages_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addon query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addon whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addon whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addon whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addon whereIsRecurring($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addon whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addon wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addon whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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