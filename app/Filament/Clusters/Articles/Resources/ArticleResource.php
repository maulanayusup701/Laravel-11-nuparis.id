<?php

namespace App\Filament\Clusters\Articles\Resources;

use App\Filament\Clusters\Articles;
use App\Filament\Clusters\Articles\Resources\ArticleResource\Pages;
use App\Models\Article;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
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

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = Articles::class;
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Cluster hilang dari sidebar
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([

                // ===== BARIS ATAS: info utama (kiri) + gambar (kanan) =====
                Section::make('Informasi Artikel')
                    ->description('Judul, alamat URL, dan kategori artikel.')
                    ->icon('heroicon-o-pencil-square')
                    ->columnSpan(['lg' => 2])
                    ->schema([
                        TextInput::make('article_title')
                            ->label('Judul')
                            ->placeholder('Contoh: Panduan Mengurus NIB untuk Pemula')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(Set $set, ?string $state) => $set('article_slug', Str::slug($state)))
                            ->required()
                            ->maxLength(255),

                        TextInput::make('article_slug')
                            ->label('Slug (URL)')
                            ->placeholder('Terisi otomatis dari judul')
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('Terisi otomatis sesuai judul.')
                            ->readOnly()
                            ->dehydrated()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Select::make('article_category_uuid')
                            ->label('Kategori Artikel')
                            ->relationship(name: 'articleCategory', titleAttribute: 'article_category_name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('article_category_name')
                                    ->label('Nama Kategori')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn(Set $set, ?string $state) => $set('article_category_slug', Str::slug($state)))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('article_category_slug')
                                    ->label('Slug Kategori')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->helperText('Belum ada kategori? Klik tanda + untuk membuat baru.')
                            ->required(),
                    ]),

                Section::make('Gambar Utama')
                    ->icon('heroicon-o-photo')
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        FileUpload::make('article_image')
                            ->hiddenLabel()
                            ->image()
                            ->disk('public')
                            ->directory('img_articles')
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
                                $slug = Str::of('article_' . ($get('article_title') ?: 'article'))
                                    ->lower()
                                    ->slug('_');
                                return $slug . '_' . time() . '.' . $file->getClientOriginalExtension();
                            })
                            ->required(),
                    ]),

                // ===== BARIS BAWAH: isi artikel selebar penuh =====
                Section::make('Isi Artikel')
                    ->description('Tulis konten artikel. Gunakan heading (H2/H3) agar mudah dibaca.')
                    ->icon('heroicon-o-document-text')
                    ->columnSpanFull()
                    ->schema([
                        RichEditor::make('article_description')
                            ->hiddenLabel()
                            ->toolbarButtons([
                                'attachFiles',
                                'blockquote',
                                'bold',
                                'bulletList',
                                'codeBlock',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'underline',
                                'undo',
                            ])
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('img_articles')
                            ->fileAttachmentsVisibility('public')
                            ->extraInputAttributes(['style' => 'min-height: 32rem;'])
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('article_image')
                    ->label('IMAGE')
                    ->circular()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('article_title')
                    ->label('TITLE')
                    ->sortable()
                    ->searchable()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('articleCategory.article_category_name')
                    ->searchable()
                    ->sortable()
                    ->label('CATEGORY')
                    ->limit(50),
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
                    ->label('Create Article')
                    ->url(route('filament.management.articles.resources.articles.create'))
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
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
