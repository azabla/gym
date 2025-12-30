<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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
public function getStatusAttribute($value)
{
    if ($this->valid_until && Carbon::parse($this->valid_until)->isPast()) {
        return 'expired';
    }
    return $value ?: 'active';
}


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

}
