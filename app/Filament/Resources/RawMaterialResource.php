<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RawMaterialResource\Pages;
use App\Filament\Resources\RawMaterialResource\RelationManagers;
use App\Models\RawMaterial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RawMaterialResource extends Resource
{
    protected static ?string $model = RawMaterial::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Stok Gudang';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Bahan Baku')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('unit')
                            ->label('Satuan')
                            ->required()
                            ->placeholder('gram, ml, pcs, dll')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('stock')
                            ->label('Stok Gudang')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->suffix(fn($get) => $get('unit')),

                        Forms\Components\TextInput::make('min_stock')
                            ->label('Stok Minimum')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->suffix(fn($get) => $get('unit')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Bahan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock')
                    ->label('Stok')
                    ->formatStateUsing(fn($state, $record) => number_format($state, 2) . ' ' . $record->unit)
                    ->sortable()
                    ->color(fn($record) => $record->isLowStock() ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('min_stock')
                    ->label('Stok Minimum')
                    ->formatStateUsing(fn($state, $record) => number_format($state, 2) . ' ' . $record->unit)
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_low_stock')
                    ->label('Status')
                    ->boolean()
                    ->getStateUsing(fn($record) => !$record->isLowStock())
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-exclamation-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('low_stock')
                    ->label('Stok Rendah')
                    ->query(fn($query) => $query->whereRaw('stock <= min_stock')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, RawMaterial $record) {
                        $productsUsingMaterial = $record->recipes()
                            ->with('product')
                            ->get()
                            ->pluck('product.name')
                            ->unique()
                            ->toArray();

                        if (!empty($productsUsingMaterial)) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Tidak dapat menghapus bahan baku')
                                ->body('Bahan baku "' . $record->name . '" masih digunakan di produk: ' . implode(', ', $productsUsingMaterial))
                                ->persistent()
                                ->send();

                            $action->cancel();
                        }
                    }),
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
            'index' => Pages\ListRawMaterials::route('/'),
            'create' => Pages\CreateRawMaterial::route('/create'),
            'edit' => Pages\EditRawMaterial::route('/{record}/edit'),
        ];
    }
}
