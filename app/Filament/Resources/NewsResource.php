<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsResource\Pages;
use App\Models\News;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class NewsResource extends Resource
{
    protected static ?string $model = News::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Resources';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([

                // ===== BARIS ATAS: info berita (kiri) + gambar (kanan) =====
                Section::make('Informasi Berita')
                    ->description('Judul, sumber, dan tautan berita.')
                    ->icon('heroicon-o-newspaper')
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        TextInput::make('news_title')
                            ->label('Judul Berita')
                            ->placeholder('Contoh: UMKM Jabar Mulai Gunakan Perizinan Online')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(Set $set, ?string $state) => $set('news_slug', Str::slug($state)))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('news_slug')
                            ->label('Slug (URL)')
                            ->placeholder('Terisi otomatis dari judul')
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('Terisi otomatis sesuai judul.')
                            ->readOnly()
                            ->dehydrated()
                            ->required()
                            ->maxLength(255),

                        TextInput::make('news_source')
                            ->label('Sumber Berita')
                            ->placeholder('Contoh: jabar.tribunnews.com, komunitas WhatsApp Pojok Wirausaha')
                            ->prefixIcon('heroicon-o-building-office')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('news_url')
                            ->label('Link Berita')
                            ->placeholder('jabar.tribunnews.com/berita/contoh')
                            ->prefix('https://')
                            ->helperText('Tulis tanpa https:// di depannya.')
                            ->required()
                            ->maxLength(255),
                    ]),

                Section::make('Gambar Berita')
                    ->icon('heroicon-o-photo')
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        FileUpload::make('news_image')
                            ->hiddenLabel()
                            ->image()
                            ->disk('public')
                            ->directory('img_news')
                            ->optimize('webp')
                            ->maxSize(2048)
                            ->downloadable()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->imagePreviewHeight('180')
                            ->helperText('Rasio 16:9 disarankan, maks. 2 MB.')
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, $get): string {
                                // judul dari input form, fallback 'news' bila kosong
                                $slug = Str::of('news_' . ($get('news_title') ?: 'news'))
                                    ->lower()
                                    ->slug('_');

                                // timestamp supaya unik
                                return $slug . '_' . time() . '.' . $file->getClientOriginalExtension();
                            })
                            ->required(),
                    ]),

                // ===== BARIS BAWAH: isi berita selebar penuh =====
                Section::make('Isi Berita')
                    ->description('Ringkasan atau isi berita yang akan ditampilkan.')
                    ->icon('heroicon-o-document-text')
                    ->columnSpanFull()
                    ->schema([
                        RichEditor::make('news_content')
                            ->hiddenLabel()
                            ->toolbarButtons([
                                'bold',
                                'bulletList',
                                'link',
                                'orderedList',
                                'underline',
                                'undo',
                            ])
                            ->extraInputAttributes(['style' => 'min-height: 24rem;'])
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('news_image')
                    ->label('IMAGE')
                    ->circular(),
                TextColumn::make('news_title')
                    ->label('TITLE')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                TextColumn::make('news_source')
                    ->label('SOURCE')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('news_url')
                    ->searchable()
                    ->label('URL')
                    ->limit(50)
                    // tambahkan https:// bila belum ada, supaya link bisa dibuka
                    ->url(fn($record) => Str::startsWith($record->news_url, ['http://', 'https://'])
                        ? $record->news_url
                        : 'https://' . $record->news_url)
                    ->openUrlInNewTab(),
                TextColumn::make('created_at')
                    ->label('LOG')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])->emptyStateActions([
                Action::make('create')
                    ->label('Create Sosial Media')
                    ->url(route('filament.management.resources.news.create'))
                    ->icon('heroicon-m-plus')
                    ->button(),
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
            'index' => Pages\ListNews::route('/'),
            'create' => Pages\CreateNews::route('/create'),
            'edit' => Pages\EditNews::route('/{record}/edit'),
        ];
    }
}
