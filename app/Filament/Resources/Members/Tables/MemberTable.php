<?php

namespace App\Filament\Resources\Members\Tables;

use App\Filament\Resources\Customers\Schemas\MemberForm;
use App\Filament\Resources\Members\MemberResource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\CheckboxList;
use Filament\Actions\Action;
use Filament\Infolists\Components\ImageEntry;
use Filament\Support\Enums\TextSize;
use App\Models\Addon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use App\Filament\Resources\Members\Pages\ListMembers;
use App\Filament\Resources\Members\Pages\CreateMember;
use Exception;

use App\Models\Member;

use Filament\Forms\Components\DatePicker;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;

use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

use App\Models\Payment;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use App\Filament\Traits\PaymentCalculationsTrait;

use App\Filament\Traits\CalcPayDateRanges;

use Illuminate\Support\Collection;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;


use App\Models\Package;
use App\Services\BulkPaymentService;
use App\Services\MemberUpdateService;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;


use Filament\Infolists\Infolist;

use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;




class MemberTable
{
    use CalcPayDateRanges, PaymentCalculationsTrait;
   

    public static function configure(Table $table): Table
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
                    ->disk('public')
                    ->extraImgAttributes([
                        'class' => 'transition-transform duration-300 hover:scale-[2] hover:z-50',
                    ])
                    ->defaultImageUrl(url('/images/default-user.png')),
                TextColumn::make('user.name')
                    ->label('Member Details')
                    ->searchable()
                    ->sortable()
                    // ->color('primary') 
                    ->weight('bold')
                    // Use an HtmlString to style the description manually
                    ->description(fn ($record): HtmlString => new HtmlString(
                        '<span class="text-yellow-600 capitalize font-medium text-xs">' . 
                        $record->package->name . 
                        '</span>'
                    ))

                    
                    ->tooltip('See In Detail')  
                    
