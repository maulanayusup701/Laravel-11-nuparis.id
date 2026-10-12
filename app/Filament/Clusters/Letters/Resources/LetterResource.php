<?php

namespace App\Filament\Clusters\Letters\Resources;

use App\Filament\Clusters\Letters;
use App\Filament\Clusters\Letters\Resources\LetterResource\Pages;
use App\Models\Letter;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class LetterResource extends Resource
{
    protected static ?string $model = Letter::class;

    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = Letters::class;

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([

                // ===== BARIS ATAS: info surat (kiri) + file (kanan) =====
                Section::make('Informasi Surat')
                    ->description('Nama, kategori, dan harga surat.')
                    ->icon('heroicon-o-envelope')
                    ->columnSpan(['lg' => 2])
                    ->columns(2)
                    ->schema([
                        TextInput::make('letter_name')
                            ->label('Nama Surat')
                            ->placeholder('Contoh: Surat Kuasa Pengurusan NIB')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(Set $set, ?string $state) => $set('letter_slug', Str::slug($state)))
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('letter_slug')
                            ->label('Slug (URL)')
                            ->placeholder('Terisi otomatis dari nama')
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('Terisi otomatis sesuai nama surat.')
                            ->readOnly()
                            ->dehydrated()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Select::make('letter_category_uuid')
                            ->label('Kategori Surat')
                            ->relationship(name: 'letterCategory', titleAttribute: 'letter_category_name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('letter_category_name')
                                    ->label('Nama Kategori')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn(Set $set, ?string $state) => $set('letter_category_slug', Str::slug($state)))
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('letter_category_slug')
                                    ->label('Slug Kategori')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->helperText('Belum ada kategori? Klik tanda + untuk membuat baru.')
                            ->required(),

                        TextInput::make('letter_price')
                            ->label('Harga')
                            ->prefix('Rp')
                            ->placeholder('0')
                            ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                            ->stripCharacters('.')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Isi 0 jika surat gratis.')
                            ->required(),
                    ]),

                Section::make('File Surat')
                    ->icon('heroicon-o-document-arrow-up')
                    ->columnSpan(['lg' => 1])
                    ->schema([
                        FileUpload::make('letter_file_path')
                            ->hiddenLabel()
                            ->disk('public')
                            ->directory('letters')
                            ->downloadable()
                            ->maxSize(10240)
                            ->helperText('File DOC/DOCX/XLSM, maks. 10 MB.')
                            ->getUploadedFileNameForStorageUsing(function ($file, $get) {
                                $name = Str::slug($get('letter_slug') ?: 'document');
                                $extension = $file->getClientOriginalExtension();
                                return "{$name}-" . time() . ".{$extension}";
                            })
                            ->required(),
                    ]),

                // ===== BARIS BAWAH: deskripsi selebar penuh =====
                Section::make('Deskripsi Surat')
                    ->description('Jelaskan kegunaan surat dan hal yang perlu diperhatikan pembeli.')
                    ->icon('heroicon-o-document-text')
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('letter_desc')
                            ->hiddenLabel()
                            ->rows(6)
                            ->required()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('letter_name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('letterCategory.letter_category_name')
                    ->label('Category')
                    ->searchable(),
                TextColumn::make('letter_price')
                    ->label('Price')
                    ->sortable()
                    ->money('idr', true),
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
                EditAction::make(),
                DeleteAction::make(),
                Action::make('visit_letter')
                    ->label('Visit')
                    ->icon('heroicon-o-globe-alt')
                    ->color('success')
                    ->tooltip('Lihat di website SCRIBD')
                    ->url(fn($record): string => 'https://scribd.nuparis.id/detail.html?slug=' . urlencode($record->letter_slug))
                    ->openUrlInNewTab(),
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
            'index' => Pages\ListLetters::route('/'),
            'create' => Pages\CreateLetter::route('/create'),
            'edit' => Pages\EditLetter::route('/{record}/edit'),
        ];
    }
}
