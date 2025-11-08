<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateMember extends CreateRecord
{
    protected static string $resource = MemberResource::class;

    // protected function mutateFormDataBeforeCreate(array $data): array

    // {
    //     $data['user.role'] = "Member";

    //     return $data;
    // }
     
    protected function handleRecordCreation(array $data): Model
    {
        
        $data['user']['role'] = "member";
        $user = User::create($data['user']);
        $data['user_id'] = $user->id;
        if(isset($data['user']['role']) && $data['user']['role'] === 'member'){
            $member = static::getModel()::create($data);
            
        }
        // if (isset($data['user']['role']) && $data['user']['role'] === 'member'){
        // }

        return $member;
    }
}
