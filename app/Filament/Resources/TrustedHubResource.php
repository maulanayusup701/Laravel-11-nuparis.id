<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrustedHubResource\Pages;
use App\Models\TrustedHub;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Support\Str;

class TrustedHubResource extends Resource
{
    protected static ?string $model = TrustedHub::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Resources';
    protected static ?string $navigationLabel = 'Trusted Hub';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informasi Trusted Hub')
                    ->description('Tautan dan deskripsi singkat Trusted Hub.')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        TextInput::make('trustedhub_url')
                            ->label('URL')
                            ->placeholder('https://www.contoh.com')
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('Tulis lengkap dengan https://')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('trustedhub_description')
                            ->label('Deskripsi')
                            ->placeholder('Jelaskan secara singkat tentang Trusted Hub ini')
                            ->rows(5)
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('No')
                    ->rowIndex()
                    ->label('No'),
                TextColumn::make('trustedhub_url')
                    ->label('URL')
                    ->searchable()
                    ->limit(50)
                    // tambahkan https:// bila belum ada, supaya link bisa dibuka
                    ->url(fn($record) => Str::startsWith($record->trustedhub_url, ['http://', 'https://'])
                        ? $record->trustedhub_url
                        : 'https://' . $record->trustedhub_url)
                    ->openUrlInNewTab(),
                TextColumn::make('trustedhub_description')
                    ->label('Description')
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label('log')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => Pages\ListTrustedHubs::route('/'),
            'create' => Pages\CreateTrustedHub::route('/create'),
            'edit' => Pages\EditTrustedHub::route('/{record}/edit'),
        ];
    }
}
