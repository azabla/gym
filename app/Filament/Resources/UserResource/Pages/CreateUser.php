<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

      // ✅ After saving, create or update the member
    protected function handleRecordCreation(array $data): Model
    {
        // Log::debug('data_user', $data);
        $user = static::getModel()::create($data);

        if ($data['role'] === 'member' && isset($data['member'])){
            $user->member()->create($data['member']);
        }
        
        return $user;
    }
}
