<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackageResource\Pages;
use App\Filament\Resources\PackageResource\RelationManagers;
use App\Models\Package;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Traits\PaymentCalculationsTrait;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    use PaymentCalculationsTrait;
    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Section::make('Basic Information')
                ->description('Fill in the name, duration and price.')
              ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('duration_unit')
                ->options([
                    'day' => 'Day',
                    'week' => 'Week',
                    'month' => 'Month',
                    'year' => 'Year',
                    ])
                    ->default('month')
                    ->label('Duration Unit')
                    ->placeholder('Select Duration Unit')
                    ->native(false)
                    ->required(),
               TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('Brr')
                    ->suffix('.00')
                    ->minValue(1)
                    ->maxValue(10000)
                    ->placeholder('Enter package price')
                    ])->columns(3),


               Section::make('Additional Information')
                        ->description('Fill in some details about the package.')
                        ->schema([
                            Grid::make(2)
                                ->schema([
                                    // Left column with 2 stacked fields
                                    Grid::make(1)
                                        ->schema([
                                            RichEditor::make('description')
                                                ->label('Package Description')
                                                ->disableToolbarButtons(['attachFiles'])
                                                ->placeholder('Write a detailed description of the package...')
                                                ->maxLength(1000),

                                                Repeater::make('features')
                                                ->label('Package Features')
                                                ->schema([
                                                    TextInput::make('feature')
                                                        ->label('Feature')
                                                        ->required(),
                                                ])
                                                ->default([])
                                                ->collapsible()
                                                ->addActionLabel('Add Feature')
                                                ->columns(1)
                                                ->nullable(),

                                            
                                        ])
                                        ->columnSpan(1), // ← Tell it to use one column

                                   Grid::make(1)
                                        ->schema([
                                            
                                            FileUpload::make('image')
                                                ->label('Package Image')
                                                ->image()
                                                ->avatar()
                                                ->imageEditor()
                                                ->imageEditorEmptyFillColor('#000000')
                                                ->imagePreviewHeight('250')
                                                ->maxSize(5120)
                                                ->uploadingMessage('Uploading package image...'),
                                               
                                            Select::make('status')
                                                ->options([
                                                    'active' => 'Active',
                                                    'inactive' => 'Inactive',
                                                ])
                                                ->default('active')
                                                ->required()
                                                ->label('Status')
                                                ->placeholder('Select Package Status')
                                                ->native(false),
                                                ])->columnSpan(1), // ← Tell it to use one column,
                                        ]),
                                        ])
                                    ->columns(1)
                                    ->collapsible()
                                    ->collapsed()
                                    ->compact(),
                    
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('Roll No.')->label('Roll No.')->rowIndex(),
                Tables\Columns\ImageColumn::make('image')
                    ->label('Profile')
                    ->circular()
                    ->extraImgAttributes([
                        'class' => 'transition-transform duration-300 hover:scale-[4] hover:z-50',
                    ])
                    ->defaultImageUrl(url('/images/default-user.png')),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->wrap()
                    ->label('Package Name')
                    ->badge('primary'),
                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->label('Price')
                    ->tooltip('Price in Brr')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_unit')
                    ->label('Duration Unit')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->toggleable()
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                    ]),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Deleted At'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Created At'),
                Tables\Columns\TextColumn::make('updated_at')
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
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListPackages::route('/'),
            'create' => Pages\CreatePackage::route('/create'),
            'view' => Pages\ViewPackage::route('/{record}'),
            'edit' => Pages\EditPackage::route('/{record}/edit'),
        ];
    }
}
