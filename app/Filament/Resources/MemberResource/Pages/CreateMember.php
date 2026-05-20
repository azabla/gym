<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Prepare User Data
        // If password isn't in the form, generate a random one
        if (!isset($data['user']['password']) || empty($data['user']['password'])) {
            $data['user']['password'] = Hash::make(Str::random(12));
        }

        // Create the User
        $user = User::create($data['user']);

        // Handle Role Assignment (Spatie Shield)
        // We look for 'user_roles' which is the name used in MemberResource
        if (isset($data['user_roles'])) {
            $user->syncRoles($data['user_roles']);
        } else {
            // Fallback: Always ensure they have the 'member' role if nothing else is selected
            $user->assignRole('member');
        }

        //  Create the Member linked to the new User
        $data['user_id'] = $user->id;
        
        // Remove nested user data and virtual fields to prevent column not found errors
        $memberData = collect($data)->except(['user', 'user_roles'])->toArray();

        return static::getModel()::create($memberData);
    }
}