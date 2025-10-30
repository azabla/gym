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

class MemberResource extends Resource
{
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
                    ->extraImgAttributes(['class'=>'cursor-pointer transition-transform hover:scale-110']),

                TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('package_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('duration_value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('starting_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('valid_from')
                    ->date()
                    ->sortable(),
                TextColumn::make('valid_until')
                    ->date()
                    ->sortable(),
                TextColumn::make('status'),
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
}
