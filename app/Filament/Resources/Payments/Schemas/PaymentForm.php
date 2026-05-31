<?php
namespace App\Filament\Resources\Payments\Schemas;

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
class PaymentForm
{
    use PaymentCalculationsTrait;
    public static function configure(Schema $schema): Schema
{
    return $schema
        ->components([
            Grid::make([
                'default' => 1,
                'lg' => 3, // Leveraging a 3-column structural layout
            ])
            ->schema([
                
                /*
                |--------------------------------------------------------------------------
                | LEFT SIDE: Subscription Details
                |--------------------------------------------------------------------------
                */
                Section::make('Subscription Details')
                    ->description('Select the member and their desired package setup.')
                    ->icon('heroicon-m-user-plus')
                    ->columnSpan(['lg' => 2]) // Takes up 2/3 of the screen width on large viewports
                    ->columns(2) // Form elements inside will align nicely in 2 columns
                    ->schema([
                        Select::make('member_id')
                            ->label('Member (Name - ID)')
                            ->relationship(
                                name: 'member',
                                titleAttribute: 'membership_id',
                                modifyQueryUsing: fn(Builder $query) => $query->with('user')
                            )
                            ->getOptionLabelFromRecordUsing(fn(Member $record) => "{$record->user->name} ({$record->membership_id})")
                            ->searchable()
                            ->preload() // Improves perceived speed for smaller datasets
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
                            ->columnSpanFull() // Member lookup is critical, give it full row space
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if ($state) {
                                    $member = Member::with(['addons', 'package.addons'])->find($state);
                                    if ($member) {
                                        $set('package_id', $member->package_id);
                                        $set('duration_value', $member->duration_value ?: 1);

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
                                self::calculateTotalAmount($set, $get);
                            })
                            ->helperText(
                                fn(Get $get) =>
                                $get('member_id') ?
                                '💡 Current Expiry: ' . (Member::find($get('member_id'))->valid_until ?? 'N/A') :
                                'Select a member to view active subscription expiry details.'
                            ),

                        Select::make('package_id')
                            ->relationship('package', 'name')
                            ->label('Package')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                $set('addons', []); 
                                self::determineValidFromDate($set, $get);
                                self::calculateValidUntil($set, $get);
                                self::calculateTotalAmount($set, $get);
                            }),

                        TextInput::make('duration_value')
                            ->label('Duration')
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

                        CheckboxList::make('addons')
                            ->label('Available Addons / Extras')
                            ->options(function (Get $get) {
                                $packageId = $get('package_id');
                                if (!$packageId) return [];
                                $package = Package::with('addons')->find($packageId);
                                return $package?->addons->pluck('name', 'id')->toArray() ?? [];
                            })
                            ->live()
                            ->gridDirection('horizontal')
                            ->columns(2) // Distributes options into a nice 2-column grid row
                            ->afterStateUpdated(fn(Set $set, Get $get) => self::calculateTotalAmount($set, $get))
                            ->afterStateHydrated(fn(Set $set, Get $get) => self::calculateTotalAmount($set, $get))
                            ->columnSpanFull()
                            ->visible(fn(Get $get) => (bool) $get('package_id')),
                    ]),

                /*
                |--------------------------------------------------------------------------
                | RIGHT SIDE: Payment & Dates Context
                |--------------------------------------------------------------------------
                */
                Section::make('Payment & Dates')
                    ->description('Financial logs and valid parameters.')
                    ->icon('heroicon-m-credit-card')
                    ->columnSpan(['lg' => 1]) // Sticks to a neat sidebar format on large displays
                    ->columns(1)
                    ->schema([
                        TextInput::make('amount')
                            ->label('Total Amount')
                            ->required()
                            ->numeric()
                            ->readOnly()
                            ->prefixIcon('heroicon-m-banknotes') // Visual money feedback
                            ->suffix('Birr')
                            ->extraInputAttributes(['class' => 'font-bold text-lg text-success-600 dark:text-success-400']) // Emphasize final price
                            ->default(0),

                        Select::make('payment_method')
                            ->options([
                                'cash' => 'Cash',
                                'online' => 'Online',
                            ])
                            ->required()
                            ->default('cash'),

                        DatePicker::make('payment_date')
                            ->native(false)
                            ->default(now())
                            ->readOnly(),

                        DatePicker::make('valid_from')
                            ->native(false)
                            ->required()
                            ->default(function (Get $get) {
                                $memberId = $get('member_id');
                                if (!$memberId) return now()->format('Y-m-d');

                                $member = Member::find($memberId);
                                if ($member && $member->valid_until) {
                                    $expiryDate = Carbon::parse($member->valid_until)->endOfDay();
                                    if ($expiryDate->greaterThan(now()->startOfDay())) {
                                        return $expiryDate->addDay()->format('Y-m-d');
                                    }
                                }
                                return now()->format('Y-m-d');
                            })
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                self::calculateValidUntil($set, $get);
                            }),

                        DatePicker::make('valid_until')
                            ->native(false)
                            ->required()
                            ->readOnly()
                            ->live()
                            ->prefixIcon('heroicon-m-calendar-days')
                            ->extraInputAttributes(['class' => 'font-extrabold text-primary-600 dark:text-primary-400'])
                            ->afterStateHydrated(function (Set $set, Get $get) {
                                self::calculateValidUntil($set, $get);
                            })
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                self::calculateValidUntil($set, $get);
                            }),
                    ]),

                /*
                |--------------------------------------------------------------------------
                | BOTTOM FULL ROW: Tracking & Status
                |--------------------------------------------------------------------------
                */
                Section::make('Tracking & Status')
                    ->description('Transaction bookkeeping system records.')
                    ->icon('heroicon-m-document-magnifying-glass')
                    ->columnSpanFull()
                    ->columns(3) // Split horizontally across the baseline
                    ->compact() // Uses less spacing paddings since it sits below everything else
                    ->schema([
                        TextInput::make('transaction_id')
                            ->label('Reference ID')
                            ->maxLength(255)
                            ->default(fn() => 'TXN-' . strtoupper(Str::random(10)))
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
                            ->rows(1) // Keep it small, scales out naturally when typed into
                            ->autosize(), 
                    ]),
            ]),
        ])->columns(1);
}
}