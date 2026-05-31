<?php

namespace App\Filament\Resources\Packages;

use Filament\Schemas\Schema;
use App\Filament\Resources\Packages\Pages\ListPackages;
use App\Filament\Resources\Packages\Pages\CreatePackage;
use App\Filament\Resources\Packages\Pages\ViewPackage;
use App\Filament\Resources\Packages\Schemas\PackageForm;
use App\Filament\Resources\Packages\Tables\PackageTable;
use App\Models\Package;

use Filament\Resources\Resource;
use Filament\Tables\Table;
use App\Filament\Traits\PaymentCalculationsTrait;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static string |\BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    use PaymentCalculationsTrait;
    
    public static function form(Schema $schema): Schema
    {
        return PackageForm::configure($schema);  
    }

    public static function table(Table $table): Table
    {
        return PackageTable::configure($table);
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
            'index' => ListPackages::route('/'),
            'create' => CreatePackage::route('/create'),
            'view' => ViewPackage::route('/{record}'),
            // 'edit' => Pages\EditPackage::route('/{record}/edit'),
        ];
    }
}