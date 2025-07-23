<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'member_id',
        'package_id',
        'amount',
        'payment_method',
        'payment_date',
        'transaction_id',
        'valid_from',
        'valid_until',
        'notes',
        'status',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function member()
    {
        return $this->belongsTo(Member::class);
    }
    
}
