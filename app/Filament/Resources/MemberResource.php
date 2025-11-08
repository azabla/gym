<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MemberResource\Pages;
use App\Filament\Resources\MemberResource\RelationManagers;
use App\Models\Member;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Payment;
use App\Models\Package;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;
use App\Filament\Traits\PaymentCalculationsTrait; 

class MemberResource extends Resource
{
    use PaymentCalculationsTrait; 
    protected static ?string $model = Member::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'User Management';
    protected static ?string $navigationLabel = 'All Members';
        public static function form(Form $form): Form
    {

        return $form
            ->schema([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),

                TextInput::make('package_id')
                    ->numeric()
                    ->default(null),
                TextInput::make('duration_value')
                    ->required()
                    ->numeric()
                    ->default(1),
                DatePicker::make('starting_date'),
                DatePicker::make('valid_from'),
                DatePicker::make('valid_until'),
                TextInput::make('status')
                    ->required(),
                TextInput::make('emergency_contact_name')
                    ->maxLength(255)

                    ->default(null),
                TextInput::make('emergency_contact_phone')
                    ->tel()
                    ->maxLength(255)
                    ->default(null),
                TextInput::make('membership_id')
                    ->required()
                    ->maxLength(255),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('user.avatar')
                    ->label('User')
                    ->size(32)
                    ->circular()
                    ->defaultImageUrl(url('/images/default-user.png'))
                    ->extraImgAttributes(['class' => 'bg-gray-200 hover:bg-gray-600 overflow-visible']),


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
                TextColumn::make('starting_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('valid_from')
                    ->date()
                    ->sortable(),
                TextColumn::make('valid_until')
                ->label('Expiry Date')    
                ->date()
                    ->sortable()
                    ->color(fn($state): string =>
                    $state && Carbon::parse($state)->isPast() ? 'danger' : 'success'
                ),
                TextColumn::make('status')
                ->badge()
                ->color(fn(string $state): string => match ($state) {
                    'active' => 'success',
                    'inactive' => 'danger',
                    'expired' => 'gray',
                    default => 'primary',
                }),
                TextColumn::make('emergency_contact_name')
                    ->searchable(),
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
                //
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

}
