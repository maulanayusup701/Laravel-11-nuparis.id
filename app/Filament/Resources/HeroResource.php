<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HeroResource\Pages;
use App\Models\Hero;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class HeroResource extends Resource
{
    protected static ?string $model = Hero::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationGroup = 'Resources';
    protected static ?string $modelLabel = 'Hero';
    protected static ?string $pluralModelLabel = 'Hero';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([

                // ================= KOLOM KIRI =================
                Section::make('Informasi Hero')
                    ->description('Nama, slug, dan status tampil hero.')
                    ->icon('heroicon-o-identification')
                    ->columnSpan(['lg' => 2])
                    ->columns(2)
                    ->schema([
                        TextInput::make('hero_name')
                            ->label('Nama Hero')
                            ->prefixIcon('heroicon-m-pencil')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(Set $set, ?string $state) => $set('hero_slug', Str::slug($state)))
                            ->disabled()
                            ->required(),

                        TextInput::make('hero_slug')
                            ->label('Slug (URL)')
                            ->placeholder('Terisi otomatis dari nama')
                            ->prefixIcon('heroicon-o-link')
                            ->disabled()
                            ->required(),

                        Toggle::make('hero_status')
                            ->label('Tampilkan di Beranda')
                            ->helperText('Aktifkan agar hero tampil di halaman depan.')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                // ================= KOLOM KANAN (sidebar) =================
                Section::make('Gambar Hero')
                    ->description('Gambar utama yang tampil di halaman depan.')
                    ->icon('heroicon-o-photo')
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        FileUpload::make('hero_assets')
                            ->hiddenLabel()
                            ->disk('public')
                            ->directory('hero_assets')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->downloadable()
                            ->helperText('Rasio 16:9 disarankan.')
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, $get): string {
                                $title = 'hero_' . $get('hero_name') ?: 'hero';

                                $slug = Str::of($title)
                                    ->lower()
                                    ->replace(' ', '_')
                                    ->slug('_');

                                return $slug . '_' . time() . '.' . $file->getClientOriginalExtension();
                            })
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('hero_assets')
                    ->label('Gambar')
                    ->disk('public')
                    ->getStateUsing(function ($record) {
                        $path = is_array($record->hero_assets) ? ($record->hero_assets[0] ?? null) : $record->hero_assets;

                        return $path && Storage::disk('public')->exists($path) ? $path : null;
                    })
                    ->placeholder('-')
                    ->height(48)
                    ->width(80)
                    ->extraImgAttributes(['class' => 'object-cover rounded-md']),

                TextColumn::make('hero_name')
                    ->label('Nama Hero')
                    ->weight('medium')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('hero_slug')
                    ->label('Slug')
                    ->color('gray')
                    ->searchable()
                    ->toggleable(),

                IconColumn::make('hero_status')
                    ->label('Status')
                    ->boolean()
                    ->alignCenter(),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada hero')
            ->emptyStateIcon('heroicon-o-photo');
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
            'index' => Pages\ListHeroes::route('/'),
            'create' => Pages\CreateHero::route('/create'),
            'edit' => Pages\EditHero::route('/{record}/edit'),
        ];
    }
}
