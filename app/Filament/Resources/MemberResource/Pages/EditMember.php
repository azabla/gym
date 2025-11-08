<?php

namespace App\Filament\Resources\MemberResource\Pages;

use App\Filament\Resources\MemberResource;
use App\Models\Package;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditMember extends EditRecord
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // for the duration_unit(Days, Weeks, Months ... ) to be filled 
        if(!empty($data['package_id'])){
            $package = Package::find($data['package_id']);
            $duration_unit = $package->duration_unit;
        }
        $data['duration_unit'] = $duration_unit;

        // for the user data

        $member = static::getRecord();

        if($member?->user){
            $data['user'] = $member->user->toArray();
        }

        return $data;
    }


     protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // $user = static::getModel();
        $record->update($data);

        if ($record?->user && isset($data['user'])){
            $record->user->update($data['user']);
        }


        return $record;
    }
}
