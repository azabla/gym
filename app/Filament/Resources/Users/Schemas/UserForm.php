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

    $isMember = fn(Get $get): bool =>
        in_array($memberRoleId, $get('roles') ?? []);

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
                                    ->required(),
                            ])
                            ->columns(2),

                        // ACCOUNT & SECURITY
                        Section::make('Account & Security')
                            ->icon('heroicon-o-lock-closed')
                            ->description('Authentication and permissions.')
                            ->compact()
                            ->schema([
                                TextInput::make('username')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->prefixIcon('heroicon-o-user-circle'),

                                TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->prefixIcon('heroicon-o-envelope'),

                                TextInput::make('password')
                                    ->password()
                                    ->dehydrated(fn($state) => filled($state))
                                    ->dehydrateStateUsing(
                                        fn($state) => filled($state) ? Hash::make($state) : null
                                    )
                                    ->required(fn(string $context) => $context === 'create')
                                    ->helperText('Leave blank to keep current password.')
                                    ->prefixIcon('heroicon-o-lock-closed'),

                                Select::make('roles')
                                    ->relationship('roles', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->live()
                                    ->required()
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),

                       
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
                            ->icon('heroicon-o-phone')
                            ->visible($isMember)
                            ->compact()
                            ->schema([
                                TextInput::make('member.emergency_contact_name'),

                                TextInput::make('member.emergency_contact_phone')
                                    ->tel(),
                            ])
                            ->columns(2),

                        // NOTES
                        Section::make('Notes')
                            ->icon('heroicon-o-document-text')
                            ->visible($isMember)
                            ->compact()
                            ->schema([
                                Textarea::make('member.notes')
                                    ->rows(4)
                                    ->columnSpanFull(),
                            ]),
                    ])
                    ->columnSpan(['default' => 12, 'xl' => 8]),


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
                                    ->required(),

                                TextInput::make('member.duration_value')
                                    ->numeric()
                                    ->default(1)
                                    ->required(),

                                DatePicker::make('member.starting_date')
                                    ->required()
                                    ->default(now()),

                                DatePicker::make('member.valid_from')
                                    ->disabled(),

                                DatePicker::make('member.valid_until')
                                    ->disabled(),

                                TextInput::make('member.membership_id')
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
                    ->columnSpan(['default' => 12, 'xl' => 4]),

            ]),
        ])
        ->columns(1);
}


}