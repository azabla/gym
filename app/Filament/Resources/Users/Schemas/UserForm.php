<?php
namespace App\Filament\Resources\Users\Schemas;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\Hash;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

use App\Models\Package;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Model;

use App\Filament\Traits\CalcPayDateRanges;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role as SpatieRole;
use Filament\Forms\Components\CheckboxList;


class UserForm
{
use calcPayDateRanges;
public static function configure(Schema $schema): Schema
{
    $memberRoleId = SpatieRole::where('name', 'member')->value('id');

    $isMember = fn(Get $get): bool => $get('roles') == $memberRoleId;


    return $schema
        ->components([

            Grid::make(['default' => 1, 'xl' => 12])
            ->schema([

                /*
                |--------------------------------------------------------------------------
                | LEFT SIDE: MAIN CONTENT COLUMN (8 of 12 slots)
                |--------------------------------------------------------------------------
                */
                Grid::make(1) // Syntactic container block for grouping layout fields
                    ->schema([
                        
                        // PERSONAL INFORMATION
                        Section::make('Personal Information')
                            ->icon('heroicon-o-user')
                            ->description('Basic member details.')
                            ->compact()
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-user'),

                                TextInput::make('phone')
                                    ->tel()
                                    ->prefixIcon('heroicon-o-phone'),

                                TextInput::make('address')
                                    ->prefixIcon('heroicon-o-map-pin'),

                                DatePicker::make('dob')
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-cake'),

                                Select::make('gender')
                                    ->options([
                                        'male' => 'Male',
                                        'female' => 'Female',
                                    ])
                                    ->native(false)
                                    ->default('male')
                                    ->required(),
                            ])
                            ->columns(2),
                        

                        Select::make('roles')
                        ->relationship('roles', 'name')
                        
                        ->preload()
                        ->searchable()
                        ->live()
                        ->required()
                        ->columnSpanFull(),


                        // ACCOUNT & SECURITY
                        Section::make('Account & Security')
                            ->icon('heroicon-o-lock-closed')
                            ->description('Authentication and permissions.')
                            ->collapsed()
                            
                            ->compact()
                            ->schema([
                                TextInput::make('username')
                                    
                                    ->unique(ignoreRecord: true)
                                    ->prefixIcon('heroicon-o-user-circle'),

                                TextInput::make('email')
                                    ->email()
                                
                                    ->unique(ignoreRecord: true)
                                    ->prefixIcon('heroicon-o-envelope'),

                                TextInput::make('password')
                                    ->password()
                                    ->dehydrated(fn($state) => filled($state))
                                    ->dehydrateStateUsing(
                                        fn($state) => filled($state) ? Hash::make($state) : null
                                    )
                                    
                                    ->helperText('Leave blank to keep current password.')
                                    ->prefixIcon('heroicon-o-lock-closed'),

                            ])
                            ->columns(2)
                            ->collapsible(),

                        
                       
                       
                        // ADDONS
                        Section::make('Membership Addons')
                            ->icon('heroicon-o-sparkles')
                            ->visible($isMember)
                            ->compact()
                            ->schema([
                                CheckboxList::make('addons')
                                    ->columns(2)
                                    ->gridDirection('column')
                                    ->columnSpanFull(),
                            ]),

                        // EMERGENCY CONTACT
                        Section::make('Emergency Contact')
                        
                            ->icon('heroicon-o-pencil-square')
                            ->description('Add detail Info.')
                            ->visible($isMember)
                            ->collapsed()
                            ->compact()
                            ->schema([
                                TextInput::make('member.emergency_contact_name'),

                                TextInput::make('member.emergency_contact_phone')
                                    ->tel(),

                                Textarea::make('member.notes')
                                    ->rows(4)
                                    ->columnSpanFull()
                            ])
                            ->columns(2)
                            ->collapsible(),

                        // NOTES
                        
                    ])
                    ->columnSpan(['default' => 12, 'xl' => 7]),


                /*
                |--------------------------------------------------------------------------
                | RIGHT SIDE: SIDEBAR COLUMN (4 of 12 slots)
                |--------------------------------------------------------------------------
                */
                Grid::make(1) // Syntactic container block for grouping layout fields
                    ->schema([
                        
                        // PROFILE PHOTO
                        Section::make('Profile Photo')
                            ->icon('heroicon-o-photo')
                            ->compact()
                            ->schema([
                                FileUpload::make('avatar')
                                    ->avatar()
                                    ->disk('public')
                                    ->directory('avatars')
                                    ->visibility('public')
                                    ->imageEditor()
                                    ->alignCenter(),
                            ]),

                        // MEMBER STATUS INFO
                        Section::make('Member Status')
                            ->icon('heroicon-o-shield-check')
                            ->visible($isMember)
                            ->compact()
                            ->schema([
                                Placeholder::make('status_info')
                                    ->label(false)
                                    ->content('Membership details and access status are managed here.'),
                            ]),

                            Section::make('Membership Details')
                            ->icon('heroicon-o-identification')
                            ->description('Membership configuration and validity.')
                            ->visible($isMember)
                            ->compact()
                            ->schema([
                                Select::make('member.package_id')
                                    ->label('Package')
                                    ->options(Package::pluck('name', 'id'))
                                    ->searchable()
                                    ->live()
                                    ->required()
                                    ->afterStateUpdated(function (Set $set, Get $get){
                                        $packageId = $get('member.package_id');
                                        if(!$packageId){
                                            return;
                                        }

                                        $package = Package::find($packageId);
                                        if($package){
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

                                TextInput::make('member.duration_value')
                                ->label('Durations')
                                ->numeric()
                                ->step(1) // forces integer input in browser
                                ->minValue(1)
                                ->default(1)
                                ->required()
                                ->live()
                                ->prefixIcon('heroicon-o-clock')
                                ->suffix(fn(Get $get) => match ($get('memeber.duration_unit')) {
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
                                        startingDatePath: 'member.starting_date',
                                        durationValuePath: 'member.duration_value',
                                        durationUnitPath: 'duration_unit',
                                        outputFromPath: 'member.valid_from',
                                        outputUntilPath: 'member.valid_until'
                                    );
                                }),

                                Hidden::make('duration_unit')
                                ->dehydrated(false), // Will hold 'day', 'week', 'month', 'year'
                                
                                DatePicker::make('member.starting_date')
                                    ->ethiopic()
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
                                            startingDatePath: 'member.starting_date',
                                            durationValuePath: 'member.duration_value',
                                            durationUnitPath: 'duration_unit',
                                            outputFromPath: 'member.valid_from',
                                            outputUntilPath: 'member.valid_until'
                                        );
                                    }),

                                DatePicker::make('member.valid_from')
                                    ->ethiopic()
                                    ->label('Valid From')
                                    ->disabled()
                                    ->prefixIcon('heroicon-o-calendar-days')
                                    ->dehydrated(),

                                DatePicker::make('member.valid_until')
                                    ->ethiopic()
                                    ->label('Valid Until')
                                    ->disabled()
                                    ->prefixIcon('heroicon-o-x-mark')
                                    ->dehydrated()
                                    ->extraAttributes(['class' => 'font-bold text-primary-600']),


                                TextInput::make('member.membership_id')
                                    ->label('Membership ID')
                                    ->unique(
                                        table: 'members',           // ✅ Check in `members` table
                                        column: 'membership_id',    // ✅ The column to check
                                        ignoreRecord: true,         // ✅ Ignore current record when editing
                                    )
                                    ->default(fn() => 'MEM-' . now()->format('Y') . '-' . random_int(1000, 9999))
                                    ->prefixIcon('heroicon-o-identification')
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
                            ->columns(2),
                    ])
                    ->columnSpan(['default' => 12, 'xl' => 5]),

            ]),
        ])
        ->columns(1);
}


}