                    ->action(
                        Action::make('view_member_profile')
                            ->modalHeading('Member Profile')
                            ->modalWidth('2xl')
                            ->modalSubmitAction(false) // View-only
                            ->infolist([
                                
                                    // Row 1: Header with Profile Info
                                    Grid::make(3)
                                        ->schema([
                                            ImageEntry::make('user.avatar')
                                                ->label(false)
                                                ->circular()
                                                ->grow()
                                                ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=7F9CF5&background=EBF4FF'),
                                            
                                            Group::make([
                                                TextEntry::make('name')
                                                    ->label(false)
                                                    ->weight('black')
                                                    ->size(TextSize::Large),
                                                TextEntry::make('email')
                                                    ->label(false)
                                                    ->icon('heroicon-m-envelope')
                                                    ->color('gray'),
                                            ])->columnSpan(2),
                                        ]),
                
                                    // Row 2: Status Highlight
                                    Section::make('Membership Status')
                                        ->columns(3)
                                        ->compact()
                                        ->schema([
                                            
                                            TextEntry::make('status')
                                                ->label('Current Status')
                                                ->badge()
                                                ->color(fn(string $state): string => match ($state) {

                                                    'active' => 'success',
                                                    'inactive' => 'danger',
                                                    // 'suspended' => 'warning',
                                                    'expired' => 'gray',
                                                    default => 'primary'
                                                }),
                                            TextEntry::make('package.name')
                                                ->label('Package Name')
                                                ->badge()

                                                ->color('info'),
                                            TextEntry::make('duration_value')
                                                ->label('Duration')
                                                ->badge()
                                                ->color('info'),
                                            TextEntry::make('starting_date')
                                                ->label('Starting From')
                                                ->badge()
                                                ->date()
                                                ->color('info'),
                                            TextEntry::make('valid_from')
                                                ->label('Access From')
                                                ->badge()
                                                ->date()
                                                ->color('info'),
                                            TextEntry::make('valid_until')
                                                ->label('Access Until')
                                                ->date()
                                                ->badge()
                                                ->color(fn ($state) => $state > now() ? 'success' : 'danger'),
                                        ]),
                
                                    // Row 3: Contact & Personal Details
                                    Section::make('Contact Information')
                                        ->columns(3)
                                        ->icon('heroicon-m-phone')
                                        ->schema([
                                            TextEntry::make('user.phone')
                                                ->label('Phone Number')
                                                ->copyable()
                                                ->icon('heroicon-m-device-phone-mobile'),
                                            TextEntry::make('emergency_contact_name')
                                                ->label('Emergency Contact'),
                                            TextEntry::make('emergency_contact_phone')
                                                ->copyable()
                                                ->label('Emergency Contact')
                                                ->icon('heroicon-m-device-phone-mobile'),

                                            TextEntry::make('user.note')
                                                // ->label('Additional Note')
                                                ->label(fn () => new HtmlString('
                                                        <div class="flex items-center gap-1.5">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-5">
                                                        <path d="m5.433 13.917 1.262-3.155A4 4 0 0 1 7.58 9.42l6.92-6.918a2.121 2.121 0 0 1 3 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 0 1-.65-.65Z" />
                                                        <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0 0 10 3H4.75A2.75 2.75 0 0 0 2 5.75v9.5A2.75 2.75 0 0 0 4.75 18h9.5A2.75 2.75 0 0 0 17 15.25V10a.75.75 0 0 0-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5Z" />
                                                        </svg>

                                                            <span>Additional Note</span>
                                                        </div>
                                                    '))
                                                ->columnSpanFull()
                                                // ->icon('heroicon-m-document-text')
                                                ->columnSpanFull(),
                                        ]),
                                    
                                ])
                        
                ),
                        

                TextColumn::make('membership_id')
                ->searchable(),
                // TextColumn::make('user.phone')
                //     ->label('Phone')
                //     ->icon('heroicon-o-phone')
                //     ->badge()
                //     ->copyable()
                //     ->copyMessage('Phone number copied')
                //     ->copyMessageDuration(1500)
                //     ->tooltip('Click to copy phone number'),
                // TextColumn::make('package.name')
                //     ->badge()
                //     ->color('info')
                //     ->icon('heroicon-o-gift')
                //     ->tooltip(fn($record) => "Price: " . number_format($record->package->price, 2) . " Birr for a {$record->package->duration_unit}"),
                // TextColumn::make('duration_value')
                //     ->label('Duration')
                //     ->numeric()
                //     ->formatStateUsing(
                //         fn($state, $record) =>
                //         $state . ' ' . ($record->package?->duration_unit ?: 'unit')
                //     )
                //     ->tooltip('Default unit if no package assigned')
                //     ->sortable(),
                // TextColumn::make('starting_date')
                //     ->label('Member Since')
                //     ->dateTime('M d, Y')
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true)
                //     ->tooltip(fn($record): string => "Exact registration: " . $record->starting_date->diffForHumans()),
                // TextColumn::make('valid_from')
                //     ->date()
                //     ->tooltip('Membership Valid From Date')
                //     ->toggleable(isToggledHiddenByDefault: true)
                //     ->sortable(),

                TextColumn::make('addons_summary')
                    ->label('Addons')
                    ->state(function ($record) {
                        $addons = $record->addons->unique('id');

                        return $addons->count()
                            ? $addons->count() . ' Addons (' . $addons->sum('price') . ' ETB)'
                            : 'No Addons';
                    })
                    ->badge()
                    ->color('success')
                    ->tooltip(function ($record) {
                        return $record->addons
                            ->unique('id')
                            ->map(fn ($addon) =>
                                "{$addon->name} - {$addon->price} ETB"
                            )
                            ->join("\n");
                    }),
                TextColumn::make('valid_until')
                        ->date()
                        ->label('Expiry Date')
                        ->color('info')
                        ->badge()
                        ->tooltip('See In Detail')
                        ->sortable()
                        ->action(
                            Action::make('view_payment_history')
                                ->label(false)
                                ->icon('heroicon-m-clock')
                                ->color('info')
                                ->modalHeading('Membership History')
                                ->modalSubmitAction(false)
                                ->modalCancelActionLabel('Close')
                                ->modalWidth('md')

                                // THIS IS THE IMPORTANT CHANGE
                                ->infolist([
                                    Section::make('Current Status')
                                        ->icon('heroicon-m-check-badge')
                                        ->schema([
                                            TextEntry::make('valid_until')
                                                ->label('Current Expiry')
                                                ->date()
                                                ->badge()
                                                ->color('info')
                                                ->weight('bold'),
                                        ]),

                                    Section::make('Previous Payments')
                                        ->icon('heroicon-m-credit-card')
                                        ->schema([
                                            RepeatableEntry::make('payments')
                                                ->label(false)
                                                ->schema([
                                                    Grid::make(1)
                                                        ->schema([
                                                            TextEntry::make('valid_until')
                                                                ->label(false)
                                                                ->date()
                                                                ->color('success')
                                                                ->weight('bold'),
                                                        ]),
                                                ]),
                                        ]),
                                ])
                                    ),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {

                        'active' => 'success',
                        'inactive' => 'danger',
                        // 'suspended' => 'warning',
                        'expired' => 'gray',
                        default => 'primary'
                    }),
                // TextColumn::make('emergency_contact_name')
                //     ->searchable(),
                // TextColumn::make('emergency_contact_phone')
                //     ->label('Emergency Phone')
                //     ->icon('heroicon-o-phone')
                //     ->badge()
                //     ->copyable()
                //     ->copyMessage('Phone number copied')
                //     ->copyMessageDuration(1500)
                //     ->tooltip('click to copy Emergency Contact'),
                
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->defaultSort('created_at', 'desc')
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
                        'expired' => 'expired',
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
                    ->schema([
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
                    ->schema([
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

            ->recordActions([
                Action::make('edit')
                    ->modalWidth('4xl') // Makes the popup size
                    ->tooltip('Quick Edit Member')
                    ->slideOver()
                    ->modalHeading('Update Member Profile')
                    ->modalDescription('Changes will be applied immediately to the member record.')
                    ->modalSubmitActionLabel('Save Changes')
                    ->icon('heroicon-m-pencil-square')
                    ->color('warning')
                    ->schema(fn (Schema $schema) => MemberResource::form($schema))
                    ->mountUsing(function ($form, Member $record) {


                        

                        $data = $record->toArray();
                        if ($record->user) {

                            $data['user'] = $record->user->toArray();
                        }
                        $data['addons'] = $record->addons()
                        ->pluck('addons.id')
                        ->toArray();
                        $form->fill($data);
                    })
                    ->action(function (
                        Member $record,
                        array $data
                    ) {
                    
                        try {
                    
                            $member = app(
                                MemberUpdateService::class
                            )->update(
                                $record,
                                $data
                            );
                    
                            Notification::make()
                                ->success()
                                ->title('Member Updated')
                                ->body(
                                    "{$member->user->name} updated successfully."
                                )
                                ->send();
                    
                        } catch (\Throwable $e) {
                    
                            report($e);
                    
                            Notification::make()
                                ->danger()
                                ->title('Update Failed')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
               
                    
                // Add the automatic Pay action
                // HYBRID ADDONS 
                Action::make('pay')
                    ->label('Pay')
                    ->icon('heroicon-o-credit-card')
                    ->color('primary')
                    ->slideOver()
                    ->modalHeading('Process Payment')
                    ->modalDescription('Review and adjust payment details before processing.')
                    ->modalSubmitActionLabel('Confirm Payment')
                    ->schema([
                        Section::make('Subscription Details 📝')
                            ->description('Select package and any addons/extras.')
                            ->schema([
                                Select::make('package_id')
                                    ->label('Package')
                                    ->options(Package::pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        $set('addons', []); // reset when package changes
                                        self::calculateTotalAmount($set, $get);
                                    }),

                                TextInput::make('duration_value')
                                    ->label('Duration')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->required()
                                    ->live()
                                    ->suffix(fn(Get $get) => $get('package_id')
                                        ? Package::find($get('package_id'))?->duration_unit ?? 'Unit'
                                        : 'Unit')
                                    ->afterStateUpdated(fn(Set $set, Get $get) => self::calculateTotalAmount($set, $get)),

                                // ADDONS CHECKBOXLIST (reliable pre-check) 
                                CheckboxList::make('addons')
                                    ->label('Addons / Extras')
                                    ->options(function (Get $get) {
                                        $packageId = $get('package_id');
                                        if (!$packageId)
                                            return [];
                                        $package = Package::with('addons')->find($packageId);
                                        return $package?->addons->pluck('name', 'id')->toArray() ?? [];
                                    })
                                    ->live()
                                    ->afterStateUpdated(fn(Set $set, Get $get) => self::calculateTotalAmount($set, $get))
                                    ->afterStateHydrated(fn(Set $set, Get $get) => self::calculateTotalAmount($set, $get))  // FIXES INITIAL LOAD
                                    ->columnSpanFull()
                                    ->visible(fn(Get $get) => (bool) $get('package_id')),
                                
                            ])->columns(2),

                            Section::make('Payment & Dates 💰')
                                ->description('Enter payment details and validity period.')
                                ->schema([
                                    TextInput::make('amount')
                                        ->label('Total Amount')
                                        ->prefix('ETB')
                                        ->readOnly()
                                        ->numeric()
                                        ->required(),

                                Select::make('payment_method')
                                    ->options(['cash' => 'Cash', 'online' => 'Online'])
                                    ->default('cash')
                                    ->required(),
                                    Select::make('payment_method')
                                        ->options([
                                            'cash' => 'Cash',
                                            'online' => 'Online',
                                        ])
                                        ->default('cash')
                                        ->required(),

                                    DatePicker::make('valid_from')
                                        ->label('Valid From')
                                        ->required()
                                        ->native(false)
                                        ->live()
                                        ->afterStateUpdated(function (Set $set, Get $get) {
                                            // Recalculate valid_until if the start date is manually changed
                                            $packageId = $get('package_id');
                                            $duration = (int) $get('duration_value');
                                            $validFrom = $get('valid_from');

                                            if ($packageId && $validFrom) {
                                                $package = Package::find($packageId);
                                                $unit = $package->duration_unit ?? 'month';
                                                $until = Carbon::parse($validFrom);
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

                                    DatePicker::make('valid_until')
                                        ->label('Valid Until')
                                        ->required()
                                        ->native(false)
                                        ->readOnly(),

                                Textarea::make('notes')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])->columns(4),
                    ])

                    ->mountUsing(function (Schema $schema, Member $record) {
                        $validFrom = now()->format('Y-m-d');
                        if ($record->valid_until) {
                            $expiry = Carbon::parse($record->valid_until)->endOfDay();
                            if ($expiry->greaterThan(now()->startOfDay())) {
                                $validFrom = $expiry->addDay()->format('Y-m-d');
                            }
                        }
                    
                        $package = $record->package;
                        $duration = $record->duration_value ?: 1;
                    
                        $validUntil = $package
                            ? self::calculateCoreValidUntil(Carbon::parse($validFrom), $package, $duration)
                            : Carbon::parse($validFrom)->addMonth();
                    
                        // RELIABLE PRE-SELECTION 
                        $preSelected = [];
                        if ($package) {
                            $packageAddonIds = $package->addons->pluck('id')->toArray();
                            $memberAddonIds = $record->addons->pluck('id')->toArray();
                            $preSelected = array_intersect($packageAddonIds, $memberAddonIds);
                        }
                    
                        //CALCULATE TOTAL WITH ADDONS
                        $packageAmount = $package ? ($package->price * $duration) : 0;
                        $addonAmount = self::calculateAddonTotal($preSelected, $duration);
                        $initialTotalAmount = $packageAmount + $addonAmount;
                    
                        $schema->fill([
                            'package_id' => $record->package_id,
                            'duration_value' => $duration,
                            'amount' => $initialTotalAmount, // Uses the combined total here
                            'payment_method' => 'cash',
                            'valid_from' => $validFrom,
                            'valid_until' => $validUntil->format('Y-m-d'),
                            'addons' => $preSelected,
                        ]);
                    })

                    ->action(function (
                        Member $record,
                        array $data
                    ) {
                    
                        app(PaymentService::class)
                            ->createMembershipPayment(
                                member: $record,
                                packageId: $data['package_id'],
                                addonIds: $data['addons'] ?? [],
                                durationValue: $data['duration_value'],
                                paymentMethod: $data['payment_method'],
                                notes: $data['notes'] ?? null,
                            );
                    
                        Notification::make()
                            ->success()
                            ->title('Payment Created')
                            ->send();
                    })

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('bulk_pay')
                        ->label('Bulk Pay Selected Members')
                        ->icon('heroicon-o-credit-card')
                        ->color('success')
                        ->action(function (Collection $records) {

                            $result = app(
                                BulkPaymentService::class
                            )->process($records);
                    
                            $message =
                                "Successful: {$result['success']}\n" .
                                "Failed: {$result['failed']}\n" .
                                "Skipped: {$result['skipped']}";
                    
                            Notification::make()
                                ->title('Bulk Payment Completed')
                                ->body($message)
                                ->success()
                                ->send();
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

}