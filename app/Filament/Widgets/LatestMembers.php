<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\MemberResource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestMembers extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';
    public function table(Table $table): Table
    {
        return $table
            ->query(MemberResource::getEloquentQuery())
            ->defaultPaginationPageOption(5)
            ->defaultSort('created_at', 'desc')
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
            ]);
    }
}
