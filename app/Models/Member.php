<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $package_id
 * @property int $duration_value
 * @property Carbon|null $starting_date
 * @property Carbon|null $valid_from
 * @property Carbon|null $valid_until
 * @property string $status
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property string $membership_id
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Addon> $addons
 * @property-read int|null $addons_count
 * @property-read \App\Models\Package|null $package
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\MemberFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member whereDurationValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member whereEmergencyContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member whereEmergencyContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member whereMembershipId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member wherePackageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member whereStartingDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member whereValidFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Member whereValidUntil($value)
 * @mixin \Eloquent
 */
class Member extends Model
{

    use HasFactory;
    protected $fillable = [
        'user_id',
        'package_id',
        'starting_date',
        'valid_from',
        'valid_until',
        'status',
        'duration_value',
        'emergency_contact_name',
        'emergency_contact_phone',
        'membership_id',
        'notes',
    ];

    protected $casts = [
        'starting_date' => 'datetime',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'status' => 'string',
        'emergency_contact_phone' => 'string'
    ];
     public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // method to get the current status
// public function getStatusAttribute($value)
// {
//     if ($this->valid_until && Carbon::parse($this->valid_until)->isPast()) {
//         return 'expired';
//     }
//     return $value ?: 'active';
// }


public function isExpired(): bool
{
    return $this->valid_until && Carbon::parse($this->valid_until)->isPast();
}

public function getExpiryStatus(): string
{
    if (!$this->valid_until) {
        return 'No expiry set';
    }
    
    $expiry = Carbon::parse($this->valid_until);
    if ($expiry->isPast()) {
        return 'Expired on ' . $expiry->format('Y-m-d');
    }
    
    return 'Expires on ' . $expiry->format('Y-m-d');
}

public function addons()
{
    return $this->belongsToMany(Addon::class, 'member_addon')
                ->withPivot('starts_at', 'ends_at')
                ->withTimestamps();
}



    // Get current active addons
    public function getActiveAddonsAttribute()
    {
        return $this->addons()->where(function($query) {
            $query->whereNull('ends_at')
                  ->orWhere('ends_at', '>=', now());
        })->get();
    }

    // Calculate total amount including package and addons
    public function calculateTotalAmount(?Package $package = null, ?array $selectedAddonIds = null): float
    {
        $package = $package ?? $this->package;
        
        if (!$package) {
            return 0;
        }
        
        // Package price
        $total = $package->price;
        
        // Addons price
        $addonIds = $selectedAddonIds ?? $this->addons->pluck('id')->toArray();
        
        if (!empty($addonIds)) {
            $addons = Addon::whereIn('id', $addonIds)->get();
            foreach ($addons as $addon) {
                $total += $addon->price;
            }
        }
        
        return $total;
    }

    // Get detailed breakdown for display
    public function getPriceBreakdown(?Package $package = null, ?array $selectedAddonIds = null): array
    {
        $package = $package ?? $this->package;
        
        if (!$package) {
            return [
                'package' => null,
                'addons' => [],
                'subtotal' => 0,
                'total' => 0
            ];
        }
        
        $addonIds = $selectedAddonIds ?? $this->addons->pluck('id')->toArray();
        $addons = Addon::whereIn('id', $addonIds)->get();
        $addonsTotal = $addons->sum('price');
        
        return [
            'package' => [
                'id' => $package->id,
                'name' => $package->name,
                'price' => $package->price,
                'duration_value' => $package->duration_value,
                'duration_unit' => $package->duration_unit,
            ],
            'addons' => $addons->map(function($addon) {
                return [
                    'id' => $addon->id,
                    'name' => $addon->name,
                    'price' => $addon->price,
                ];
            }),
            'subtotal' => $package->price,
            'addons_total' => $addonsTotal,
            'total' => $package->price + $addonsTotal
        ];
    }

    public function getPackageAmountAttribute(): float
{
    return (float) ($this->package?->price ?? 0);
}

public function getAddonAmountAttribute(): float
{
    return $this->addons->sum(fn ($addon) => $addon->price);
}

public function getTotalAmountAttribute(): float
{
    return $this->package_amount + $this->addon_amount;
}


}