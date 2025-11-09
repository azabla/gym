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


class MemberResource extends Resource
{
    use PaymentCalculationsTrait; 
    protected static ?string $model = Member::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'User Management';
    protected static ?string $navigationLabel = 'All Members';
    use CalcPayDateRanges;
    public static function form(Form $form): Form
    {

        return $form
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
                    ->default(state: null),
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
                         ->ethiopic()
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
                    ->ethiopic()
                        ->label('Valid From')
                        ->disabled()
                        ->dehydrated(),

                    DatePicker::make('valid_until')
                    ->ethiopic()
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
                ImageColumn::make('user.avatar')
                    ->label('Profile')
                    ->size(32)
                    ->circular()
                    // ->tooltip(fn (Model $record) =>{
                        
                    // }),
                    ->defaultImageUrl(url('/images/default-user.png'))
                    ->extraImgAttributes(['class' => 'bg-gray-200 hover:scale-110 overflow-visible'])
                    ,
                   


                TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('package.name')
                    ->label('Package')
                    ->sortable(),
                TextColumn::make('duration_value')
                    ->label('Duration')
                    ->numeric()
                    ->formatStateUsing(fn($state, $record) => 
                    $state . ' ' . ($record->package?->duration_unit ?: 'unit')
                )
                    ->sortable(),
                // TextColumn::make('duration_value')
                //     ->numeric()
                //     ->sortable(),
                TextColumn::make('starting_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('valid_from')
                    ->date()
                    ->sortable(),
                TextColumn::make('valid_until')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    
                    'active' => 'success',
                    'inactive' => 'danger',
                    'suspended' => 'warning',
                    default => 'primary'
                }),
                            // TextColumn::make('emergency_contact_name')
                //     ->searchable(),
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
                        true: fn (Builder $query) => $query->where('valid_until', '<', now()),
                        false: fn (Builder $query) => $query->where('valid_until', '>=', now())->orWhereNull('valid_until'),
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
                                fn (Builder $query, $date): Builder => $query->whereDate('valid_until', '>=', $date),
                            )
                            ->when(
                                $data['valid_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('valid_until', '<=', $date),
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
                                fn (Builder $query, $date): Builder => $query->whereDate('starting_date', '>=', $date),
                            )
                            ->when(
                                $data['starting_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('starting_date', '<=', $date),
                            );
                    }),



            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Add the automatic Pay action
                Tables\Actions\Action::make('auto_pay')
                    ->label('Pay')
                    ->icon('heroicon-o-credit-card')
                    ->color('success')
                    ->action(function (Member $record) {
                        self::processAutoPayment($record);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Auto Payment')
                    ->modalDescription(
                        fn(Member $record) =>
                        "Create automatic payment for {$record->user->name} using their profile settings?"
                    )
                    ->modalSubmitActionLabel('Confirm Payment')
                    ->visible(
                        fn(Member $record): bool =>
                        $record->package_id && $record->duration_value > 0
                    ),
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
            'duration_value' =>  $member->duration_value ?: 1,
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
