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

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('User Information')
                ->description('Fill in the user details.')
                ->schema([
                // TextInput::make('first_name')
                //     ->label('First Name')
                //     ->required()
                //     ->minLength(2)
                //     ->maxLength(50)
                //     ->rule('alpha')
                //     ->live(onBlur: true)
                //     ->afterStateUpdated(fn (Get $get, Set $set, $state) => 
                //         $set('name', trim("{$state} {$get('last_name')}"))
                //     ),

                    
                // TextInput::make('last_name')
                //     ->label('Last Name')
                //     ->minLength(2)
                //     ->maxLength(50)
                //     ->required()
                //     ->live(onBlur: true)
                //     ->afterStateUpdated(fn (Get $get, Set $set, $state) => 
                //         $set('name', trim("{$get('first_name')} {$state}"))
                //     ),

                // Hidden::make('name'),

                  TextInput::make('name')
                    ->label('Last Name')
                    ->minLength(2)
                    ->maxLength(20)
                    ->required()

                ])
                ->columns(2),
                Section::make('User Address')
                ->description('Fill in the user details.')
                ->schema([
                    TextInput::make('phone')
                    ->tel()
                    ->maxLength(255)
                    ->default(null),
                    TextInput::make('address')
                    ->maxLength(255)
                    ->default(null),
                ])->columns(2),
                Section::make('Dates')
                ->description('Fill in the user details.')
                ->schema([
                DatePicker::make('dob'),
                Select::make('gender')
                     ->label('Gender') 
                     ->options([
                            'male' => 'Male',
                            'female' => 'Female',
                     ])
                    ->native(false)
                    ->required(),
                ])->columns(2),
                Section::make('Additional Information')
                ->description('Fill in the user details.')
                ->schema([ 
                TextInput::make('username')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255)
                    ->default(null),
                TextInput::make('password')
                    ->password()
                    ->maxLength(255)
                    ->default(null),
                // TextInput::make('avatar')
                //     ->maxLength(255)
                //     ->default(null),
                FileUpload::make('avatar')
                     ->avatar()
                     ->directory('avatars')
                     ->disk('public')
                     ->visibility('public')
                ])->columns(2),
                 Section::make('User Role')
                ->description('Fill in the user details.')
                ->schema([
                    Select::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'cashier' => 'Cashier',
                        'member' => 'Member',
                    ])
                    ->required()
                    ->native(false)
                    ->live()
                ]),




            // --- Membership Fields (Only shown if role == 'member') ---
            Section::make('Membership Details')
                ->schema([
                    Placeholder::make('role_hint')
                        ->content('Membership details will be managed after creation.')
                        ->visible(fn (Get $get) => $get('role') !== 'member'),

                    // Only show actual fields if role is 'member'
                    Forms\Components\Grid::make()
                        ->schema([
                            Select::make('member.package_id')
                                ->label('Package')
                                ->options(Package::pluck('name', 'id'))
                                ->searchable()
                                ->nullable()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Set $set, Get $get) {

                                    $packageId = $get('member.package_id'); // get the selected package ID
                                    if (!$packageId) {
                                        return;
                                    }

                                    $package = Package::find($packageId); // find the package by ID
                                    if ($package) { // if the package exists, set the duration unit
                                        $set('duration_unit', $package->duration_unit ?? 'month');
                                    } 

                                    self::calculateMembershipValidity($set, $get);
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
                                ->suffix(fn (Get $get) => match ($get('duration_unit')) {
                                    'day' => 'Day(s)',
                                    'week' => 'Week(s)',
                                    'month' => 'Month(s)',
                                    'year' => 'Year(s)',
                                    default => 'Unit(s)',
                                })
                                ->afterStateUpdated(function (Set $set, Get $get) {
                                    self::calculateMembershipValidity($set, $get);
                                }),

                            DatePicker::make('member.starting_date')
                                ->label('Starting Date')
                                ->required()
                                ->default(now())
                                ->live()
                                ->afterStateUpdated(function (Set $set, Get $get) {
                                    self::calculateMembershipValidity($set, $get);
                                }),
                            DatePicker::make('member.valid_from')
                                ->label('Valid From')
                                ->disabled()
                                ->dehydrated(),

                            DatePicker::make('member.valid_until')
                                ->label('Valid Until')
                                ->disabled()
                                ->dehydrated(),

                            TextInput::make('member.membership_id')
                                ->label('Membership ID')
                                 ->unique(
                                    table: 'members',           // ✅ Check in `members` table
                                    column: 'membership_id',    // ✅ The column to check
                                    ignoreRecord: true,         // ✅ Ignore current record when editing
                                )
                                ->default(fn () => 'MEM-' . now()->format('Y') . '-' . random_int(1000, 9999))
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
                        ->columns(3)
                        ->visible(fn (Get $get) => $get('role') === 'member'),
                                ]),
                // ->hidden(fn (Get $get) => $get('role') !== 'member'),




            // --- Emergency Contact (Only for members) ---
            Section::make('Emergency Contact')
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
                ->columns(2)
                ->hidden(fn (Get $get) => $get('role') !== 'member'),

            // --- Notes ---
            Section::make('Notes')
                ->schema([
                    Forms\Components\Textarea::make('member.notes')
                        ->label('Additional Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->hidden(fn (Get $get) => $get('role') !== 'member'),


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

        if (! $startingDate) {
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
    public static function mutateFormDataBeforeCreate(array $data): array
    {
         // Combine first and last name into 'name'
       $data['name'] = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));

         // 🔐 Fallback if empty
        if (empty($data['name'])) {
            $data['name'] = $data['username'] ?? 'Unnamed User';
        }

        // 🗑️ Remove first_name and last_name from data
        // so Laravel doesn't try to save them to DB
        unset($data['first_name']);
        unset($data['last_name']);

        if ($data['role'] === 'member') {
            // Ensure member data is present
            $data['member'] = [
                // 'user_id' => $data['id'], authomatically set 
                'package_id' => $data['member']['package_id'],
                'duration_value' => $data['member']['duration_value'] ?? 1,
                'starting_date' => $data['member']['starting_date'],
                'valid_from' => $data['member']['valid_from'],
                'valid_until' => $data['member']['valid_until'],
                'membership_id' => $data['member']['membership_id'],
                'status' => $data['member']['status'] ?? 'active',
                'emergency_contact_name' => $data['member']['emergency_contact_name'],
                'emergency_contact_phone' => $data['member']['emergency_contact_phone'],
                'notes' => $data['member']['notes'] ?? null,
            ];
        }

        return $data;
    }

    // // For editing: fill member data if exists
    // public static function mutateFormDataBeforeFill(array $data): array
    // {
    //     $user = static::getRecord();

    
    //     if ($user?->member) {
    //         $data['member'] = $user->member->toArray();
    //     }

    //     return $data;
    // }

    // ✅ After saving, create or update the member
    public static function afterCreate(array $data, Model $model): void
    {
        Log::debug('created');
        if ($data['role'] === 'member') {
            $model->member()->create($data['member']);
        }
    }

    public static function afterEdit(array $data, Model $model): void
    {
        if ($data['role'] === 'member') { // If role is still member, update or create membership
            if ($model->member) { // If member already exists, update it
                $model->member()->update($data['member']);
            } else { // If no member exists, create it
                $model->member()->create($data['member']);
            }
        } elseif ($model->member) {
            // If role changed from member to admin/cashier, delete membership
            $model->member()->delete();
        }
    }

    // public static function mutateFormDataBeforeSave(array $data): array
    //     {
    //         $data['name'] = trim("{$data['first_name'] ?? ''} {$data['last_name'] ?? ''}");
    //         return $data;
    //     }

 

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('role'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('username')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('dob')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gender'),
                Tables\Columns\TextColumn::make('address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\ImageColumn::make('avatar')
                    ->circular()  // Makes it rounded for avatars
                    ->size(40)
                    ->defaultImageUrl(url('/images/default-user.png')),   // Fixed size
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
