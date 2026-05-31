<?php
namespace App\Filament\Resources\Payments\Tables;


use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkActionGroup;

use App\Models\Member;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\EditAction;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;

class PaymentTable
{
    public static function configure(Table $table): Table
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
                ->color(fn ($state) => match ($state) {
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
                EditAction::make()
                ->modalWidth('4xl') 
                ->tooltip('Quick Edit Payments')
                ->slideOver() 
                ->modalHeading('Update Package Profile')
                ->modalDescription('Changes will be applied immediately to the package record.')
                ->modalSubmitActionLabel('Save Changes')
                ->icon('heroicon-m-pencil-square')
                ->color('warning'),
        ], position: RecordActionsPosition::BeforeColumns)
                // Tables\Actions\EditAction::make(),
            
            ->toolbarActions([
                BulkActionGroup::make([
                    // Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}