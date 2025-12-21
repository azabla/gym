<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MemberResource\Pages;
use App\Filament\Resources\MemberResource\RelationManagers;
use App\Models\Member;
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
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use App\Filament\Traits\PaymentCalculationsTrait;

use App\Filament\Traits;
use App\Filament\Traits\CalcPayDateRanges;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;


use App\Models\Package;


class MemberResource extends Resource
{
    use PaymentCalculationsTrait;
    protected static ?string $model = Member::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'User Management';
    protected static ?string $navigationLabel = 'All Members';
    use CalcPayDateRanges;
    public static function form(Form $form): Form
    {

        return $form
            ->schema([
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
                                    ->collapsed(false) // Collapsed by default to save space
                                    ->schema([
                                        Grid::make(3)->schema([
                                            Hidden::make('user.id'),
                                            TextInput::make('user.username')
                                                ->required()
                                                ->rule(function (Get $get) {
                                                    $userId = $get('user.id');
                                                    return Rule::unique('users', 'username')->ignore($userId);
                                                })
                                                ->maxLength(255)
                                                ->prefixIcon('heroicon-o-at-symbol'),
                                            TextInput::make('user.email')
                                                ->email()
                                                ->maxLength(255)
                                                ->prefixIcon('heroicon-o-envelope')
                                                ->default(null),
                                            TextInput::make('user.password')
                                                ->password()
                                                ->maxLength(255)
                                                ->prefixIcon('heroicon-o-lock-closed')
                                                ->default(null),
                                        ]),
                                    ]),
                                // --- Emergency Contact (Only for members) ---
                                Section::make('Emergency Contact 🆘')
                                    ->description('Contact details in case of emergencies.')
                                    ->collapsed(false)
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
                                    ->collapsed(false)
                                    ->schema([
                                        Forms\Components\Textarea::make('notes')
                                            ->label('Additional Notes')
                                            ->rows(3),
                                    ]),
                                // ->hidden(fn(Get $get) => $get('role') !== 'member'),
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
                                    ->collapsed(false)
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
                                            ->nullable()
                                            ->required()
                                            ->prefixIcon('heroicon-o-gift')
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
                                    ]),

                                Section::make('Validity Period 📅')
                                    ->collapsed(false)
                                    ->schema([

                                        DatePicker::make('starting_date')
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
                                                    startingDatePath: 'starting_date',
                                                    durationValuePath: 'duration_value',
                                                    durationUnitPath: 'duration_unit',
                                                    outputFromPath: 'valid_from',
                                                    outputUntilPath: 'valid_until'
                                                );
                                            }),
                                        DatePicker::make('valid_from')
                                            ->ethiopic()
                                            ->label('Valid From')
                                            ->disabled()
                                            ->prefixIcon('heroicon-o-calendar-days')
                                            ->dehydrated(),

                                        DatePicker::make('valid_until')
                                            ->ethiopic()
                                            ->label('Valid Until')
                                            ->disabled()
                                            ->prefixIcon('heroicon-o-x-mark')
                                            ->dehydrated()
                                            ->extraAttributes(['class' => 'font-bold text-primary-600']),



