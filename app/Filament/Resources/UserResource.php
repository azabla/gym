<?php

namespace App\Filament\Resources;
use Illuminate\Support\Facades\Log;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\Package;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Form;
use Filament\Forms\FormsComponent;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Traits\CalcPayDateRanges;
use Illuminate\Validation\Rule;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Spatie\Permission\Models\Role as SpatieRole;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Users';
    use CalcPayDateRanges;

    public static function form(Form $form): Form
    {
        $memberRoleId = SpatieRole::where('name', 'member')->value('id');

        return $form
            ->schema([
                Grid::make(3)
                    ->schema([
                        // --- LEFT COLUMN: Personal & Contact Info (Takes 2/3 width) ---
                        Group::make()
                            ->columnSpan(['lg' => 2])
                            ->schema([

                                Section::make('Personal Information ')
                                    ->description('Basic identification details.')
                                    ->collapsed(false)
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Full Name')
                                            ->minLength(2)
                                            ->maxLength(30)
                                            ->required()
                                            ->prefixIcon('heroicon-o-user')
                                            ->placeholder('John')
                                            ->columns(2),
                                        TextInput::make('phone')
                                            ->tel()
                                            ->maxLength(255)
                                            ->placeholder('09********')
                                            ->prefixIcon('heroicon-o-phone')
                                            ->default(null),
                                        TextInput::make('address')
                                            ->maxLength(255)
                                            ->prefixIcon('heroicon-o-map-pin')
                                            ->placeholder('123 Main St, City, Country')
                                            ->default(null),
                                        DatePicker::make('dob')
                                            ->label('Date of Birth')
                                            ->placeholder('YYYY-MM-DD')
                                            ->default(null)
                                            ->prefixIcon('heroicon-o-cake')
                                            ->ethiopic()
                                            ->native(false),
                                        Select::make('gender')
                                            ->label('Gender')
                                            ->prefixIcon('heroicon-o-user-circle')
                                            ->options([
                                                'male' => 'Male',
                                                'female' => 'Female',
                                            ])
                                            ->default('male')
                                            ->native(false)
                                            ->required(),
                                    ])->columns(2),


                                Section::make('Account Security & Role 🔐')
                                    ->description('Manage login credentials and access level.')
                                    ->collapsed(false)
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('username')
                                                ->required()
                                                ->prefixIcon('heroicon-o-user-circle')
                                                ->maxLength(255)
                                                ->required()
                                                ->unique(
                                                    ignoreRecord: true,
                                                ),
                                            TextInput::make('email')
                                                ->email()
                                                ->maxLength(255)
                                                ->prefixIcon('heroicon-o-envelope')
                                                ->default(null)
                                                ->required(),
                                            TextInput::make('password')
                                                ->password()
                                                ->prefixIcon('heroicon-o-lock-closed')
                                                ->maxLength(255)
                                                ->required()
                                                ->default(null),
                                            // TextInput::make('avatar')
                                            //     ->maxLength(255)
                                            //     ->default(null),



                                            Forms\Components\Select::make('roles') // Note the 's' for relationship
                                                ->relationship('roles', 'name') // Connects to the Shield roles table
                                                ->multiple()
                                                ->preload()
                                                ->searchable()
                                                ->label('Assign Roles')
                                                ->columnSpanFull()
                                                ->helperText('Select one or more roles for this user.')
                                                ->live(),

                                        ]),


                                    ]),
                                // --- Notes ---
                                Section::make('Notes 📝')
                                    ->description('Additional information about the user')
                                    ->collapsed(false)
                                    ->schema([
                                        Forms\Components\Textarea::make('member.notes')
                                            ->label('Additional Notes')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ])
                                    // ->hidden(fn(Get $get) => $get('role') !== 'member'),
                                    ->visible(function (Get $get) use ($memberRoleId): bool {
                                        // Check if the 'member' role ID is included in the array of selected role IDs
                                        $selectedRoleIds = $get('roles');

                                        // If the tutor role ID is null or no roles are selected, return false
                                        if (!$memberRoleId || empty($selectedRoleIds)) {
                                            return false;
                                        }

                                        // Return true if the member role ID is found in the selected IDs array
                                        return in_array($memberRoleId, $selectedRoleIds);
                                    })

                            ]),



                        Group::make()
                            ->columnSpan(['lg' => 1])
                            ->schema([
                                Section::make('Profile Image 📸')
                                    ->collapsed(false)
                                    ->schema([
                                        FileUpload::make('avatar')
                                            ->label('Avatar')
                                            ->hiddenLabel()
                                            ->avatar()
                                            ->directory('avatars')
                                            ->disk('public')
                                            ->visibility('public')
                                            ->alignCenter(),
                                    ]),
                                // --- Membership Fields (Only shown if role == 'member') ---
                                Section::make('Metadata & Status ✨')
                                    ->collapsed(false)
                                    ->visible(function (Get $get) use ($memberRoleId): bool {
                                        // Check if the 'member' role ID is included in the array of selected role IDs
                                        $selectedRoleIds = $get('roles');

                                        // If the tutor role ID is null or no roles are selected, return false
                                        if (!$memberRoleId || empty($selectedRoleIds)) {
                                            return false;
                                        }

                                        // Return true if the member role ID is found in the selected IDs array
                                        return in_array($memberRoleId, $selectedRoleIds);
                                    })
                                    ->schema([
                                        Placeholder::make('role_hint')
                                            ->content('Membership details will be managed after creation.'),

                                        // Only show actual fields if role is 'member'
                                        Grid::make(1)
                                            ->schema([
                                                Select::make('member.package_id')
                                                    ->label('Package')
                                                    ->options(Package::pluck('name', 'id'))
                                                    ->searchable()
                                                    ->nullable()
                                                    ->required()
                                                    ->live()
                                                    ->extraAttributes(['class' => 'font-bold text-primary-600'])
                                                    ->afterStateUpdated(function (Set $set, Get $get) {

                                                        $packageId = $get('member.package_id'); // get the selected package ID
                                                        if (!$packageId) {
                                                            return;
                                                        }

                                                        $package = Package::find($packageId); // find the package by ID
                                                        if ($package) { // if the package exists, set the duration unit
                                                            $set('duration_unit', $package->duration_unit ?? 'month');
                                                        }

                                                        static::calcPayDateRanges(
                                                            set: $set,
                                                            get: $get,
                                                            startingDatePath: 'member.starting_date',
                                                            durationValuePath: 'member.duration_value',
                                                            durationUnitPath: 'duration_unit',
                                                            outputFromPath: 'member.valid_from',
                                                            outputUntilPath: 'member.valid_until'
                                                        );
                                                    }),

                                                // Add hidden field to store duration_unit
                                                Hidden::make('duration_unit')
                                                    ->dehydrated(false), // Will hold 'day', 'week', 'month', 'year'

                                                TextInput::make('member.duration_value')
                                                    ->label('Duration (Months)')
                                                    ->numeric()
                                                    ->step(1) // forces integer input in browser
                                                    ->minValue(1)
                                                    ->default(1)
                                                    ->required()
                                                    ->live()
                                                    ->suffix(fn(Get $get) => match ($get('duration_unit')) {
                                                        'day' => 'Day(s)',
                                                        'week' => 'Week(s)',
                                                        'month' => 'Month(s)',
                                                        'year' => 'Year(s)',
                                                        default => 'Unit(s)',
                                                    })
                                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                                        static::calcPayDateRanges(
                                                            set: $set,
                                                            get: $get,
                                                            startingDatePath: 'member.starting_date',
                                                            durationValuePath: 'member.duration_value',
                                                            durationUnitPath: 'duration_unit',
                                                            outputFromPath: 'member.valid_from',
                                                            outputUntilPath: 'member.valid_until'
                                                        );
                                                    }),

                                                DatePicker::make('member.starting_date')
                                                    ->label('Starting Date')
                                                    ->required()
                                                    ->default(now())
                                                    ->live()
                                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                                        static::calcPayDateRanges(
                                                            set: $set,
                                                            get: $get,
                                                            startingDatePath: 'member.starting_date',
                                                            durationValuePath: 'member.duration_value',
                                                            durationUnitPath: 'duration_unit',
                                                            outputFromPath: 'member.valid_from',
                                                            outputUntilPath: 'member.valid_until'
                                                        );
                                                    }),
                                                DatePicker::make('member.valid_from')
                                                    ->label('Valid From')
                                                    ->disabled()
                                                    ->dehydrated(),

                                                DatePicker::make('member.valid_until')
                                                    ->label('Valid Until')
                                                    ->disabled()
                                                    ->dehydrated(),

                                                Hidden::make('member.id'),
                                                TextInput::make('member.membership_id')
                                                    ->label('Membership ID')
                                                    ->rule(function (Get $get) {
                                                        $memberId = $get('member.id');
                                                        return Rule::unique('members', 'membership_id')->ignore($memberId);
                                                    })
                                                    //  ->unique(
                                                    //     table: 'members',           // ✅ Check in `members` table
                                                    //     column: 'membership_id',    // ✅ The column to check
                                                    //     ignoreRecord: true,         // ✅ Ignore current record when editing
                                                    // )
                                                    ->default(fn() => 'MEM-' . now()->format('Y') . '-' . random_int(1000, 9999))
                                                    ->required(),

                                                Select::make('member.status')
                                                    ->options([
                                                        'active' => 'Active',
                                                        'inactive' => 'Inactive',
                                                        'suspended' => 'Suspended',
                                                    ])
                                                    ->default('active')
                                                    ->required(),
                                            ])
                                            ->columns(2)
                                            ->visible(function (Get $get) use ($memberRoleId): bool {
                                                // Check if the 'member' role ID is included in the array of selected role IDs
                                                $selectedRoleIds = $get('roles');

                                                // If the tutor role ID is null or no roles are selected, return false
                                                if (!$memberRoleId || empty($selectedRoleIds)) {
                                                    return false;
                                                }

                                                // Return true if the member role ID is found in the selected IDs array
                                                return in_array($memberRoleId, $selectedRoleIds);
                                            }),
                                    ]),
                                // ->hidden(fn (Get $get) => $get('role') !== 'member'),




                                // --- Emergency Contact (Only for members) ---
                                Section::make('Emergency Contact')
                                    ->collapsed(false)
                                    ->schema([
                                        TextInput::make('member.emergency_contact_name')
                                            ->label('Name')
                                            ->maxLength(255),
                                        // ->default(null),
                                        // ->required(fn (Get $get) => $get('role') === 'member'),

                                        TextInput::make('member.emergency_contact_phone')
                                            ->label('Phone')
                                            ->tel(),
                                        // ->required(fn (Get $get) => $get('role') === 'member'),
                                    ])
                                    ->visible(function (Get $get) use ($memberRoleId): bool {
                                        // Check if the 'member' role ID is included in the array of selected role IDs
                                        $selectedRoleIds = $get('roles');

                                        // If the tutor role ID is null or no roles are selected, return false
                                        if (!$memberRoleId || empty($selectedRoleIds)) {
                                            return false;
                                        }

                                        // Return true if the member role ID is found in the selected IDs array
                                        return in_array($memberRoleId, $selectedRoleIds);
                                    }),



                            ]),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('member');
    }

    // Helper: Calculate valid_from and valid_until
    protected static function calculateMembershipValidity(Set $set, Get $get): void
    {
        $startingDate = $get('member.starting_date');
        $duration = (int) ($get('member.duration_value') ?? 1);
        $durationUnit = $get('duration_unit');

        if (!$startingDate) {
            return;
        }

        $from = \Carbon\Carbon::parse($startingDate);
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
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('Roll No.')->label('Roll No.')->rowIndex(),
                ImageColumn::make('avatar')
                    ->label('Profile')
                    ->circular()
                    ->extraImgAttributes([
                        'class' => 'transition-transform duration-300 hover:scale-[4] hover:z-50',
                    ])
                    ->defaultImageUrl(url('/images/default-user.png')),
                Tables\Columns\TextColumn::make('name')
                    ->label('Member Details')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => $record->email) // Puts email under the name
                    ->copyable() // Allows clicking to copy email
                    ->tooltip('Click to copy name'),
                Tables\Columns\TextColumn::make('roles')
                    ->label('Roles')
                    ->formatStateUsing(fn($state, $record) => $record->roles->pluck('name')->join(', '))
                    ->badge()
                    ->colors([
                        'admin' => 'danger',
                        'cashier' => 'warning',
                        'member' => 'success',
                    ])
                    ->searchable()
                    ->toggleable()
                    ->wrap()
                    ->extraAttributes(['class' => 'font-bold'])
                    ->icon(fn($record) => match ($record->role) {
                        'super-admin' => 'heroicon-o-shield-check',
                        'cashier' => 'heroicon-o-currency-dollar',
                        'member' => 'heroicon-o-user-group',
                        default => null,
                    }),
                Tables\Columns\TextColumn::make('username')
                    ->searchable(),
                Tables\Columns\TextColumn::make('dob')
                    ->label('Date of Birth')
                    ->date()
                    // ->tooltip(fn($record): string => $record->dob->diffForHumans())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('gender')
                    ->badge()
                    ->colors([
                        'info' => 'male',
                        'danger' => 'female',
                    ])
                    ->icon(fn($record) => match ($record->gender) {
                        'male' => 'heroicon-o-user',
                        'female' => 'heroicon-o-user-plus',
                        default => null,
                    }),
                Tables\Columns\TextColumn::make('address')
                    ->searchable()
                    ->limit(20)
                    ->tooltip(fn($record) => $record->address),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Phone Number')
                    ->searchable()
                    ->icon('heroicon-o-phone')
                    ->badge()
                    ->copyable()
                    ->copyMessage('Phone number copied')
                    ->copyMessageDuration(1500)
                    ->tooltip('Click to copy Number'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->deferLoading()
            ->striped()
            ->filters([
                SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Roles')
                    ->placeholder('All Roles'),
                SelectFilter::make('gender')
                    ->options([
                        'male' => 'Male',
                        'female' => 'Female',
                    ])
                    ->placeholder('All Genders'),
                SelectFilter::make('address')
                    ->options(function () {
                        return User::query()
                            ->distinct()
                            ->pluck('address', 'address')
                            ->filter(fn($value) => !is_null($value) && $value !== '')
                            ->toArray();
                    })
                    ->searchable(),

            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth('4xl') // Makes the popup size
                    ->tooltip('Quick Edit Users')
                    ->slideOver()
                    ->modalHeading('Update User Profile')
                    ->modalDescription('Changes will be applied immediately to the User record.')
                    ->modalSubmitActionLabel('Save Changes')
                    ->icon('heroicon-m-pencil-square')
                    ->color('warning'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            // 'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
