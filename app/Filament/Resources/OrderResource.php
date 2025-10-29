<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Pesanan';

    protected static ?string $navigationGroup = 'Orders';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->label('No. Pesanan')
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('table_number')
                            ->label('No. Meja')
                            ->disabled(),

                        Forms\Components\Select::make('order_type')
                            ->label('Tipe Pesanan')
                            ->options([
                                'cashier' => 'Kasir',
                                'customer' => 'Customer',
                            ])
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Dikonfirmasi',
                                'preparing' => 'Diproses',
                                'ready' => 'Siap',
                                'completed' => 'Selesai',
                                'cancelled' => 'Dibatalkan',
                            ])
                            ->required(),

                        Forms\Components\Select::make('cashier_id')
                            ->label('Kasir')
                            ->relationship('cashier', 'name')
                            ->disabled(),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total')
                            ->disabled()
                            ->prefix('Rp'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Item Pesanan')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('product.name')
                                    ->label('Produk')
                                    ->disabled(),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Jumlah')
                                    ->disabled(),

                                Forms\Components\TextInput::make('price')
                                    ->label('Harga')
                                    ->prefix('Rp')
                                    ->disabled(),

                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->prefix('Rp')
                                    ->disabled(),
                            ])
                            ->columns(4)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('No. Pesanan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('table_number')
                    ->label('No. Meja')
                    ->searchable()
                    ->sortable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('order_type')
                    ->label('Tipe')
                    ->formatStateUsing(fn($state) => $state === 'cashier' ? 'Kasir' : 'Customer')
                    ->badge()
                    ->color(fn($state) => $state === 'cashier' ? 'info' : 'warning'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'pending' => 'Pending',
                            'confirmed' => 'Dikonfirmasi',
                            'preparing' => 'Diproses',
                            'ready' => 'Siap',
                            'completed' => 'Selesai',
                            'cancelled' => 'Dibatalkan',
                            default => $state,
                        };
                    })
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'pending' => 'warning',
                        'confirmed' => 'info',
                        'preparing' => 'primary',
                        'ready' => 'success',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('cashier.name')
                    ->label('Kasir')
                    ->default('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Dikonfirmasi',
                        'preparing' => 'Diproses',
                        'ready' => 'Siap',
                        'completed' => 'Selesai',
                        'cancelled' => 'Dibatalkan',
                    ]),
                Tables\Filters\SelectFilter::make('order_type')
                    ->label('Tipe Pesanan')
                    ->options([
                        'cashier' => 'Kasir',
                        'customer' => 'Customer',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
