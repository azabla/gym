<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role',
        'name',
        'username',
        'email',
        'password',
        'dob',
        'gender',
        'address',
        'phone',
        'avatar',
    ];

    //relationships
    
    public function member()
    {
        return $this->hasOne(Member::class);
    }

    // === Accessor: Age from DOB ===
    public function getAgeAttribute()
    {
        return $this->dob ? Carbon::parse($this->dob)->age : null;
    }
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'dob' => 'date',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->map(fn (string $name) => Str::of($name)->substr(0, 1))
            ->implode('');
    }


    // first_name and last_name accessor

    public function getFirstNameAttribute(){
        return Str::of($this->name)->before(' ')->toString() ?: $this->name;
    }
    public function getLastNameAttribute(){
        return Str::of($this->name)->after(' ')->toString();
        
    }

    // app/Models/User.php

protected static function booted()
{
    static::created(function ($user) {
        // If the user was created without any roles (like from the Member form)
        if ($user->roles()->count() === 0) {
            $user->assignRole('member');
        }
        
        // Also sync your legacy string column for Flutter/Mobile consistency
        if (!$user->role) {
            $user->updateQuietly(['role' => 'member']);
        }
    });
}
}
