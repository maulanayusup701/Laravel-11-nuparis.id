<?php

namespace App\Filament\Clusters\Activities\Resources;

use App\Filament\Clusters\Activities;
use App\Filament\Clusters\Activities\Resources\ActivityResource\Pages;
use App\Models\Activity;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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

class ActivityResource extends Resource
{
    protected static ?string $model = Activity::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'Resources';
    protected static ?string $cluster = Activities::class;

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
                Section::make('Informasi Kegiatan')
                    ->description('Judul, kategori, waktu, dan lokasi kegiatan.')
                    ->icon('heroicon-o-pencil-square')
                    ->columnSpan(['lg' => 2])
                    ->columns(2)
                    ->schema([
                        TextInput::make('activity_title')
                            ->label('Judul Kegiatan')
                            ->placeholder('Contoh: Sosialisasi Perizinan Usaha UMKM')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(Set $set, ?string $state) => $set('activity_slug', Str::slug($state)))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('activity_slug')
                            ->label('Slug (URL)')
                            ->placeholder('Terisi otomatis dari judul')
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('Terisi otomatis sesuai judul.')
                            ->readOnly()
                            ->dehydrated()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Select::make('activity_category_uuid')
                            ->label('Kategori Kegiatan')
                            ->relationship(name: 'activityCategory', titleAttribute: 'activity_category_name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('activity_category_name')
                                    ->label('Nama Kategori')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn(Set $set, ?string $state) => $set('activity_category_slug', Str::slug($state)))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('activity_category_slug')
                                    ->label('Slug Kategori')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->helperText('Belum ada kategori? Klik tanda + untuk membuat baru.')
                            ->required(),

                        DateTimePicker::make('activity_date')
                            ->label('Tanggal & Waktu')
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('d/m/Y H:i')
                            ->prefixIcon('heroicon-o-calendar-days')
                            ->required(),

                        Textarea::make('activity_location')
                            ->label('Lokasi Kegiatan')
                            ->placeholder('Jl. Raya No. 1, Kelurahan, Kecamatan, Kota, Provinsi')
                            ->rows(3)
                            ->maxLength(255)
                            ->required()
                            ->columnSpanFull(),
                    ]),

                Section::make('Gambar Utama')
                    ->icon('heroicon-o-photo')
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        FileUpload::make('activity_image')
                            ->hiddenLabel()
                            ->image()
                            ->disk('public')
                            ->directory('img_activities')
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
                                $slug = Str::of('activity_' . ($get('activity_title') ?: 'activity'))
                                    ->lower()
                                    ->slug('_');
                                return $slug . '_' . time() . '.' . $file->getClientOriginalExtension();
                            })
                            ->required(),
                    ]),

                // ===== BARIS BAWAH: deskripsi selebar penuh =====
                Section::make('Deskripsi Kegiatan')
                    ->description('Ceritakan jalannya kegiatan. Gunakan heading (H2/H3) agar mudah dibaca.')
                    ->icon('heroicon-o-document-text')
                    ->columnSpanFull()
                    ->schema([
                        RichEditor::make('activity_description')
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
                            ->fileAttachmentsDirectory('img_activities')
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
                ImageColumn::make('activity_image')
                    ->label('IMAGE')
                    ->circular()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('activity_title')
                    ->label('TITLE')
                    ->sortable()
                    ->searchable()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('activity_location')
                    ->label('LOCATION')
                    ->sortable()
                    ->searchable()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('activity_date')
                    ->dateTime()
                    ->sortable()
                    ->label('DATE')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('activityCategory.activity_category_name')
                    ->searchable()
                    ->sortable()
                    ->label('CATEGORY'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('LOG')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                Action::make('visit')
                    ->label('Visit')
                    ->icon('heroicon-o-link')
                    ->color('success')
                    ->url(fn($record) => config('app.url') . '/activity/' . $record->activity_slug)
                    ->openUrlInNewTab(),
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
                    ->label('Create Activity')
                    ->url(route('filament.management.activities.resources.activities.create'))
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
            'index' => Pages\ListActivities::route('/'),
            'create' => Pages\CreateActivity::route('/create'),
            'edit' => Pages\EditActivity::route('/{record}/edit'),
        ];
    }
}
