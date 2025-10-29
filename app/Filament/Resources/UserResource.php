<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Manajemen User';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        return Auth::user()?->role === 'superadmin';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi User')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\Select::make('role')
                            ->label('Role')
                            ->options([
                                'superadmin' => 'Super Admin',
                                'admin' => 'Admin',
                                'cashier' => 'Kasir',
                            ])
                            ->required()
                            ->default('cashier')
                            ->helperText('Super Admin: Full access | Admin: Dashboard access | Kasir: Cashier only'),

                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->dehydrateStateUsing(fn($state) => Hash::make($state))
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $context): bool => $context === 'create')
                            ->maxLength(255)
                            ->helperText('Kosongkan jika tidak ingin mengubah password'),

                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Konfirmasi Password')
                            ->password()
                            ->dehydrated(false)
                            ->same('password')
                            ->requiredWith('password')
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('role')
                    ->label('Role')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'superadmin' => 'Super Admin',
                            'admin' => 'Admin',
                            'cashier' => 'Kasir',
                            default => $state,
                        };
                    })
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'superadmin' => 'danger',
                        'admin' => 'warning',
                        'cashier' => 'success',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Total Pesanan')
                    ->counts('orders')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('Email Verified')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Role')
                    ->options([
                        'superadmin' => 'Super Admin',
                        'admin' => 'Admin',
                        'cashier' => 'Kasir',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, User $record) {
                        if ($record->id === Auth::id()) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Tidak dapat menghapus akun sendiri')
                                ->send();

                            $action->cancel();
                        }

                        if ($record->role === 'superadmin' && User::where('role', 'superadmin')->count() <= 1) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Tidak dapat menghapus')
                                ->body('Minimal harus ada 1 Super Admin di sistem')
                                ->send();

                            $action->cancel();
                        }

                        $orderCount = $record->orders()->count();
                        if ($orderCount > 0) {
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('Perhatian: User memiliki ' . $orderCount . ' pesanan')
                                ->body('Selama memiliki pesanan tercatat, data user ini tidak dapat dihapus.')
                                ->persistent()
                                ->send();
                            $action->cancel();
                        }
                    })
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Tables\Actions\DeleteBulkAction $action, $records) {
                            if ($records->contains('id', Auth::id())) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Tidak dapat menghapus akun sendiri')
                                    ->send();

                                $action->cancel();
                            }

                            $superAdminCount = User::where('role', 'superadmin')->count();
                            $deletingSuperAdmins = $records->where('role', 'superadmin')->count();

                            if ($superAdminCount - $deletingSuperAdmins < 1) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Tidak dapat menghapus')
                                    ->body('Minimal harus ada 1 Super Admin di sistem')
                                    ->send();

                                $action->cancel();
                            }
                        }),
                ]),
            ])
            ->defaultSort(function ($query) {
                $query->orderByRaw("CASE WHEN role = 'superadmin' THEN 0 ELSE 1 END")
                    ->orderBy('role', 'asc');
            });
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

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
