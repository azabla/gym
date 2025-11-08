<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Member;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Fieldset;
use App\Filament\Traits\PaymentCalculationsTrait;


class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    use PaymentCalculationsTrait;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)->schema([
                    Section::make('Subscription Details 📝')
                        ->description('Select the member and their desired package.')
                        ->columnSpan(1)
                        ->schema([
                            Forms\Components\Select::make('member_id')
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
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    self::determineValidFromDate($set, $get);
                                    self::calculateValidUntil($set, $get);
                                    self::calculateAmount($set, $get);
                                })
                                ->helperText(
                                    fn(Forms\Get $get) =>
                                    $get('member_id') ?
                                    'Current Expiry: ' . (Member::find($get('member_id'))->valid_until ?? 'N/A') :
                                    'Select a member to view expiry.'
                                ),

                            Forms\Components\Select::make('package_id')
                                ->relationship('package', 'name')
                                ->label('Package')
                                ->required()
                                ->searchable()
                                ->live()
                                // to calculate amount and dates when package changes
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    self::determineValidFromDate($set, $get);
                                    // date calculation
                                    self::calculateValidUntil($set, $get);
                                    // amount calculation
                                    self::calculateAmount($set, $get);
                                }),

                            Forms\Components\TextInput::make('duration_value')
                                ->label('Duration Value')
                                ->required()
                                ->numeric()
                                ->minValue(1)
                                ->default('1')
                                ->live()
                                ->suffix(
                                    fn(Forms\Get $get) =>
                                    $get('package_id') ?
                                    Package::find($get('package_id'))->duration_unit :
                                    'Unit'
                                )
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    self::determineValidFromDate($set, $get);
                                    self::calculateValidUntil($set, $get);
                                    self::calculateAmount($set, $get);
                                }),

                        ]),
                    Section::make('Payment & Dates 💰')
                        ->description('Enter payment details and validity period.')
                        ->columnSpan(1)
                        ->schema([
                            Forms\Components\TextInput::make('amount')
                                ->label('Total Amount')
                                ->required()
                                ->numeric()
                                ->readOnly()
                                ->suffix('Birr')
                                ->default(0),

                            Forms\Components\Select::make('payment_method')
                                ->options([
                                    'cash' => 'Cash',
                                    'online' => 'Online',
                                ])
                                ->required()
                                ->default('cash')
                                ->columnSpan(1),

                            Forms\Components\DatePicker::make('payment_date')
                                ->native(false)
                                ->default(now())
                                ->readOnly(),

                            Forms\Components\DatePicker::make('valid_from')
                                ->native(false)
                                ->required()
                                
                                ->default(function (Forms\Get $get) {

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
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                    //  date calculation when valid_from changes
                                    self::calculateValidUntil($set, $get);
                                }),
                            Forms\Components\DatePicker::make('valid_until')
                                ->native(false)
                                ->required()
                                ->readOnly()
                                ->live()
                                ->extraAttributes(['class' => 'font-bold text-primary-600'])
                                ->afterStateHydrated(function (Forms\Set $set, Forms\Get $get) {
                                    self::calculateValidUntil($set, $get);
                                })
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                    self::calculateValidUntil($set, $get);
                                }),
                        ])->columns(2),


                    Section::make('Tracking & Status 🏷️')
                        ->description('Transaction details and final notes.')
                        ->columnSpan(1)
                        ->schema([
                            // Transaction ID field with a default value using a unique ID generator
                            Forms\Components\TextInput::make('transaction_id')
                                ->maxLength(255)
                                ->default(fn() => 'TXN-' . strtoupper(\Illuminate\Support\Str::random(10))) //  Auto-generate
                                ->readOnly()
                                ->required(),

                            Forms\Components\Select::make('status')
                                ->options([
                                    'pending' => 'Pending',
                                    'completed' => 'Completed',
                                    'failed' => 'Failed',
                                ])
                                ->required()
                                ->default('completed'),
                            Forms\Components\Textarea::make('notes')
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
                Tables\Columns\TextColumn::make('member.user.name')
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
                Tables\Columns\TextColumn::make('package.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method'),
                Tables\Columns\TextColumn::make('payment_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('valid_from')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('valid_until')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status'),
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
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'view' => Pages\ViewPayment::route('/{record}'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
