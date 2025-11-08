<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $fillable = [
        // 'user_id',
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
    
    // public function user()
    // {
    //     return $this->belongsTo(User::class);
    // }
    
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }



        protected static function booted(): void
        {
            static::saved(function (Payment $payment) {
                // Only update if the payment is 'completed'
                if ($payment->status !== 'completed') {
                    return;
                }
    
                $member = $payment->member;
                if (!$member) {
                    return;
                }
    
                // Get the latest 'completed' payment for this member (including this one)
                $latestPayment = $member->payments()
                    ->where('status', 'completed')
                    ->orderByDesc('valid_until')
                    ->first();
    
                // Update the member's valid_until to match the latest active subscription end
                $member->update([
                    'valid_until' => $latestPayment?->valid_until,
                ]);
            });
    
            // Handle deletion if a payment is deleted or status changed to 'failed')
            static::deleted(function (Payment $payment) {
                if ($payment->status !== 'completed') {
                    return;
                }
    
                $member = $payment->member;
                if (!$member) {
                    return;
                }
    
                $latestPayment = $member->payments()
                    ->where('status', 'completed')
                    ->orderByDesc('valid_until')
                    ->first();
    
                $member->update([
                    'valid_until' => $latestPayment?->valid_until,
                ]);
            });
        }
    
}
