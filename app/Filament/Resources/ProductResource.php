<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Repeater;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Produk';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Produk')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Produk')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('price')
                            ->label('Harga')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0),

                        Forms\Components\Toggle::make('is_available')
                            ->label('Tersedia')
                            ->default(true),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Resep / Bahan Baku')
                    ->schema([
                        Repeater::make('recipes')
                            ->label('Bahan Baku')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('raw_material_id')
                                    ->label('Bahan Baku')
                                    ->relationship('rawMaterial', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $material = \App\Models\RawMaterial::find($state);
                                            if ($material) {
                                                $set('unit_display', $material->unit);
                                            }
                                        }
                                    }),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Jumlah')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->suffix(function (Forms\Get $get) {
                                        $materialId = $get('raw_material_id');
                                        if ($materialId) {
                                            $material = \App\Models\RawMaterial::find($materialId);
                                            return $material ? $material->unit : '';
                                        }
                                        return '';
                                    }),

                                Forms\Components\Hidden::make('unit_display'),
                            ])
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Bahan')
                            ->collapsible()
                            ->itemLabel(
                                fn(array $state): ?string =>
                                $state['raw_material_id']
                                    ? \App\Models\RawMaterial::find($state['raw_material_id'])?->name
                                    : null
                            ),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('available_stock')
                    ->label('Stok Tersedia')
                    ->getStateUsing(fn($record) => $record->available_stock)
                    ->color(fn($state) => $state > 0 ? 'success' : 'danger')
                    ->badge(),

                Tables\Columns\IconColumn::make('is_available')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('recipes_count')
                    ->label('Bahan Baku')
                    ->counts('recipes')
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_available')
                    ->label('Tersedia'),
                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Stok Habis')
                    ->query(function ($query) {
                        return $query->whereHas('recipes', function ($q) {
                            $q->whereHas('rawMaterial', function ($q2) {
                                $q2->whereRaw('raw_materials.stock < recipes.quantity');
                            });
                        });
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
