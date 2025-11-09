<?php

namespace App\Filament\Resources\UserResource\Pages;
use Illuminate\Support\Facades\Log;

use App\Filament\Resources\UserResource;
use App\Models\Member;
use Filament\Actions;
use Filament\Forms\Components\Builder;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;
    protected function mutateFormDataBeforeFill(array $data): array
        {
            $user = static::getRecord();


        if ($user?->member) {
                $data['member'] = $user->member->toArray();
            }
            return $data;
        }
   protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // $user = static::getModel();
        $record->update($data);

        if ($data['role'] === 'member' && isset($data['member'])){
            $record->member->update($data['member']);
        }


        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