                                        Select::make('status')
                                            ->options([
                                                'active' => 'Active',
                                                'inactive' => 'Inactive',
                                                'suspended' => 'Suspended',
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

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            // ->headerActions([
            //         CreateAction::make()
            //             ->form([
            //                 TextInput::make('title')
            //                     ->required()
            //                     ->maxLength(255),
            //                 // ...
            //             ]),
            //     ])
            ->columns([
                TextColumn::make('Roll No.')->label('Roll No.')->rowIndex(),
                ImageColumn::make('user.avatar')
                    ->label('Profile')
                    ->circular()
                    ->extraImgAttributes([
                        'class' => 'transition-transform duration-300 hover:scale-[4] hover:z-50',
                    ])
                    ->defaultImageUrl(url('/images/default-user.png')),
                TextColumn::make('user.name')
                    ->label('Member Details')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => $record->user->email) // Puts email under the name
                    ->copyable() // Allows clicking to copy email
                    ->tooltip('Click to copy name'),
                TextColumn::make('user.phone')
                    ->label('Phone')
                    ->icon('heroicon-o-phone')
                    ->badge()
                    ->copyable()
                    ->copyMessage('Phone number copied')
                    ->copyMessageDuration(1500)
                    ->tooltip('Click to copy phone number'),
                TextColumn::make('package.name')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-gift')
                    ->tooltip(fn($record) => "Price: " . number_format($record->package->price, 2) . " Birr for a {$record->package->duration_unit}"),
                TextColumn::make('duration_value')
                    ->label('Duration')
                    ->numeric()
                    ->formatStateUsing(
                        fn($state, $record) =>
                        $state . ' ' . ($record->package?->duration_unit ?: 'unit')
                    )
                    ->tooltip('Default unit if no package assigned')
                    ->sortable(),
                TextColumn::make('starting_date')
                    ->label('Member Since')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn($record): string => "Exact registration: " . $record->starting_date->diffForHumans()),
                TextColumn::make('valid_from')
                    ->date()
                    ->tooltip('Membership Valid From Date')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('valid_until')
                    ->date()
                    ->label('Expiary Date')
                    ->tooltip('Membership Expiry Date')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {

                        'active' => 'success',
                        'inactive' => 'danger',
                        'suspended' => 'warning',
                        'expired' => 'gray',
                        default => 'primary'
                    }),
                // TextColumn::make('emergency_contact_name')
                //     ->searchable(),
                TextColumn::make('emergency_contact_phone')
                    ->label('Emergency Phone')
                    ->icon('heroicon-o-phone')
                    ->badge()
                    ->copyable()
                    ->copyMessage('Phone number copied')
                    ->copyMessageDuration(1500)
                    ->tooltip('click to copy Emergency Contact'),
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
            ->deferLoading() // Adds a nice loading shimmer
            ->striped()
            ->filters([
                SelectFilter::make('package')
                    ->label('Package')
                    ->multiple()
                    ->preload()
                    ->placeholder('All Packages')
                    ->relationship('package', 'name')->label('Package'),
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'suspended' => 'Suspended',
                    ])->label('Status'),
                TernaryFilter::make('is_expired')
                    ->label('Expired')
                    ->placeholder('All')
                    ->indicator('Expired Status')
                    ->trueLabel('Yes')
                    ->falseLabel('No')
                    ->queries(
                        true: fn(Builder $query) => $query->where('valid_until', '<', now()),
                        false: fn(Builder $query) => $query->where('valid_until', '>=', now())->orWhereNull('valid_until'),
                    ),
                Filter::make('valid_until')
                    ->label('Expiry Date Range')
                    ->form([
                        DatePicker::make('valid_from')
                            ->label('Expire From')
                            ->native(false)
                            ->placeholder('Start Date'),
                        DatePicker::make('valid_until')
                            ->label('Expire Until')
                            ->native(false)
                            ->placeholder('End Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['valid_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('valid_until', '>=', $date),
                            )
                            ->when(
                                $data['valid_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('valid_until', '<=', $date),
                            );
                    }),
                Filter::make('starting_date')
                    ->label('Starting Date Range')
                    ->form([
                        DatePicker::make('starting_from')
                            ->label('Starting From')
                            ->native(false)
                            ->placeholder('Start Date'),
                        DatePicker::make('starting_until')
                            ->label('Starting Until')
                            ->native(false)
                            ->placeholder('End Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['starting_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('starting_date', '>=', $date),
                            )
                            ->when(
                                $data['starting_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('starting_date', '<=', $date),
                            );
                    }),



            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth('4xl') // Makes the popup size
                    ->tooltip('Quick Edit Member')
                    ->slideOver() 
                    ->modalHeading('Update Member Profile')
                    ->modalDescription('Changes will be applied immediately to the member record.')
                    ->modalSubmitActionLabel('Save Changes')
                    ->icon('heroicon-m-pencil-square')
                    ->color('warning'),
                // Add the automatic Pay action
                Tables\Actions\Action::make('pay')
                    ->label('Pay')
                    ->icon('heroicon-o-credit-card')
                    ->color('primary')
                    ->slideOver() // Makes it a sidebar instead of a center modal
                    ->modalHeading('Process Payment')
                    ->modalDescription('Review and adjust payment details before processing.')
                    ->modalSubmitActionLabel('Confirm Payment')
                    ->form([
                        Section::make('Subscription Details 📝')
                            ->description('Select their desired package.')
                            ->schema([
                                Forms\Components\Select::make('package_id')
                                    ->label('Package')
                                    ->options(Package::pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        // Recalculate amount and unit when package changes
                                        $packageId = $get('package_id');
                                        if ($packageId) {
                                            $package = Package::find($packageId);
                                            $duration = (int) $get('duration_value');

                                            // Update price
                                            $set('amount', $package->price * $duration);

                                            // Recalculate End Date
                                            $validFrom = $get('valid_from');
                                            if ($validFrom) {
                                                $unit = $package->duration_unit ?? 'month';
                                                $until = \Carbon\Carbon::parse($validFrom);
                                                match ($unit) {
                                                    'day' => $until->addDays($duration),
                                                    'week' => $until->addWeeks($duration),
                                                    'month' => $until->addMonths($duration),
                                                    'year' => $until->addYears($duration),
                                                    default => $until->addMonths($duration),
                                                };
                                                $set('valid_until', $until->format('Y-m-d'));
                                            }
                                        }
                                    }),

                                Forms\Components\TextInput::make('duration_value')
                                    ->label('Duration')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->live()
                                    ->suffix(
                                        fn(Forms\Get $get) =>
                                        $get('package_id') ?
                                        Package::find($get('package_id'))->duration_unit :
                                        'Unit'
                                    )
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        // Recalculate amount and dates when duration changes
                                        $packageId = $get('package_id');
                                        if ($packageId) {
                                            $package = Package::find($packageId);
                                            $duration = (int) $get('duration_value');

                                            // Update Price
                                            $set('amount', $package->price * $duration);

                                            // Update Date
                                            $validFrom = $get('valid_from');
                                            if ($validFrom) {
                                                $unit = $package->duration_unit ?? 'month';
                                                $until = \Carbon\Carbon::parse($validFrom);
                                                match ($unit) {
                                                    'day' => $until->addDays($duration),
                                                    'week' => $until->addWeeks($duration),
                                                    'month' => $until->addMonths($duration),
                                                    'year' => $until->addYears($duration),
                                                    default => $until->addMonths($duration),
                                                };
                                                $set('valid_until', $until->format('Y-m-d'));
                                            }
                                        }
                                    }),
                            ])->columns(2),

                        Section::make('Payment & Dates 💰')
                            ->description('Enter payment details and validity period.')
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label('Total Amount')
                                    ->prefix('ETB')
                                    ->readOnly()
                                    ->numeric()
                                    ->required(),

                                Forms\Components\Select::make('payment_method')
                                    ->options([
                                        'cash' => 'Cash',
                                        'online' => 'Online',
                                    ])
                                    ->default('cash')
                                    ->required(),

                                Forms\Components\DatePicker::make('valid_from')
                                    ->label('Valid From')
                                    ->required()
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        // Recalculate valid_until if the start date is manually changed
                                        $packageId = $get('package_id');
                                        $duration = (int) $get('duration_value');
                                        $validFrom = $get('valid_from');

                                        if ($packageId && $validFrom) {
                                            $package = Package::find($packageId);
                                            $unit = $package->duration_unit ?? 'month';
                                            $until = \Carbon\Carbon::parse($validFrom);
                                            match ($unit) {
                                                'day' => $until->addDays($duration),
                                                'week' => $until->addWeeks($duration),
                                                'month' => $until->addMonths($duration),
                                                'year' => $until->addYears($duration),
                                                default => $until->addMonths($duration),
                                            };
                                            $set('valid_until', $until->format('Y-m-d'));
                                        }
                                    }),

