<?php

namespace App\Filament\Resources\Members;


use Filament\Schemas\Schema;
use App\Filament\Resources\Members\Pages\ListMembers;
use App\Filament\Resources\Members\Pages\CreateMember;
use App\Filament\Resources\Members\Schemas\MemberForm as SchemasMemberForm;
use App\Filament\Resources\Members\Tables\MemberTable;
use App\Models\Member;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

use App\Filament\Traits\PaymentCalculationsTrait;

use App\Filament\Traits\CalcPayDateRanges;
use App\Filament\Resources\Members\Schemas\MemberForm;


class MemberResource extends Resource
{
    use PaymentCalculationsTrait;
    protected static ?string $model = Member::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    protected static string | \UnitEnum | null $navigationGroup = 'User Management';
    protected static ?string $navigationLabel = 'All Members';
    use CalcPayDateRanges;
    public static function form(Schema $schema): Schema
    {
        return MemberForm::configure($schema);
        
    }

    public static function getEloquentQuery(): Builder
    {
        // Add 'package' to the with() array
        return parent::getEloquentQuery()->with(['user', 'package']);
    }

    public static function table(Table $table): Table
    {
        return MemberTable::configure($table);

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
            'index' => ListMembers::route('/'),
            'create' => CreateMember::route('/create'),
            // 'edit' => Pages\EditMember::route('/{record}/edit'),
        ];
    }



}