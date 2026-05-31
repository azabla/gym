<?php

namespace App\Filament\Resources\Packages\Schemas;



use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;

use Filament\Forms\Components\TextInput;


class PackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
        ->components([
            Grid::make(['default' => 1, 'xl' => 12])

            
            ->schema([
                Grid::make(1)
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
                                ])->columns(2)
                    ])->columnSpan(['default' => 12 , 'xl' => 8]),

                Grid::make(1)
                    ->schema([
                        Section::make('Available Addons / Extras')
                        ->description('Select which addons can be purchased with this package.')
                        ->schema([
                                Select::make('addons')
                                    ->label('Addons')
                                    ->relationship('addons', 'name')
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->columnSpanFull()
                                    ->helperText('Hold Ctrl/Cmd to select multiple addons'),
                            ]),
                    ])->columnSpan(['default' => 12 , 'xl' => 4])
                        ]),

          
            

            Section::make('Additional Information')
                ->description('Fill in some details about the package.')
                ->schema([
                        Grid::make(3)
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
                                        ->columnSpan(2), // ← Tell it to use one column

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
                                 ])->columnSpan(1),
                            ])
                            ->columns(1)
                            ->collapsible()
                            ->collapsed()
                            ->compact(),
                    
            ])->columns(1);
    }
}