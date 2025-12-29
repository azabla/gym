<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // Create the user
        $user = static::getModel()::create($data);

        // Get the Member Role ID for comparison
        $memberRoleId = Role::where('name', 'member')->value('id');

        // Check if 'member' is one of the selected roles in the form
        $selectedRoles = $data['roles'] ?? [];
        
        if (in_array($memberRoleId, $selectedRoles) && isset($data['member'])) {
            $user->member()->create($data['member']);
        }
        
        return $user;
    }
}