                                Forms\Components\DatePicker::make('valid_until')
                                    ->label('Valid Until')
                                    ->required()
                                    ->native(false)
                                    ->readOnly(),

                                Forms\Components\Textarea::make('notes')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])->columns(4),
                    ])
                    // Pre-fill the form with Member data
                    ->mountUsing(function (Forms\ComponentContainer $form, Member $record) {
                        // Calculate logic for start date (Logic taken from PaymentResource)
                        $validFrom = now()->format('Y-m-d');
                        if ($record->valid_until) {
                            $expiryDate = \Carbon\Carbon::parse($record->valid_until)->endOfDay();
                            $today = now()->startOfDay();
                            if ($expiryDate->greaterThan($today)) {
                                $validFrom = $expiryDate->addDay()->format('Y-m-d');
                            }
                        }

                        // Calculate End Date
                        $package = $record->package;
                        $duration = $record->duration_value ?: 1;
                        $validUntil = \Carbon\Carbon::parse($validFrom);

                        if ($package) {
                            $unit = $package->duration_unit ?? 'month';
                            match ($unit) {
                                'day' => $validUntil->addDays($duration),
                                'week' => $validUntil->addWeeks($duration),
                                'month' => $validUntil->addMonths($duration),
                                'year' => $validUntil->addYears($duration),
                                default => $validUntil->addMonths($duration),
                            };
                        } else {
                            $validUntil->addMonth();
                        }

                        $form->fill([
                            'package_id' => $record->package_id,
                            'duration_value' => $duration,
                            'amount' => $package ? ($package->price * $duration) : 0,
                            'payment_method' => 'cash',
                            'valid_from' => $validFrom,
                            'valid_until' => $validUntil->format('Y-m-d'),
                        ]);
                    })
                    ->action(function (Member $record, array $data) {
                        // Process the payment with the DATA from the FORM, not just the record defaults
                        $payment = Payment::create([
                            'member_id' => $record->id,
                            'package_id' => $data['package_id'],
                            'amount' => $data['amount'],
                            'payment_method' => $data['payment_method'],
                            'payment_date' => now(),
                            'valid_from' => $data['valid_from'],
                            'valid_until' => $data['valid_until'],
                            'transaction_id' => 'TXN-' . strtoupper(Str::random(8)),
                            'status' => 'completed',
                            'duration_value' => $data['duration_value'],
                            'notes' => $data['notes'] ?? 'Manual payment via Member Table',
                        ]);

                        // Payment model observer (booted method) handles updating the Member
                        // automatically when a 'completed' payment is created. 
                        // But if want it to be explicit uncomment this
            
                        $record->update([
                            'valid_until' => $data['valid_until'],
                            'status' => 'active',
                        ]);


                        Notification::make()
                            ->title('Payment Processed')
                            ->body("Payment of {$data['amount']} Birr recorded. Expiry updated to {$data['valid_until']}")
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulk_pay')
                        ->label('Bulk Pay Selected Members')
                        ->icon('heroicon-o-credit-card')
                        ->color('success')
                        ->action(function (Collection $records) {
                            self::processBulkAutoPayments($records);
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Confirm Bulk Payments')
                        ->modalDescription(function (Collection $records) {
                            $count = $records->count();
                            $validCount = $records->filter(function ($member) {
                                return $member->package_id && $member->duration_value > 0;
                            })->count();

                            $invalidCount = $count - $validCount;

                            $description = "Process automatic payments for {$count} members.\n";

                            if ($validCount > 0) {
                                $description .= "✅ {$validCount} members have valid packages and duration values.\n";
                            }

                            if ($invalidCount > 0) {
                                $description .= "⚠️ {$invalidCount} members are missing package or duration settings and will be skipped.\n";
                            }

                            return $description;
                        })
                        ->modalSubmitActionLabel('Process Bulk Payments')
                        ->deselectRecordsAfterCompletion(),
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

    protected static function processAutoPayment(Member $member): void
    {
        // Get the package
        $package = $member->package;
        if (!$package) {
            throw new \Exception("Member doesn't have a valid package assigned");
        }

        $validFrom = self::determineValidFromDateForMember($member);
        $validUntil = self::calculateValidUntilForMember($member, $validFrom);
        $amount = self::calculatePaymentAmountForMember($member);

        // Create payment
        $payment = Payment::create([
            'member_id' => $member->id,
            'package_id' => $package->id,
            'amount' => $amount,
            'payment_method' => 'cash',
            'payment_date' => now(),
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'transaction_id' => 'AUTO-' . strtoupper(Str::random(8)),
            'status' => 'completed',
            'duration_value' => $member->duration_value ?: 1,
            'notes' => 'Auto-generated payment from member profile',
        ]);

        // Update member's valid_until to the new expiry
        $member->update([
            'valid_until' => $validUntil,
            'status' => 'active',
        ]);

        // Notify user
        Notification::make()
            ->title('Payment Processed Successfully')
            ->body("payment of {$amount} Birr is Done for {$member->user->name}. New expiry: {$validUntil->format('Y-m-d')}")
            ->success()
            ->send();
    }


    protected static function processBulkAutoPayments(Collection $members): void
    {
        $successCount = 0;
        $errorCount = 0;
        $errorMessages = [];
        $skippedMembers = [];

        foreach ($members as $member) {
            try {
                // Skip members without required data
                if (!$member->package_id || !$member->duration_value || $member->duration_value <= 0) {
                    $skippedMembers[] = $member->user->name;
                    continue;
                }

                // Get the package
                $package = $member->package;
                if (!$package) {
                    $errorMessages[] = "{$member->user->name}: No valid package assigned";
                    $errorCount++;
                    continue;
                }

                // Use trait methods for calculations
                $validFrom = self::determineValidFromDateForMember($member);
                $validUntil = self::calculateValidUntilForMember($member, $validFrom);
                $amount = self::calculatePaymentAmountForMember($member);

                // Create payment
                Payment::create([
                    'member_id' => $member->id,
                    'package_id' => $package->id,
                    'amount' => $amount,
                    'payment_method' => 'cash',
                    'payment_date' => now(),
                    'valid_from' => $validFrom,
                    'valid_until' => $validUntil,
                    'transaction_id' => 'AUTO-' . strtoupper(Str::random(8)),
                    'status' => 'completed',
                    'duration_value' => $member->duration_value ?: 1,
                    'notes' => 'Auto-generated bulk payment from member profile',
                ]);

                // Update member's valid_until to the new expiry
                $member->update([
                    'valid_until' => $validUntil,
                    'status' => 'active',
                ]);

                $successCount++;

            } catch (\Exception $e) {
                $errorMessages[] = "{$member->user->name}: {$e->getMessage()}";
                $errorCount++;
            }
        }

        // Prepare notification message
        $message = "Bulk payment processing completed:\n";
        $message .= "✅ Successful: {$successCount}\n";

        if ($skippedMembers) {
            $message .= "⏭️ Skipped (missing data): " . count($skippedMembers) . "\n";
        }

        if ($errorCount > 0) {
            $message .= "❌ Failed: {$errorCount}\n";
            $message .= "Error details:\n" . implode("\n", $errorMessages);
        }

        // Send notification
        if ($successCount > 0) {
            Notification::make()
                ->title('Bulk Payments Completed Successfully')
                ->body($message)
                ->success()
                ->send();
        } elseif ($errorCount > 0) {
            Notification::make()
                ->title('Bulk Payments Failed')
                ->body($message)
                ->danger()
                ->send();
        } else {
            Notification::make()
                ->title('No Valid Members for Bulk Payment')
                ->body('All selected members were skipped due to missing package or duration settings.')
                ->warning()
                ->send();
        }
    }

}
