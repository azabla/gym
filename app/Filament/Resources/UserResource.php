<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\FormsComponent;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('User Information')
                ->description('Fill in the user details.')
                ->schema([
                TextInput::make('first_name')
                    ->label('First Name')
                    ->required()
                    ->reactive()
                    ->minLength(2)
                    ->maxLength(50)
                    ->rule('alpha') // Only letters
                    ->afterStateUpdated(fn ($state, callable $set, callable $get) =>
                        $set('full_name', $state . ' ' . $get('last_name'))
                    ),
                TextInput::make('last_name')
                    ->label('Last Name')
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set, callable $get) =>
                        $set('full_name', $get('first_name') . ' ' . $state)
                    ),  

                Hidden::make('name')->required(),
                ])->columns(2),
                Section::make('User Adress')
                ->description('Fill in the user details.')
                ->schema([
                    TextInput::make('phone')
                    ->tel()
                    ->maxLength(255)
                    ->default(null),
                    TextInput::make('address')
                    ->maxLength(255)
                    ->default(null),
                ])->columns(2),
                Section::make('Dates')
                ->description('Fill in the user details.')
                ->schema([
                DatePicker::make('dob'),
                Select::make('gender')
                     ->label('Gender') 
                     ->options([
                            'male' => 'Male',
                            'female' => 'Female',
                     ])
                    ->native(false)
                    ->required(),
                ])->columns(2),
                Section::make('Additional Information')
                ->description('Fill in the user details.')
                ->schema([ 
                TextInput::make('username')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->maxLength(255)
                    ->default(null),
                TextInput::make('password')
                    ->password()
                    ->maxLength(255)
                    ->default(null),
                TextInput::make('avatar')
                    ->maxLength(255)
                    ->default(null),
                ])->columns(2),
                 Section::make('User Role')
                ->description('Fill in the user details.')
                ->schema([
                    Select::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'cashier' => 'Cashier',
                        'member' => 'Member',
                    ])
                    ->required(),
                ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('role'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('username')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('dob')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('gender'),
                Tables\Columns\TextColumn::make('address')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('avatar')
                    ->searchable(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
