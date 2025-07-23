<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'emergency_contact_name',
        'emergency_contact_phone',
        'membership_id',
        'notes',
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
        return $this->hasMany(Payment::class, 'user_id', 'user_id');
    }
}
