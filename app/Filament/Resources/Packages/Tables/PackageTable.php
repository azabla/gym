<?php

namespace App\Filament\Resources\Packages\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

use Filament\Tables\Table;
use Filament\Tables\Enums\RecordActionsPosition;

class PackageTable
{
    public static function configure(Table $table): Table
    {
        return $table
        ->columns([
            TextColumn::make('Roll No.')->label('Roll No.')->rowIndex(),
            ImageColumn::make('image')
                ->label('Profile')
                ->circular()
                ->extraImgAttributes([
                    'class' => 'transition-transform duration-300 hover:scale-[4] hover:z-50',
                ])
                ->defaultImageUrl(url('/images/default-user.png')),
            TextColumn::make('name')
                ->searchable()
                ->sortable()
                ->limit(30)
                ->wrap()
                ->label('Package Name')
                ->badge('primary'),
            TextColumn::make('price')
                ->money()
                ->label('Price')
                ->tooltip('Price in Brr')
                ->sortable(),
            TextColumn::make('duration_unit')
                ->label('Duration Unit')
                ->sortable(),
            TextColumn::make('status')
                ->label('Status')
                ->sortable()
                ->toggleable()
                ->badge()
                ->colors([
                    'success' => 'active',
                    'danger' => 'inactive',
                ]),
            TextColumn::make('deleted_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true)
                ->label('Deleted At'),
            TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true)
                ->label('Created At'),
            TextColumn::make('updated_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true)
                ->label('Updated At'),
        ])
        ->striped()
        ->deferloading()
        ->filters([
            //
        ])
        ->recordActions([
            ViewAction::make(),
            EditAction::make()
            ->modalWidth('4xl') 
                ->tooltip('Quick Edit Packages')
                ->slideOver() 
                ->modalHeading('Update Package Profile')
                ->modalDescription('Changes will be applied immediately to the package record.')
                ->modalSubmitActionLabel('Save Changes')
                ->icon('heroicon-m-pencil-square')
                ->color('warning'),
        ], position: RecordActionsPosition::BeforeColumns)
        ->toolbarActions([
            BulkActionGroup::make([
                DeleteBulkAction::make(),
            ]),
        ]);
    }
}