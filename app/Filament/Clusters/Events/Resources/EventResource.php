<?php

namespace App\Filament\Clusters\Events\Resources;

use \App\Filament\Clusters\Events\Resources\EventResource\RelationManagers\ParticipantsRelationManager;
use App\Filament\Clusters\Events;
use App\Filament\Clusters\Events\Resources\EventResource\Pages;
use App\Models\Event;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = Events::class;
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([

                    // ===================== STEP 1 =====================
                    Step::make('Informasi Dasar')
                        ->description('Judul, kategori, harga, kuota')
                        ->icon('heroicon-o-information-circle')
                        ->columns(2)
                        ->schema([
                            TextInput::make('event_title')
                                ->label('Judul Event')
                                ->placeholder('Contoh: Workshop Perizinan Usaha 2026')
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn(Set $set, ?string $state) => $set('event_slug', Str::slug($state)))
                                ->required()
                                ->maxLength(255)
                                ->columnSpanFull(),

                            TextInput::make('event_slug')
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

                            Select::make('event_category_uuid')
                                ->label('Kategori Event')
                                ->relationship(name: 'eventCategory', titleAttribute: 'event_category_name')
                                ->searchable()
                                ->preload()
                                ->createOptionForm([
                                    TextInput::make('event_category_name')
                                        ->label('Nama Kategori')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn(Set $set, ?string $state) => $set('event_category_slug', Str::slug($state)))
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('event_category_slug')
                                        ->label('Slug Kategori')
                                        ->required()
                                        ->maxLength(255),
                                ])
                                ->helperText('Belum ada kategori? Klik tanda + untuk membuat baru.')
                                ->required()
                                ->columnSpanFull(),

                            TextInput::make('event_price')
                                ->label('Harga Tiket')
                                ->prefix('Rp')
                                ->placeholder('0')
                                ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                ->stripCharacters('.')
                                ->numeric()
                                ->minValue(0)
                                ->helperText('Isi 0 jika event gratis.')
                                ->required(),

                            TextInput::make('event_quota')
                                ->label('Kuota Peserta')
                                ->placeholder('200')
                                ->suffix('orang')
                                ->numeric()
                                ->minValue(0)
                                ->required(),
                        ]),

                    // ===================== STEP 2 =====================
                    Step::make('Jadwal & Lokasi')
                        ->description('Waktu dan tipe event')
                        ->icon('heroicon-o-calendar-days')
                        ->columns(2)
                        ->schema([
                            DateTimePicker::make('event_date_start')
                                ->label('Mulai')
                                ->native(false)
                                ->seconds(false)
                                ->displayFormat('d/m/Y H:i')
                                ->format('Y-m-d H:i:s')
                                ->prefixIcon('heroicon-o-play')
                                ->live()
                                ->required(),

                            DateTimePicker::make('event_date_end')
                                ->label('Selesai')
                                ->native(false)
                                ->seconds(false)
                                ->displayFormat('d/m/Y H:i')
                                ->format('Y-m-d H:i:s')
                                ->prefixIcon('heroicon-o-stop')
                                ->after('event_date_start')
                                ->live()
                                ->afterStateUpdated(function ($state, $set) {
                                    if ($state && Carbon::parse($state)->isPast()) {
                                        $set('event_is_active', false);
                                    }
                                })
                                ->required(),

                            ToggleButtons::make('event_type')
                                ->label('Tipe Event')
                                ->multiple()
                                ->inline()
                                ->options([
                                    'Online'  => 'Online',
                                    'Offline' => 'Offline',
                                ])
                                ->icons([
                                    'Online'  => 'heroicon-o-video-camera',
                                    'Offline' => 'heroicon-o-map-pin',
                                ])
                                ->colors([
                                    'Online'  => 'info',
                                    'Offline' => 'success',
                                ])
                                ->helperText('Pilih minimal satu. Bisa keduanya (hybrid).')
                                ->live()
                                ->required()
                                ->columnSpanFull(),

                            Textarea::make('event_location')
                                ->label('Alamat Lokasi')
                                ->placeholder('Jl. Raya No. 1, Kelurahan, Kecamatan, Kota, Provinsi')
                                ->rows(3)
                                ->maxLength(255)
                                ->visible(fn(Get $get) => in_array('Offline', (array) $get('event_type')))
                                ->required(fn(Get $get) => in_array('Offline', (array) $get('event_type')))
                                ->columnSpanFull(),
                        ]),

                    // ===================== STEP 3 =====================
                    Step::make('Pendaftaran & Gambar')
                        ->description('Status, grup WhatsApp, poster')
                        ->icon('heroicon-o-ticket')
                        ->schema([
                            Toggle::make('event_is_active')
                                ->label('Buka Pendaftaran')
                                ->helperText('Otomatis nonaktif jika event sudah lewat.')
                                ->onColor('success')
                                ->offColor('danger')
                                ->live()
                                ->disabled(
                                    fn($get) => $get('event_date_end') &&
                                        Carbon::parse($get('event_date_end'))->isPast()
                                )
                                ->afterStateHydrated(function ($set, $get) {
                                    $endDate = $get('event_date_end');
                                    if ($endDate && Carbon::parse($endDate)->isPast()) {
                                        $set('event_is_active', false);
                                    }
                                }),

                            TextInput::make('event_link')
                                ->label('Link Grup WhatsApp')
                                ->placeholder('https://chat.whatsapp.com/KODE_UNDANGAN')
                                ->prefixIcon('heroicon-o-chat-bubble-left-right')
                                ->url()
                                ->regex('/^https:\/\/chat\.whatsapp\.com\/.+$/')
                                ->validationMessages([
                                    'regex' => 'Gunakan link undangan grup WhatsApp (https://chat.whatsapp.com/...).',
                                ])
                                ->helperText('Dikirim ke peserta setelah pendaftaran.')
                                ->maxLength(255)
                                ->required(),

                            FileUpload::make('event_image')
                                ->label('Gambar Event')
                                ->image()
                                ->disk('public')
                                ->directory('img_events')
                                ->optimize('webp')
                                ->maxSize(2048)
                                ->downloadable()
                                ->imageEditor()
                                ->imageEditorAspectRatios(['16:9', '4:3', '1:1'])
                                ->imagePreviewHeight('180')
                                ->helperText('Rasio 16:9 disarankan, maks. 2 MB.')
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, $get): string {
                                    $slug = Str::of($get('event_title') ?? 'event')->lower()->slug('_');
                                    return $slug . '_' . time() . '.' . $file->getClientOriginalExtension();
                                })
                                ->required(),
                        ]),

                    // ===================== STEP 4 =====================
                    Step::make('Deskripsi')
                        ->description('Isi lengkap event')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            RichEditor::make('event_description')
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
                                ->fileAttachmentsDirectory('img_events')
                                ->fileAttachmentsVisibility('public')
                                ->extraInputAttributes(['style' => 'min-height: 32rem;'])
                                ->required()
                                ->columnSpanFull(),
                        ]),
                ])
                    ->columnSpanFull()
                    ->persistStepInQueryString()
                    // saat edit, boleh lompat antar step tanpa harus berurutan
                    ->skippable(fn(string $operation) => $operation === 'edit')
                    ->submitAction(new HtmlString(Blade::render(<<<'BLADE'
                        <x-filament::button type="submit" size="sm" icon="heroicon-m-check">
                            Simpan Event
                        </x-filament::button>
                    BLADE))),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('event_image')
                    ->label('IMAGE')
                    ->circular()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->width(50)
                    ->height(50),
                TextColumn::make('event_title')
                    ->label('TITLE')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('event_price')
                    ->label('PRICE')
                    ->searchable()
                    ->sortable()
                    ->money('IDR')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('eventCategory.event_category_name')
                    ->label('CATEGORY')
                    ->sortable()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('event_quota')
                    ->label('QUOTA')
                    ->sortable()
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: false),

                // STATUS PENDAFTARAN
                IconColumn::make('event_is_active')
                    ->label('STATUS REGIST')
                    ->icon(fn(bool $state): string => match ($state) {
                        true => 'heroicon-o-check-circle',
                        false => 'heroicon-o-x-circle',
                    })
                    ->color(fn(bool $state): string => match ($state) {
                        true => 'success',
                        false => 'danger',
                    })
                    ->tooltip(fn(bool $state): string => match ($state) {
                        true => 'Pendaftaran Dibuka',
                        false => 'Pendaftaran Ditutup',
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                // STATUS EVENT (Berdasarkan tanggal)
                TextColumn::make('event_status')
                    ->label('STATUS EVENT')
                    ->badge()
                    ->getStateUsing(function ($record): string {
                        $now = Carbon::now();
                        $startDate = Carbon::parse($record->event_date_start);
                        $endDate = Carbon::parse($record->event_date_end);

                        if ($now->lt($startDate)) {
                            return 'Upcoming';
                        } elseif ($now->between($startDate, $endDate)) {
                            return 'Active';
                        } else {
                            return 'Inactive';
                        }
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'Active' => 'success',
                        'Inactive' => 'danger',
                        'Upcoming' => 'warning',
                        default => 'gray',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'Active' => 'heroicon-o-play-circle',
                        'Inactive' => 'heroicon-o-stop-circle',
                        'Upcoming' => 'heroicon-o-clock',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('event_type')
                    ->label('TYPE')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn($state): string => is_array($state) ? implode(', ', $state) : $state)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('event_date_start')
                    ->label('START DATE')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('event_date_end')
                    ->label('END DATE')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('created_at')
                    ->label('CREATED AT')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('UPDATED AT')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                //
            ])
            ->actions([
                EditAction::make()
                    ->label('Edit'),
                DeleteAction::make()
                    ->label('Delete'),
                Action::make('visit_event')
                    ->label('Visit')
                    ->icon('heroicon-o-globe-alt')
                    ->url(fn($record): string => route('event-detail', ['event_slug' => $record->event_slug]))
                    ->openUrlInNewTab()
                    ->color('success')
                    ->tooltip('View this event on the public website'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Action::make('create')
                    ->label('Create Event')
                    ->url(route('filament.management.events.resources.events.create'))
                    ->icon('heroicon-m-plus')
                    ->button(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ParticipantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'view' => Pages\ViewEvent::route('/{record}'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
