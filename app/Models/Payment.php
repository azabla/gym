<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $member_id
 * @property int|null $package_id
 * @property numeric $amount
 * @property string $payment_method
 * @property string|null $payment_date
 * @property string|null $transaction_id
 * @property string $valid_from
 * @property string $valid_until
 * @property string|null $notes
 * @property array<array-key, mixed>|null $addons
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Member $member
 * @property-read \App\Models\Package|null $package
 * @method static \Database\Factories\PaymentFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereAddons($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereMemberId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePackageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaymentDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereValidFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereValidUntil($value)
 * @mixin \Eloquent
 */
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
        'duration_value', 
        'addons',    
        
    ];

    protected $casts = [
        'addons' => 'array',
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
    


        // Get addons as collection
    public function getAddonsListAttribute()
    {
        if (!$this->addons) {
            return collect();
        }
        
        return collect($this->addons);
    }

    // Calculate addons total
    public function getAddonsTotalAttribute()
    {
        if (!$this->addons) {
            return 0;
        }
        
        return collect($this->addons)->sum('price');
    }

    // Get package price from stored data
    public function getPackagePriceAttribute()
    {
        return $this->package?->price ?? 0;
    }
}