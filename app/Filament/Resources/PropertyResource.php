<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropertyResource\Pages;
use App\Models\Property;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
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

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Resources';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([

                // ================= KOLOM KIRI =================
                Grid::make(1)
                    ->columnSpan(['lg' => 2])
                    ->schema([

                        Section::make('Informasi Properti')
                            ->description('Nama, jenis, harga, dan batas waktu.')
                            ->icon('heroicon-o-home-modern')
                            ->columns(2)
                            ->schema([
                                TextInput::make('property_name')
                                    ->label('Nama Properti')
                                    ->placeholder('Contoh: Rumah Minimalis Cluster Asri')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn(Set $set, ?string $state) => $set('property_slug', Str::slug($state)))
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                TextInput::make('property_slug')
                                    ->label('Slug (URL)')
                                    ->placeholder('Terisi otomatis dari nama')
                                    ->prefixIcon('heroicon-o-link')
                                    ->helperText('Terisi otomatis sesuai nama properti.')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                TextInput::make('property_type')
                                    ->label('Jenis Properti')
                                    ->placeholder('Contoh: Rumah, Ruko, Tanah')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('property_price')
                                    ->label('Harga')
                                    ->prefix('Rp')
                                    ->placeholder('0')
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                    ->stripCharacters('.')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required(),

                                DateTimePicker::make('property_date_end')
                                    ->label('Tanggal Berakhir')
                                    ->native(false)
                                    ->seconds(false)
                                    ->displayFormat('d/m/Y H:i')
                                    ->prefixIcon('heroicon-o-calendar-days')
                                    ->required()
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Spesifikasi')
                            ->description('Luas, fasilitas, dan legalitas.')
                            ->icon('heroicon-o-square-3-stack-3d')
                            ->columns(2)
                            ->schema([
                                TextInput::make('property_land_area')
                                    ->label('Luas Tanah')
                                    ->placeholder('120')
                                    ->suffix('m²')
                                    ->required(),

                                TextInput::make('property_building_area')
                                    ->label('Luas Bangunan')
                                    ->placeholder('90')
                                    ->suffix('m²')
                                    ->required(),

                                TextInput::make('property_fasilities')
                                    ->label('Fasilitas')
                                    ->placeholder('Contoh: Carport, taman, 3 kamar tidur'),

                                TextInput::make('property_certificate')
                                    ->label('Sertifikat')
                                    ->placeholder('Contoh: SHM, HGB'),
                            ]),

                        Section::make('Lokasi')
                            ->description('Alamat dan titik koordinat peta.')
                            ->icon('heroicon-o-map-pin')
                            ->columns(2)
                            ->schema([
                                Textarea::make('property_address')
                                    ->label('Alamat Lengkap')
                                    ->placeholder('Jl. Raya No. 1, Kelurahan, Kecamatan, Kota, Provinsi')
                                    ->rows(3)
                                    ->required()
                                    ->columnSpanFull(),

                                TextInput::make('property_latitude')
                                    ->label('Latitude')
                                    ->placeholder('-6.200000')
                                    ->rules(['nullable', 'numeric', 'between:-90,90'])
                                    ->helperText('Opsional. Salin dari Google Maps.'),

                                TextInput::make('property_longitude')
                                    ->label('Longitude')
                                    ->placeholder('106.816666')
                                    ->rules(['nullable', 'numeric', 'between:-180,180'])
                                    ->helperText('Opsional. Salin dari Google Maps.'),
                            ]),
                    ]),

                // ================= KOLOM KANAN (sidebar) =================
                Grid::make(1)
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        Section::make('Gambar / Video')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                FileUpload::make('property_image')
                                    ->hiddenLabel()
                                    ->acceptedFileTypes(['image/*', 'video/*'])
                                    ->disk('public')
                                    ->directory('img_properties')
                                    ->optimize('webp')
                                    ->multiple()
                                    ->maxFiles(10)
                                    ->maxSize(102400)
                                    ->downloadable()
                                    ->previewable(true)
                                    ->reorderable()
                                    ->appendFiles()
                                    ->helperText('Maks. 10 file (gambar/video), 100 MB per file. Urutan pertama jadi thumbnail, jadi gunakan gambar.')
                                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, $get): string {
                                        $slug = Str::of('property_' . ($get('property_name') ?: 'property'))
                                            ->lower()
                                            ->slug('_');

                                        return $slug . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                                    })
                                    ->required(),
                            ]),
                    ]),

                // ================= BARIS BAWAH: deskripsi selebar penuh =================
                Section::make('Deskripsi Properti')
                    ->description('Jelaskan kondisi, keunggulan, dan lingkungan sekitar properti.')
                    ->icon('heroicon-o-document-text')
                    ->columnSpanFull()
                    ->schema([
                        RichEditor::make('property_description')
                            ->hiddenLabel()
                            ->toolbarButtons([
                                'bold',
                                'bulletList',
                                'link',
                                'orderedList',
                                'underline',
                                'undo',
                            ])
                            ->extraInputAttributes(['style' => 'min-height: 20rem;'])
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('property_image')
                    ->label('Image')
                    ->getStateUsing(fn($record) => is_array($record->property_image) ? $record->property_image[0] : null)
                    ->size(60)
                    ->circular(),
                TextColumn::make('property_name')
                    ->sortable()
                    ->searchable()
                    ->limit(30),
                TextColumn::make('property_type')
                    ->searchable(),
                TextColumn::make('property_price')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Log')
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
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperty::route('/create'),
            'edit' => Pages\EditProperty::route('/{record}/edit'),
        ];
    }
}
