<?php

namespace App\Filament\Resources\Payments;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Illuminate\Support\Str;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Filament\Resources\Payments\Pages\ViewPayment;
use App\Filament\Resources\Payments\Pages\EditPayment;
use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Member;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Fieldset;
use App\Filament\Traits\PaymentCalculationsTrait;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;


class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-currency-dollar';
    use PaymentCalculationsTrait;
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(3)->schema([
                    Section::make('Subscription Details 📝')
                        ->description('Select the member and their desired package.')
                        ->columnSpan(1)
                        ->schema([
                            Select::make('member_id')
                                ->label('Member (Name - ID)')
                                ->relationship(
                                    name: 'member',
                                    titleAttribute: 'membership_id', // fallback, but we override display below
                                    modifyQueryUsing: fn(Builder $query) => $query->with('user')
                                )
                                ->getOptionLabelFromRecordUsing(fn(Member $record) => "{$record->user->name} ({$record->membership_id})")
                                ->searchable()
                                ->getSearchResultsUsing(function (string $search) {
                                    return Member::query()
                                        ->with('user')
                                        ->whereHas('user', function (Builder $query) use ($search) {
                                            $query->where('name', 'like', "%{$search}%");
                                        })
                                        ->orWhere('membership_id', 'like', "%{$search}%")
                                        ->limit(50)
                                        ->get()
                                        ->mapWithKeys(fn(Member $member) => [
                                            $member->id => "{$member->user->name} ({$member->membership_id})"
                                        ]);
                                })
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    // Auto-fill member's package and addons when member is selected
                                    if ($state) {
                                        $member = Member::with(['addons', 'package.addons'])->find($state);
                                        if ($member) {
                                            $set('package_id', $member->package_id);
                                            $set('duration_value', $member->duration_value ?: 1);

                                            // Pre-select addons the member currently has
                                            if ($member->package_id && $member->package) {
                                                $packageAddonIds = $member->package->addons->pluck('id')->toArray();
                                                $memberAddonIds = $member->addons->pluck('id')->toArray();
                                                $preSelected = array_intersect($packageAddonIds, $memberAddonIds);
                                                $set('addons', array_values($preSelected));
                                            } else {
                                                $set('addons', []);
                                            }
                                        }
                                    }

                                    self::determineValidFromDate($set, $get);
                                    self::calculateValidUntil($set, $get);
                                    self::calculateTotalAmount($set, $get); // Use total amount to include addons
                                })
                                ->helperText(
                                    fn(Get $get) =>
                                    $get('member_id') ?
                                    'Current Expiry: ' . (Member::find($get('member_id'))->valid_until ?? 'N/A') :
                                    'Select a member to view expiry.'
                                ),

                            Select::make('package_id')
                                ->relationship('package', 'name')
                                ->label('Package')
                                ->required()
                                ->searchable()
                                ->live()
                                // to calculate amount and dates when package changes
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $set('addons', []); // Reset addons when package changes
                                    self::determineValidFromDate($set, $get);
                                    self::calculateValidUntil($set, $get);
                                    self::calculateTotalAmount($set, $get);
                                }),

                            TextInput::make('duration_value')
                                ->label('Duration Value')
                                ->required()
                                ->numeric()
                                ->minValue(1)
                                ->default('1')
                                ->live()
                                ->suffix(
                                    fn(Get $get) =>
                                    $get('package_id') ?
                                    Package::find($get('package_id'))->duration_unit :
                                    'Unit'
                                )
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    self::determineValidFromDate($set, $get);
                                    self::calculateValidUntil($set, $get);
                                    self::calculateTotalAmount($set, $get);
                                }),

                            // Addons Checkbox List for Payment creation
                            CheckboxList::make('addons')
                                ->label('Addons / Extras')
                                ->options(function (Get $get) {
                                    $packageId = $get('package_id');
                                    if (!$packageId) return [];
                                    $package = Package::with('addons')->find($packageId);
                                    return $package?->addons->pluck('name', 'id')->toArray() ?? [];
                                })
                                ->live()
                                ->afterStateUpdated(fn(Set $set, Get $get) => self::calculateTotalAmount($set, $get))
                                ->afterStateHydrated(fn(Set $set, Get $get) => self::calculateTotalAmount($set, $get))
                                ->columnSpanFull()
                                ->visible(fn(Get $get) => (bool) $get('package_id')),

                        ]),
                    Section::make('Payment & Dates 💰')
                        ->description('Enter payment details and validity period.')
                        ->columnSpan(1)
                        ->schema([
                            TextInput::make('amount')
                                ->label('Total Amount')
                                ->required()
                                ->numeric()
                                ->readOnly()
                                ->suffix('Birr')
                                ->default(0),

                            Select::make('payment_method')
                                ->options([
                                    'cash' => 'Cash',
                                    'online' => 'Online',
                                ])
                                ->required()
                                ->default('cash')
                                ->columnSpan(1),

                            DatePicker::make('payment_date')
                                ->native(false)
                                ->default(now())
                                ->readOnly(),

                            DatePicker::make('valid_from')
                                ->native(false)
                                ->required()
                                
                                ->default(function (Get $get) {

                                    $memberId = $get('member_id');
                                    if (!$memberId) {
                                        return now()->format('Y-m-d');
                                    }

                                    $member = Member::find($memberId);

                                    if ($member && $member->valid_until) {
                                        $expiryDate = Carbon::parse($member->valid_until)->endOfDay();
                                        $today = now()->startOfDay();

                                        if ($expiryDate->greaterThan($today)) {
                                            return $expiryDate->addDay()->format('Y-m-d');
                                        }
                                    }

                                    return now()->format('Y-m-d');
                                })
                                ->live()
                                ->afterStateUpdated(function (Set $set, Get $get) {
                                    //  date calculation when valid_from changes
                                    self::calculateValidUntil($set, $get);
                                }),
                            DatePicker::make('valid_until')
                                ->native(false)
                                ->required()
                                ->readOnly()
                                ->live()
                                ->extraAttributes(['class' => 'font-bold text-primary-600'])
                                ->afterStateHydrated(function (Set $set, Get $get) {
                                    self::calculateValidUntil($set, $get);
                                })
                                ->afterStateUpdated(function (Set $set, Get $get) {
                                    self::calculateValidUntil($set, $get);
                                }),
                        ])->columns(2),


                    Section::make('Tracking & Status 🏷️')
                        ->description('Transaction details and final notes.')
                        ->columnSpan(1)
                        ->schema([
                            // Transaction ID field with a default value using a unique ID generator
                            TextInput::make('transaction_id')
                                ->maxLength(255)
                                ->default(fn() => 'TXN-' . strtoupper(Str::random(10))) //  Auto-generate
                                ->readOnly()
                                ->required(),

                            Select::make('status')
                                ->options([
                                    'pending' => 'Pending',
                                    'completed' => 'Completed',
                                    'failed' => 'Failed',
                                ])
                                ->required()
                                ->default('completed'),
                            Textarea::make('notes')
                                ->columnSpanFull()
                                ->rows(2),
                        ])->columns(1),
                ]),
            ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('Roll No.')->label('Roll No.')->rowIndex(),
                ImageColumn::make('member.user.avatar')
                    ->label('Profile')
                    ->circular()
                    ->extraImgAttributes([
                        'class' => 'transition-transform duration-300 hover:scale-[4] hover:z-50',
                    ])
                    ->defaultImageUrl(url('/images/default-user.png')),
                TextColumn::make('member.user.name')
                    ->label('Member')
                    ->formatStateUsing(fn($record) => $record->member?->user?->name . ' (' . ($record->member?->membership_id ?? 'N/A') . ')')
                    // Search using name or membership_id
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('member.user', function (Builder $q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(query: function (Builder $query): Builder {
                        return $query->join('members', 'payments.member_id', '=', 'members.id')
                            ->join('users', 'members.user_id', '=', 'users.id')
                            ->orderBy('users.name');
                    }),
                TextColumn::make('package.name')
                ->badge()
                ->color('info')
                ->icon('heroicon-o-gift')
                ->tooltip(fn($record) => "Price: " . number_format($record->package->price, 2) . " Birr for a {$record->package->duration_unit}"),
                TextColumn::make('amount')
                    ->numeric()
                    ->sortable()
                    ->tooltip('Total amount paid in Birr'),
                TextColumn::make('payment_method')
                    ->sortable()
                    ->tooltip('Method of payment used')
                    ->badge(fn ($state) => $state === 'cash' ? 'success' : 'primary')
                    ->icon(fn ($state) => $state === 'cash' ? 'heroicon-o-currency-dollar' : 'heroicon-o-credit-card'),
                TextColumn::make('payment_date')
                    ->date()
                    ->tooltip('Date when the payment was made')
                    ->sortable(),
                TextColumn::make('transaction_id')
                ->label('Transaction ID')
                ->limit(20)
                    ->searchable()
                    ->tooltip(fn($state) => "Transaction ID: {$state}"),
                TextColumn::make('valid_from')
                    ->date()
                    ->tooltip('Start date of membership validity')
                    ->sortable(),
                TextColumn::make('valid_until')
                    ->date()
                    ->tooltip('End date of membership validity')
                    ->sortable(),
                TextColumn::make('status')
                ->badge()
                -> color(fn ($state) => match ($state) {
                    'completed' => 'success',
                    'pending' => 'warning',
                    'failed' => 'danger',
                    default => 'secondary',
                })
                ->icon(fn ($state) => match ($state) {
                    'completed' => 'heroicon-o-check-circle',
                    'pending' => 'heroicon-o-clock',
                    'failed' => 'heroicon-o-x-circle',
                    default => 'heroicon-o-question-mark-circle',
                })
                ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->striped()
            ->deferloading()
            ->filters([
                Filter::make('payment_date_range')
                    ->schema([
                        DatePicker::make('payment_date_from')
                            ->label('Payment Date From')
                            ->native(false),
                        DatePicker::make('payment_date_to')
                            ->label('Payment Date To')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['payment_date_from'])) {
                            $query->whereDate('payment_date', '>=', $data['payment_date_from']);
                        }
                        if (!empty($data['payment_date_to'])) {
                            $query->whereDate('payment_date', '<=', $data['payment_date_to']);
                        }
                        return $query;
                    }),
                SelectFilter::make('user')
                    ->label('Member')
                    ->relationship(
                        name: 'member',
                        titleAttribute: 'membership_id',
                        modifyQueryUsing: fn (Builder $query) => $query->with('user')
                    )
                    ->getOptionLabelFromRecordUsing(fn (Member $record) => "{$record->user->name} ({$record->membership_id})")
                    ->searchable(['member.user.name', 'member.membership_id'])
                    ->multiple()
                    ->preload()
                    ->placeholder('All Members'),
                SelectFilter::make('package_id')
                    ->label('Package')
                    ->multiple()
                    ->relationship('package', 'name'),
                    Filter::make('amount_range')
                    ->schema([
                        TextInput::make('amount_min')
                            ->label('Minimum Amount')
                            ->numeric(),
                        TextInput::make('amount_max')
                            ->label('Maximum Amount')
                            ->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (isset($data['amount_min'])) {
                            $query->where('amount', '>=', $data['amount_min']);
                        }
                        if (isset($data['amount_max'])) {
                            $query->where('amount', '<=', $data['amount_max']);
                        }
                        return $query;
                    }),

                    
            ])
            ->recordActions([
                ViewAction::make(),
                // Tables\Actions\EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
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
            'index' => ListPayments::route('/'),
            'create' => CreatePayment::route('/create'),
            'view' => ViewPayment::route('/{record}'),
            'edit' => EditPayment::route('/{record}/edit'),
        ];
    }
}