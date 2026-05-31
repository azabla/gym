<?php
namespace App\Filament\Resources\Members\Schemas;

use App\Filament\Traits\CalcPayDateRanges;
use App\Models\Addon;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use App\Models\Package;
use Carbon\Carbon;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Hash;

class MemberForm
{
    use CalcPayDateRanges;
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)
                    ->schema([
                        // Personal & Contact Info
                        Group::make()
                            ->columnSpan(['lg' => 2])
                            ->schema([
                                Section::make('Personal Information ')
                                    ->description('Basic personal details of the member.')
                                    ->collapsed(false)
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('user.name')
                                                ->label('Full Name')
                                                ->placeholder('Abel Asrat')
                                                ->minLength(2)
                                                ->maxLength(30)
                                                ->prefixIcon('heroicon-o-user')
                                                ->required(),
                                            TextInput::make('user.phone')
                                                ->tel()
                                                ->placeholder('09********')
                                                ->maxLength(255)
                                                ->prefixIcon('heroicon-o-phone')
                                                ->default(null),
                                            TextInput::make('user.address')
                                                ->placeholder('Kality O9')
                                                ->maxLength(255)
                                                ->prefixIcon('heroicon-o-map-pin')
                                                ->default(state: null),
                                            Select::make('user.gender')
                                                ->label('Gender')
                                                ->options([
                                                    'male' => 'Male',
                                                    'female' => 'Female',
                                                ])
                                                ->default('male')
                                                ->native(false)
                                                ->prefixIcon('heroicon-o-users')
                                                ->required(),
                                            DatePicker::make('user.dob')
                                                ->native(false)
                                                ->ethiopic()
                                                ->label('Date of Birth')
                                                ->placeholder('Select Date of Birth')
                                                ->maxDate(now()->subYears(10))
                                                ->prefixIcon('heroicon-o-cake')
                                                ->default(null),
                                        ]),
                                    ]),
                                Section::make('Account Security 🔐')
                                    ->description('Manage login credentials.')
                                    ->collapsed() // Collapsed by default to save space
                                    ->schema([
                                        Grid::make(3)->schema([
                                            Hidden::make('user.id'),
                                            TextInput::make('user.username')
                                                
                                                ->unique(
                                                    table: 'users',
                                                    column: 'username',
                                                    ignorable: fn(?Model $record) => $record?->user, // Important to ignore current record on edit
                                                )
                                                ->rule(function (Get $get) {
                                                    $userId = $get('user.id');
                                                    return Rule::unique('users', 'username')->ignore($userId);
                                                })
                                                ->maxLength(255)
                                                ->prefixIcon('heroicon-o-at-symbol'),
                                            TextInput::make('user.email')
                                                ->email()
                                                ->maxLength(255)
                                                ->unique(
                                                    table: 'users',
                                                    column: 'email',
                                                    ignorable: fn(?Model $record) => $record?->user,
                                                )
                                                ->prefixIcon('heroicon-o-envelope')
                                                ->default(null),
                                            TextInput::make('user.password')//avoid requiring password on edit and only hash/update if new value provided
                                                ->password()
                                                ->maxLength(255)
                                                ->prefixIcon('heroicon-o-lock-closed')
                                                ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                                                ->dehydrated(fn($state) => filled($state))
                                                
                                                ->helperText('Leave blank to keep current password.'),
                                        ]),
                                    ]),
                                // --- Emergency Contact (Only for members) ---
                                Section::make('Emergency Contact 🆘')
                                    ->description('Contact details in case of emergencies.')
                                    ->collapsed()
                                    ->schema([
                                        Grid::make(2)->schema([
                                            TextInput::make('emergency_contact_name')
                                                ->label('Name')
                                                ->prefixIcon('heroicon-o-user')
                                                ->maxLength(255),
                                            // ->default(null),
                                            // ->required(fn (Get $get) => $get('role') === 'member'),

                                            TextInput::make('emergency_contact_phone')
                                                ->label('Phone')
                                                ->prefixIcon('heroicon-o-phone')
                                                ->tel(),
                                            // ->required(fn (Get $get) => $get('role') === 'member'),
                                        ])
                                            ->columns(2),
                                        // ->hidden(fn(Get $get) => $get('role') !== 'member'),
                                    ]),

                                // --- Notes ---
                                Section::make('Notes 📝')
                                    ->description('Additional notes about the member')
                                    ->collapsed()
                                    ->schema([
                                        Textarea::make('notes')
                                            ->label('Additional Notes')
                                            ->rows(3),
                                    ]),
                                // ->hidden(fn(Get $get) => $get('role') !== 'member'),

                                Section::make('Add-ons & Extras')
                                    ->description('Select optional add-ons to customize the membership')
                                    ->icon('heroicon-o-shopping-bag')
                                    ->schema([
                                        CheckboxList::make('addons')
                                            ->label('Member Addons / Extras')
                                            // ->relationship('addons', 'name')
                                            ->options(Addon::pluck('name', 'id'))
                                            ->columnSpanFull()
                                            ->live()
                                            ->afterStateUpdated(function ($state,  Set $set){
                                                $total = Addon::whereIn('id', $state)->sum('price');
                                                $set('addon_total', $total);
                                            })->columns(2),
                                        Placeholder::make('addon_total_display')
                                        ->label('Addon Total')
                                        ->content(fn (Get $get) =>
                                            number_format($get('addon_total') ?? 0, 2) . ' ETB'
                                        )
                                        ]),

                                        Section::make('Membership Summary')
                                        ->schema([
                                            Placeholder::make('package_summary')
                                                ->label('Package')
                                                ->content(function (Get $get) {
                                                    $package = Package::find($get('package_id'));
                                                    if (!$package) return '—';
                                                    return $package->name . ' — ' . number_format($package->price, 2) . ' ETB';
                                                })
                                                ->extraAttributes(['class' => 'bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3']),
                                    
                                            Placeholder::make('addons_summary')
                                                ->label('Add-ons')
                                                ->content(function (Get $get) {
                                                    $addonIds = $get('addons') ?? [];
                                                    if (empty($addonIds)) return '—';
                                                    
                                                    $addons = Addon::whereIn('id', $addonIds)->get();
                                                    return $addons->map(fn($a) => $a->name . ' (+' . number_format($a->price, 2) . ')')->implode(', ');
                                                })
                                                ->extraAttributes(['class' => 'bg-green-50 dark:bg-green-900/20 rounded-lg p-3']),
                                    
                                            Placeholder::make('total')
                                                ->label('Total')
                                                ->content(function (Get $get) {
                                                    $packagePrice = Package::find($get('package_id'))?->price ?? 0;
                                                    $addonPrice = Addon::whereIn('id', $get('addons') ?? [])->sum('price');
                                                    return number_format($packagePrice + $addonPrice, 2) . ' ETB';
                                                })
                                                ->extraAttributes(['class' => 'bg-primary-50 dark:bg-primary-900/20 rounded-lg p-3 font-bold text-primary-700 dark:text-primary-400']),
                                        ])
                                        ->extraAttributes(['class' => 'space-y-3'])
                                        ->live()
                                        ->columns(3),
                            ]),
                        Group::make()
                            ->columnSpan(['lg' => 1])
                            ->schema([
                                Section::make('Profile Image 📸')
                                    ->collapsed(false)
                                    ->schema([
                                        FileUpload::make('user.avatar')
                                            ->avatar()
                                            ->label('Avatar')
                                            ->hiddenLabel()
                                            ->directory('avatars')
                                            ->disk('public')
                                            ->visibility('public')
                                            ->alignCenter(),

                                    ]),

                                // the member specific data

                                Section::make('Membership Details 💳')
                                    // ->collapsed()
                                    ->schema([
                                        TextInput::make('membership_id')
                                            ->label('Membership ID')
                                            ->unique(
                                                table: 'members',           // ✅ Check in `members` table
                                                column: 'membership_id',    // ✅ The column to check
                                                ignoreRecord: true,         // ✅ Ignore current record when editing
                                            )
                                            ->default(fn() => 'MEM-' . now()->format('Y') . '-' . random_int(1000, 9999))
                                            ->prefixIcon('heroicon-o-identification')
                                            ->required(),

                                        Select::make('package_id')
                                            ->label('Package')
                                            ->options(Package::pluck('name', 'id'))
                                            ->searchable()
                                            ->native(false)
                                            
                                            ->required()
                                            ->prefixIcon('heroicon-o-gift')
                                            ->live()
                                            ->default(fn() => Package::where('name', 'monthly')->value('id'))
                                            
                                            ->afterStateUpdated(function (Set $set, Get $get) {

                                                $packageId = $get('package_id'); // get the selected package ID
                                                if (!$packageId) {
                                                    return;
                                                }

                                                $package = Package::find($packageId); // find the package by ID
                                                if ($package) { // if the package exists, set the duration unit
                                                    $set('duration_unit', $package->duration_unit ?? 'month');
                                                    $set('duration_value', $package->duration_value ?? 1);

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
                                            ->prefixIcon('heroicon-o-clock')
                                            ->suffix(fn(Get $get) => match ($get('duration_unit')) {
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

                                        

                                        // CheckboxList::make('addons')
                                        // ->options(
                                        //     \App\Models\Addon::pluck('name', 'id')->toArray()
                                        // )
                                        // ->live()
                                        // ->afterStateUpdated(function ($state) {
                                        //     dd($state);
                                        // }),
                                    ]),

                                Section::make('Validity Period 📅')
                                    ->collapsed(false)
                                    ->schema([

                                        DatePicker::make('starting_date')
                                           
                                            ->label('Starting Date')
                                            ->required()
                                            ->default(now())
                                            ->live()
                                            ->prefixIcon('heroicon-o-calendar')
                                            ->native(false)
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
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-calendar-days')
                                            ->dehydrated()
                                            ->default(fn () => Carbon::parse(now())),

                                        DatePicker::make('valid_until')
                                            
                                            ->label('Valid Until')
                                            ->disabled()
                                            ->native(false)
                                            ->prefixIcon('heroicon-o-x-mark')
                                            ->dehydrated()
                                            ->extraAttributes(['class' => 'font-bold text-primary-600'])
                                            ->default(fn () => Carbon::parse(now())->addMonth()),



                                        Select::make('status')
                                            ->options([
                                                'active' => 'Active',
                                                'inactive' => 'Inactive',
                                                'expired' => 'Expired',
                                            ])
                                            ->native(false)
                                            ->prefixIcon(function (string $state): string {
                                                if ($state === 'active') {
                                                    return 'heroicon-o-check-circle';
                                                }
                                                return 'heroicon-o-x-circle';
                                            })
                                            ->default('active')
                                            ->live()
                                            ->required(),

                                    ]),





                            ]),
                    ]),


                    

            ])->columns(1);
    }


    
}