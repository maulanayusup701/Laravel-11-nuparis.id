<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Users';
    protected static ?string $modelLabel = 'Users';
    protected static ?string $pluralModelLabel = 'Users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Pribadi')
                    ->description('Data identitas pengguna.')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('fullname')
                                ->label('Nama Lengkap')
                                ->required()
                                ->maxLength(25)
                                ->prefixIcon('heroicon-m-user')
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn(Set $set, ?string $state) => $set('name', Str::slug($state))),

                            TextInput::make('name')
                                ->label('Username')
                                ->required()
                                ->maxLength(25)
                                ->placeholder('Dibuat otomatis')
                                ->helperText('Terisi otomatis dari nama lengkap.')
                                ->prefixIcon('heroicon-m-at-symbol'),
                        ]),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-m-envelope'),
                    ]),

                Section::make('Akses & Keamanan')
                    ->description('Peran dan kredensial login.')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('roles')
                                ->label('Peran')
                                ->relationship('roles', 'name')
                                ->preload()
                                ->searchable()
                                ->required(),

                            TextInput::make('password')
                                ->label('Password')
                                ->password()
                                ->revealable()
                                ->maxLength(255)
                                ->required(fn(string $operation): bool => $operation === 'create')
                                ->dehydrated(fn(?string $state): bool => filled($state))
                                ->dehydrateStateUsing(fn(string $state): string => Hash::make($state))
                                ->helperText(fn(string $operation): ?string => $operation === 'edit'
                                    ? 'Kosongkan jika tidak ingin mengubah password.'
                                    : null),
                        ]),

                        DateTimePicker::make('email_verified_at')
                            ->label('Email Terverifikasi Pada')
                            ->native(false),
                    ]),

                Section::make('Kontak')
                    ->icon('heroicon-o-phone')
                    ->schema([
                        Grid::make(2)->schema([
                            Textarea::make('phone')
                                ->label('Telepon')
                                ->required()
                                ->rows(2),

                            Textarea::make('address')
                                ->label('Alamat')
                                ->required()
                                ->rows(2),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fullname')
                    ->label('Nama Lengkap')
                    ->weight('medium')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Username')
                    ->prefix('@')
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->icon('heroicon-m-envelope')
                    ->copyable()
                    ->copyMessage('Email disalin')
                    ->searchable(),

                TextColumn::make('roles.name')
                    ->label('Peran')
                    ->badge()
                    ->color('info')
                    ->separator(', '),

                IconColumn::make('email_verified_at')
                    ->label('Terverifikasi')
                    ->boolean()
                    ->getStateUsing(fn(User $record): bool => filled($record->email_verified_at))
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->actions([
                EditAction::make()->iconButton(),
                DeleteAction::make()->iconButton(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada pengguna')
            ->emptyStateIcon('heroicon-o-users');
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
