<?php

namespace App\Filament\Resources\Users\Tables;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Exception;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

use Illuminate\Support\Facades\Log;

use App\Models\Package;
use App\Models\User;

use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;

use Illuminate\Database\Eloquent\Model;

use Filament\Tables\Filters\SelectFilter;

use Carbon\Carbon;


class UserTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('Roll No.')->label('Roll No.')->rowIndex(),
                ImageColumn::make('avatar')
                    ->label('Profile')
                    ->circular()
                    ->disk('public')
                    ->extraImgAttributes([
                        'class' => 'transition-transform duration-300 hover:scale-[2] hover:z-50',
                    ])
                    ->defaultImageUrl(url('/images/default-user.png')),
                TextColumn::make('name')
                    ->label('Member Details')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => $record->email) // Puts email under the name
                    ->copyable() // Allows clicking to copy email
                    ->tooltip('Click to copy name'),
                TextColumn::make('roles')
                    ->label('Roles')
                    ->formatStateUsing(fn($state, $record) => $record->roles->pluck('name')->join(', '))
                    ->badge()
                    ->colors([
                        'danger' => 'super_admin',
                        'warning' => 'admin',
                        'info' => 'cashier',
                        'success' => 'member',
                    ]),
                TextColumn::make('username')
                    ->searchable(),
                TextColumn::make('dob')
                    ->label('Date of Birth')
                    ->date()
                    // ->tooltip(fn($record): string => $record->dob->diffForHumans())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('gender')
                    ->badge()
                    ->colors([
                        'info' => 'male',
                        'danger' => 'female',
                    ])
                    ->icon(fn($record) => match ($record->gender) {
                        'male' => 'heroicon-o-user',
                        'female' => 'heroicon-o-user-plus',
                        default => null,
                    }),
                TextColumn::make('address')
                    ->searchable()
                    ->limit(20)
                    ->tooltip(fn($record) => $record->address),
                TextColumn::make('phone')
                    ->label('Phone Number')
                    ->searchable()
                    ->icon('heroicon-o-phone')
                    ->badge()
                    ->copyable()
                    ->copyMessage('Phone number copied')
                    ->copyMessageDuration(1500)
                    ->tooltip('Click to copy Number'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->deferLoading()
            ->striped()
            ->filters([
                SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Roles')
                    ->placeholder('All Roles'),
                SelectFilter::make('gender')
                    ->options([
                        'male' => 'Male',
                        'female' => 'Female',
                    ])
                    ->placeholder('All Genders'),
                SelectFilter::make('address')
                    ->options(function () {
                        return User::query()
                            ->distinct()
                            ->pluck('address', 'address')
                            ->filter(fn($value) => !is_null($value) && $value !== '')
                            ->toArray();
                    })
                    ->searchable(),

            ])
            ->recordActions([
                EditAction::make()
                    ->modalWidth('5xl') // Makes the popup size
                    ->tooltip('Quick Edit Users')
                    ->slideOver()
                    ->modalHeading('Update User Profile')
                    ->modalDescription('Changes will be applied immediately to the User record.')
                    ->modalSubmitActionLabel('Save Changes')
                    ->icon('heroicon-m-pencil-square')
                    ->color('warning')
                    ->mountUsing(function (Schema $schema, Model $record) {  // Custom fill with nested member data
                        Log::debug('mountUsing called for modal edit');
                        $data = $record->toArray();
                        if ($record->member) {
                            Log::debug('Member data:', $record->member->toArray());
                            $data['member'] = $record->member->toArray();
                        }
                        $schema->fill($data);
                    })
                    ->using(function (Model $record, array $data): Model {  // Custom update with nested member and recalc
                        Log::debug('Submitted update data: ' . json_encode($data));

                        $record->update($data);  // Update main user
            
                        if (isset($data['member'])) {
                            $memberData = $data['member'];
                            $memberData['user_id'] = $record->id;

                            try {
                                // Recalculate dates
                                $package = Package::find($memberData['package_id'] ?? null);
                                if (!$package) {
                                    Log::warning('Package not found for ID: ' . ($memberData['package_id'] ?? 'null') . ' - Using default month unit');
                                }
                                $durationUnit = $package ? ($package->duration_unit ?? 'month') : 'month';
                                $startingDate = $memberData['starting_date'] ?? Carbon::now()->toDateString();
                                $duration = (int) ($memberData['duration_value'] ?? 1);

                                $from = Carbon::parse($startingDate);
                                $until = $from->copy()->add($duration, $durationUnit);

                                $memberData['valid_from'] = $from->toDateString();
                                $memberData['valid_until'] = $until->toDateString();

                                Log::debug('Final member data before update/create: ' . json_encode($memberData));

                                if ($record->member) {
                                    $record->member->update($memberData);
                                } else {
                                    $record->member()->create($memberData);
                                }
                            } catch (Exception $e) {
                                Log::error('Member update/create failed: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
                            }
                        } elseif ($record->member) {
                            try {
                                $record->member->delete();
                            } catch (Exception $e) {
                                Log::error('Member delete failed: ' . $e->getMessage());
                            }
                        }

                        return $record;
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}