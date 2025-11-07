<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MemberResource\Pages;
use App\Filament\Resources\MemberResource\RelationManagers;
use App\Models\Member;
use App\Models\Package;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use App\Filament\Trait;
use App\Filament\Traits\CalcPayDateRanges;
use Illuminate\Validation\Rule;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'User Management';
    protected static ?string $navigationLabel = 'All Members';
    use CalcPayDateRanges;
    public static function form(Form $form): Form
    {
    
        return $form

        
            ->schema([
                Section::make('User Information')
                ->description('Fill in the user details.')
                ->columns([
                    'sm' => 2,
                    'md' => 3,
                    'xl' => 4,
                ])
                ->schema([
                    TextInput::make('user.name')
                        ->label('Full Name')
                        ->placeholder('Abel Asrat')
                        ->minLength(2)
                        ->maxLength(20)
                        ->required(),
                    TextInput::make('user.phone')
                    ->tel()
                    ->placeholder('09########')
                    ->maxLength(255)
                    ->default(null),
                    TextInput::make('user.address')
                    ->placeholder('Kality O9')
                    ->maxLength(255)
                    ->default(null),
                    Select::make('user.gender')
                         ->label('Gender') 
                         ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                         ])
                        ->default('male')
                        ->native(false)
                        ->required(),
                    DatePicker::make('dob'),
                         ]),

                Section::make('Additional Information')
                ->description('Fill in the user details.')
                ->columns([
                    'sm' => 1,
                    'md' => 3,
                    'xl' => 4,
                ])
                ->schema([ 
                Hidden::make('user.id'),
                TextInput::make('user.username')
                    ->required()
                    ->rule (function (Get $get){
                        $userId = $get('user.id');
                        return Rule::unique('users', 'username')->ignore($userId);
                    })
                    ->maxLength(255),
                TextInput::make('user.email')
                    ->email()
                    ->maxLength(255)
                    ->default(null),
                TextInput::make('user.password')
                    ->password()
                    ->maxLength(255)
                    ->default(null),
                FileUpload::make('user.avatar')
                     ->avatar()
                     ->directory('avatars')
                     ->disk('public')
                     ->visibility('public')
                     ->columnSpan(2)
                     ->columnStart([
                         'sm' => 1,
                         'md' => 2,
                     ])
                ]),
                
                // the member specific data

                Section::make('User Information')
                ->description('Fill in the user details.')
                ->columns([
                    'sm' => 2,
                    'md' => 3,
                    'xl' => 4,
                ])
                ->schema([
                    TextInput::make('membership_id')
                        ->label('Membership ID')
                            ->unique(
                            table: 'members',           // ✅ Check in `members` table
                            column: 'membership_id',    // ✅ The column to check
                            ignoreRecord: true,         // ✅ Ignore current record when editing
                        )
                        ->default(fn () => 'MEM-' . now()->format('Y') . '-' . random_int(1000, 9999))
                        ->required(),

                    Select::make('package_id')
                    ->label('Package')
                    ->options(Package::pluck('name', 'id'))
                    ->searchable()
                    ->nullable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get) {

                        $packageId = $get('package_id'); // get the selected package ID
                        if (!$packageId) {
                            return;
                        }

                        $package = Package::find($packageId); // find the package by ID
                        if ($package) { // if the package exists, set the duration unit
                            $set('duration_unit', $package->duration_unit ?? 'month');
                        } 

                        // self::calculateMembershipValidity($set, $get);
                        static::calcPayDateRanges(
                            set: $set,
                            get: $get,
                            startingDatePath: 'starting_date',
                            durationValuePath: 'duration_value',
                            durationUnitPath: 'duration_unit',
                            outputFromPath: 'valid_from',
                            outputUntilPath: 'valid_until'
                        );
                    }),

                    // Add hidden field to store duration_unit
                    Hidden::make('duration_unit')
                    ->dehydrated(false), // Will hold 'day', 'week', 'month', 'year'

                    TextInput::make('duration_value')
                        ->label('Durations')
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
                            default => 'Month(s)',
                        })
                        ->afterStateUpdated(function (Set $set, Get $get) {
                            // self::calculateMembershipValidity($set, $get);
                            static::calcPayDateRanges(
                            set: $set,
                            get: $get,
                            startingDatePath: 'starting_date',
                            durationValuePath: 'duration_value',
                            durationUnitPath: 'duration_unit',
                            outputFromPath: 'valid_from',
                            outputUntilPath: 'valid_until'
                        );
                        }),
                    ]),

                Section::make('User Information')
                ->description('Fill in the user details.')
                ->columns([
                    'sm' => 2,
                    'md' => 3,
                    'xl' => 4,
                ])
                ->schema([

                    DatePicker::make('starting_date')
                        ->label('Starting Date')
                        ->required()
                        ->default(now())
                        ->live()
                        ->afterStateUpdated(function (Set $set, Get $get) {
                            // self::calculateMembershipValidity($set, $get);
                            static::calcPayDateRanges(
                            set: $set,
                            get: $get,
                            startingDatePath: 'starting_date',
                            durationValuePath: 'duration_value',
                            durationUnitPath: 'duration_unit',
                            outputFromPath: 'valid_from',
                            outputUntilPath: 'valid_until'
                        );
                        }),
                    DatePicker::make('valid_from')
                        ->label('Valid From')
                        ->disabled()
                        ->dehydrated(),

                    DatePicker::make('valid_until')
                        ->label('Valid Until')
                        ->disabled()
                        ->dehydrated(),

                    

                    Select::make('status')
                        ->options([
                            'active' => 'Active',
                            'inactive' => 'Inactive',
                            'suspended' => 'Suspended',
                        ])
                        ->default('active')
                        ->required(),
                       
                        ]),

            // --- Emergency Contact (Only for members) ---
            Section::make('Emergency Contact')
                ->schema([
                    TextInput::make('emergency_contact_name')
                        ->label('Name')
                        ->maxLength(255),
                        // ->default(null),
                        // ->required(fn (Get $get) => $get('role') === 'member'),

                    TextInput::make('emergency_contact_phone')
                        ->label('Phone')
                        ->tel(),
                        // ->required(fn (Get $get) => $get('role') === 'member'),
                ])
                ->columns(2)
                ->hidden(fn (Get $get) => $get('role') !== 'member'),

            // --- Notes ---
            Section::make('Notes')
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label('Additional Notes')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->hidden(fn (Get $get) => $get('role') !== 'member'),




                
                // TextInput::make('user_id')
                //     ->required()
                //     ->numeric(),
                
                // TextInput::make('package_id')
                //     ->numeric()
                //     ->default(null),
                // TextInput::make('duration_value')
                //     ->required()
                //     ->numeric()
                //     ->default(1),
                // DatePicker::make('starting_date'),
                // DatePicker::make('valid_from'),
                // DatePicker::make('valid_until'),
                // TextInput::make('status')
                //     ->required(),
                // TextInput::make('emergency_contact_name')
                //     ->maxLength(255)

                //     ->default(null),
                // TextInput::make('emergency_contact_phone')
                //     ->tel()
                //     ->maxLength(255)
                //     ->default(null),
                // TextInput::make('membership_id')
                //     ->required()
                //     ->maxLength(255),
                // Textarea::make('notes')
                //     ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('user.avatar')
                    ->label('User')
                    ->size(32)
                    ->circular()
                    // ->tooltip(fn (Model $record) =>{
                        
                    // }),
                    ->defaultImageUrl(url('/images/default-user.png'))
                    ->extraImgAttributes(['class' => 'bg-gray-200 hover:scale-110 overflow-visible'])
                    ,
                   
                      

                TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('package_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('duration_value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('starting_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('valid_from')
                    ->date()
                    ->sortable(),
                TextColumn::make('valid_until')
                    ->date()
                    ->sortable(),
                TextColumn::make('status'),
                TextColumn::make('emergency_contact_name')
                    ->searchable(),
                TextColumn::make('emergency_contact_phone')
                    ->searchable(),
                TextColumn::make('membership_id')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMembers::route('/'),
            'create' => Pages\CreateMember::route('/create'),
            'edit' => Pages\EditMember::route('/{record}/edit'),
        ];
    }
}
