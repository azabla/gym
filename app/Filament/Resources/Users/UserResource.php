<?php

namespace App\Filament\Resources\Users;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\CreateUser;

use App\Models\User;

use Filament\Resources\Resource;
use Filament\Tables\Table;
use App\Filament\Traits\CalcPayDateRanges;

use Carbon\Carbon;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UserTable;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Users';
    use CalcPayDateRanges;

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    // public static function getEloquentQuery(): Builder
    // {
    //     return parent::getEloquentQuery()->with('member');
    // }

    // Helper: Calculate valid_from and valid_until
    protected static function calculateMembershipValidity(Set $set, Get $get): void
    {
        $startingDate = $get('member.starting_date');
        $duration = (int) ($get('member.duration_value') ?? 1);
        $durationUnit = $get('duration_unit');

        if (!$startingDate) {
            return;
        }

        $from = Carbon::parse($startingDate);
        $until = $from->copy();

        // Dynamically add based on duration type
        match ($durationUnit) {
            'day' => $until->addDays($duration),
            'week' => $until->addWeeks($duration),
            'month' => $until->addMonths($duration),
            'year' => $until->addYears($duration),
            default => $until->addMonths($duration),
        };
        $set('member.valid_from', $from->toDateString());
        $set('member.valid_until', $until->toDateString());
    }

    // Save member data when user is saved
    // public static function mutateFormDataBeforeCreate(array $data): array
    // {
    //      // Combine first and last name into 'name'
    //    $data['name'] = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));

    //      // 🔐 Fallback if empty
    //     if (empty($data['name'])) {
    //         $data['name'] = $data['username'] ?? 'Unnamed User';
    //     }

    //     // 🗑️ Remove first_name and last_name from data
    //     // so Laravel doesn't try to save them to DB
    //     unset($data['first_name']);
    //     unset($data['last_name']);

    //     if ($data['role'] === 'member') {
    //         // Ensure member data is present
    //         $data['member'] = [
    //             // 'user_id' => $data['id'], authomatically set 
    //             'package_id' => $data['member']['package_id'],
    //             'duration_value' => $data['member']['duration_value'] ?? 1,
    //             'starting_date' => $data['member']['starting_date'],
    //             'valid_from' => $data['member']['valid_from'],
    //             'valid_until' => $data['member']['valid_until'],
    //             'membership_id' => $data['member']['membership_id'],
    //             'status' => $data['member']['status'] ?? 'active',
    //             'emergency_contact_name' => $data['member']['emergency_contact_name'],
    //             'emergency_contact_phone' => $data['member']['emergency_contact_phone'],
    //             'notes' => $data['member']['notes'] ?? null,
    //         ];
    //     }

    //     return $data;
    // }

    // // // For editing: fill member data if exists
    // // public static function mutateFormDataBeforeFill(array $data): array
    // // {
    // //     $user = static::getRecord();


    // //     if ($user?->member) {
    // //         $data['member'] = $user->member->toArray();
    // //     }

    // //     return $data;
    // // }

    // // ✅ After saving, create or update the member
    // public static function afterCreate(array $data, Model $model): void
    // {
    //     Log::debug('created');
    //     if ($data['role'] === 'member') {
    //         $model->member()->create($data['member']);
    //     }
    // }

    // public static function afterEdit(array $data, Model $model): void
    // {
    //     if ($data['role'] === 'member') { // If role is still member, update or create membership
    //         if ($model->member) { // If member already exists, update it
    //             $model->member()->update($data['member']);
    //         } else { // If no member exists, create it
    //             $model->member()->create($data['member']);
    //         }
    //     } elseif ($model->member) {
    //         // If role changed from member to admin/cashier, delete membership
    //         $model->member()->delete();
    //     }
    // }

    // public static function mutateFormDataBeforeSave(array $data): array
    //     {
    //         $data['name'] = trim("{$data['first_name'] ?? ''} {$data['last_name'] ?? ''}");
    //         return $data;
    //     }



    public static function table(Table $table): Table
    {
       return UserTable::configure($table);
    }

    // public static function mutateFormDataBeforeFill(array $data): array
    //     {
    //         Log::debug('mutateCalled');
    //         $user = static::getRecord();
    //         if ($user?->member) {
    //             Log::debug('Member data:', $user->member->toArray());
    //             foreach ($user->member->toArray() as $key => $value) {
    //                 $data["member.{$key}"] = $value;
    //             }
    //         }
    //         Log::debug('Form data:', $data);
    //         return $data;
    //     }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            // 'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}