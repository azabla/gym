<?php

namespace App\Filament\Resources\UserResource\Pages;
use Illuminate\Support\Facades\Log;

use App\Filament\Resources\UserResource;
use App\Models\Member;
use Filament\Actions;
use Filament\Forms\Components\Builder;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;
    protected function mutateFormDataBeforeFill(array $data): array
        {
            $user = static::getRecord();
            Log::debug('mutateCalled', $user->toArray());

            // $member = $user?->member;
            // $member = Member::where('user_id', $user->id)->first();
            
            // if ($member) {
            //     Log::debug('member', $member->toArray());
            //     Log::debug('Member data:', $user->member->toArray());
            //     foreach ($user->member->toArray() as $key => $value) {
            //         $data["member.{$key}"] = $value;
            //     }
            // }

        if ($user?->member) {
                $data['member'] = $user->member->toArray();
            }
            Log::debug('Form data:', $data);
            return $data;
        }

   

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
