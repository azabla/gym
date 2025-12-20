<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Carbon\Carbon;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements FilamentUser
{
    public function canAccessPanel(Panel $panel): bool
    {
         // Allow multiple admin emails
    $adminEmails = [
        'admin@example.com',
        'admin@gym.com', 
        'your-email@gmail.com' // Add your actual email
    ];
    
    // OR check by role
    return in_array($this->email, $adminEmails) || $this->role === 'admin';
    }
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

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
}
