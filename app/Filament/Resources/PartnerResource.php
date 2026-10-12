<?php

namespace App\Filament\Resources;

use App\Exports\PartnersExport;
use App\Filament\Resources\PartnerResource\Pages;
use App\Models\Info;
use App\Models\Partner;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Maatwebsite\Excel\Facades\Excel;

class PartnerResource extends Resource
{
    protected static ?string $model = Partner::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Resources';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(3)
            ->schema([

                // ================= KOLOM KIRI =================
                \Filament\Forms\Components\Grid::make(1)
                    ->columnSpan(['lg' => 2])
                    ->schema([

                        Section::make('Informasi Mitra')
                            ->description('Nama, slug, dan jenis kemitraan.')
                            ->icon('heroicon-o-building-office-2')
                            ->schema([
                                TextInput::make('partner_name')
                                    ->label('Nama Mitra')
                                    ->placeholder('Contoh: PT Maju Bersama')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn(Set $set, ?string $state) => $set('partner_slug', Str::slug($state)))
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('partner_slug')
                                    ->label('Slug (URL)')
                                    ->placeholder('Terisi otomatis dari nama')
                                    ->prefixIcon('heroicon-o-link')
                                    ->helperText('Terisi otomatis sesuai nama mitra.')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->required()
                                    ->maxLength(255),

                                ToggleButtons::make('partner_type')
                                    ->label('Jenis Mitra')
                                    ->multiple()
                                    ->inline()
                                    ->options([
                                        'Client'   => 'Client',
                                        'Supplier' => 'Supplier',
                                    ])
                                    ->icons([
                                        'Client'   => 'heroicon-o-user-group',
                                        'Supplier' => 'heroicon-o-truck',
                                    ])
                                    ->colors([
                                        'Client'   => 'primary',
                                        'Supplier' => 'danger',
                                    ])
                                    ->helperText('Pilih minimal satu. Bisa keduanya.')
                                    ->required(),
                            ]),

                        Section::make('Kontak')
                            ->description('Telepon, email, dan website mitra.')
                            ->icon('heroicon-o-phone')
                            ->columns(2)
                            ->schema([
                                TextInput::make('partner_phone')
                                    ->label('Telepon')
                                    ->tel()
                                    ->placeholder('08123456789')
                                    ->prefixIcon('heroicon-o-phone')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('partner_email')
                                    ->label('Email')
                                    ->email()
                                    ->placeholder('example@gmail.com')
                                    ->prefixIcon('heroicon-o-envelope')
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('partner_url')
                                    ->label('Website')
                                    ->url()
                                    ->placeholder('https://www.contoh.com')
                                    ->prefixIcon('heroicon-o-globe-alt')
                                    ->helperText('Opsional. Tulis lengkap dengan https://')
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                            ]),

                        Section::make('Dokumen Legal')
                            ->description('Unggah NIB dan NPWP mitra (opsional).')
                            ->icon('heroicon-o-document-check')
                            ->columns(2)
                            ->schema([
                                FileUpload::make('partner_NIB')
                                    ->label('NIB')
                                    ->disk('public')
                                    ->directory('NIB_partners')
                                    ->downloadable()
                                    ->dehydrated()
                                    ->maxSize(5120)
                                    ->helperText('Maks. 5 MB.')
                                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, $get): string {
                                        $slug = Str::of('partner_NIB_' . ($get('partner_name') ?: 'partner_NIB'))
                                            ->lower()
                                            ->slug('_');
                                        return $slug . '_' . time() . '.' . $file->getClientOriginalExtension();
                                    }),

                                FileUpload::make('partner_NPWP')
                                    ->label('NPWP')
                                    ->disk('public')
                                    ->directory('NPWP_partners')
                                    ->downloadable()
                                    ->dehydrated()
                                    ->maxSize(5120)
                                    ->helperText('Maks. 5 MB.')
                                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, $get): string {
                                        $slug = Str::of('partner_NPWP_' . ($get('partner_name') ?: 'partner_NPWP'))
                                            ->lower()
                                            ->slug('_');
                                        return $slug . '_' . time() . '.' . $file->getClientOriginalExtension();
                                    }),
                            ]),
                    ]),

                // ================= KOLOM KANAN (sidebar) =================
                \Filament\Forms\Components\Grid::make(1)
                    ->columnSpan(['lg' => 1])
                    ->schema([

                        Section::make('Status')
                            ->icon('heroicon-o-signal')
                            ->schema([
                                ToggleButtons::make('partner_status')
                                    ->label('Status Mitra')
                                    ->options([
                                        'Active'   => 'Active',
                                        'Inactive' => 'Inactive',
                                    ])
                                    ->colors([
                                        'Active'   => 'success',
                                        'Inactive' => 'danger',
                                    ])
                                    ->grouped()
                                    ->live()
                                    ->required(),

                                ToggleButtons::make('partner_footer_status')
                                    ->label('Tampil di Footer')
                                    ->options([
                                        'Active'   => 'Active',
                                        'Inactive' => 'Inactive',
                                    ])
                                    ->colors([
                                        'Active'   => 'success',
                                        'Inactive' => 'danger',
                                    ])
                                    ->grouped()
                                    ->helperText('Mitra harus Active. Maksimal 6 mitra tampil di footer.')
                                    ->required()
                                    ->rule(function ($record, Get $get) {
                                        return function ($_, $value, $fail) use ($record, $get) {
                                            if ($value !== 'Active') {
                                                return;
                                            }

                                            $partnerStatus = $get('partner_status') ?? $record?->partner_status;
                                            if ($partnerStatus !== 'Active') {
                                                $fail('Partner harus Active terlebih dahulu.');
                                                return;
                                            }

                                            $query = Partner::where('partner_footer_status', 'Active');
                                            if ($record) {
                                                $query->where('uuid', '!=', $record->uuid);
                                            }
                                            if ($query->count() >= 6) {
                                                $fail('Maksimal hanya 6 data yang boleh Active.');
                                            }
                                        };
                                    }),
                            ]),

                        Section::make('Logo / Gambar')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                FileUpload::make('partner_image')
                                    ->hiddenLabel()
                                    ->image()
                                    ->disk('public')
                                    ->directory('img_partners')
                                    ->optimize('webp')
                                    ->maxSize(2048)
                                    ->downloadable()
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        '16:9',
                                        '4:3',
                                        '1:1',
                                    ])
                                    ->imagePreviewHeight('160')
                                    ->helperText('Maks. 2 MB.')
                                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, $get): string {
                                        $slug = Str::of('partner_' . ($get('partner_name') ?: 'partner'))
                                            ->lower()
                                            ->slug('_');
                                        return $slug . '_' . time() . '.' . $file->getClientOriginalExtension();
                                    })
                                    ->required(),
                            ]),
                    ]),

                // ================= BARIS BAWAH: profil selebar penuh =================
                Section::make('Profil Mitra')
                    ->description('Deskripsi usaha dan alamat lengkap mitra.')
                    ->icon('heroicon-o-document-text')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        RichEditor::make('partner_description')
                            ->label('Deskripsi')
                            ->toolbarButtons([
                                'bold',
                                'bulletList',
                                'link',
                                'orderedList',
                                'underline',
                                'undo',
                            ])
                            ->extraInputAttributes(['style' => 'min-height: 14rem;'])
                            ->required(),

                        RichEditor::make('partner_address')
                            ->label('Alamat')
                            ->toolbarButtons([
                                'bold',
                                'bulletList',
                                'link',
                                'orderedList',
                                'underline',
                                'undo',
                            ])
                            ->extraInputAttributes(['style' => 'min-height: 14rem;'])
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Action::make('exportExcel')
                    ->label('Export Excel')
                    ->color('success')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function () {
                        $partners = Partner::orderBy('created_at', 'desc')->get();
                        return Excel::download(new PartnersExport($partners, 1), 'partners_export_' . date('Y-m-d_H-i-s') . '.xlsx');
                    }),
                Action::make('uploadPanduanMitra')
                    ->label(function () {
                        $info = Info::first();
                        return $info && $info->partner_guide ? 'Update Panduan Mitra' : 'Upload Panduan Mitra';
                    })
                    ->color('success')
                    ->icon('heroicon-o-document-arrow-up')
                    ->modalHeading(function () {
                        $info = Info::first();
                        return $info && $info->partner_guide ? 'Update Panduan Mitra' : 'Upload Panduan Mitra';
                    })
                    ->form(function () {
                        $info = Info::first();
                        $hasFile = $info && $info->partner_guide;

                        return [
                            FileUpload::make('partner_guide')
                                ->label($hasFile ? 'File Panduan Baru (PDF)' : 'File Panduan Mitra (PDF)')
                                ->required()
                                ->acceptedFileTypes(['application/pdf'])
                                ->maxSize(5120)
                                ->disk('public')
                                ->directory('panduan-mitra')
                                ->storeFiles()
                                ->preserveFilenames()
                                ->default($hasFile ? [$info->partner_guide] : [])
                                ->dehydrated()
                                ->getUploadedFileNameForStorageUsing(
                                    fn($file) => (string) str()->uuid() . '.' . $file->getClientOriginalExtension()
                                )
                                ->helperText('Format PDF. Maksimal 5MB')
                        ];
                    })
                    ->action(function (array $data) {
                        try {
                            $info = Info::first();

                            if (!$info) {
                                $info = Info::create([
                                    'uuid' => Str::uuid(),
                                    'address' => '-',
                                    'email' => 'admin@example.com',
                                    'instagram' => '-',
                                    'youtube' => '-',
                                    'logo' => 'default.png',
                                    'meta_domain' => 'example.com',
                                    'meta_title' => 'Default',
                                    'meta_desc' => 'Default',
                                    'meta_keywords' => 'default',
                                    'meta_image' => 'default.png',
                                ]);
                            }

                            $oldFile = $info->partner_guide;
                            if ($oldFile && Storage::disk('public')->exists($oldFile)) {
                                Storage::disk('public')->delete($oldFile);
                            }

                            $info->update(['partner_guide' => $data['partner_guide']]);

                            \Filament\Notifications\Notification::make()
                                ->title('✅ Berhasil!')
                                ->body('Panduan mitra telah ' . ($oldFile ? 'diupdate' : 'diupload'))
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('❌ Error!')
                                ->body('Gagal: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    })
                    ->extraModalFooterActions([
                        Action::make('hapusFile')
                            ->label('Hapus File')
                            ->color('danger')
                            ->icon('heroicon-o-trash')
                            ->requiresConfirmation()
                            ->modalHeading('Hapus File Panduan')
                            ->modalDescription('File akan dihapus permanen dari sistem. Anda tidak dapat mengembalikannya.')
                            ->action(function () {
                                try {
                                    $info = Info::first();
                                    if ($info && $info->partner_guide) {
                                        $filename = basename($info->partner_guide);
                                        if (Storage::disk('public')->exists($info->partner_guide)) {
                                            Storage::disk('public')->delete($info->partner_guide);
                                        }
                                        $info->update(['partner_guide' => null]);
                                        \Filament\Notifications\Notification::make()
                                            ->title('✅ Berhasil!')
                                            ->body('File ' . $filename . ' telah dihapus')
                                            ->success()
                                            ->send();
                                    }
                                } catch (\Exception $e) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('❌ Error!')
                                        ->body('Gagal menghapus: ' . $e->getMessage())
                                        ->danger()
                                        ->send();
                                }
                            })
                            ->hidden(fn() => !Info::first()?->partner_guide),
                    ]),
            ])
            ->columns([
                TextColumn::make('no')
                    ->label('NO')
                    ->state(function ($rowLoop): string {
                        return (string) $rowLoop->iteration;
                    })
                    ->sortable(false)
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->width('50px')
                    ->alignment('center'),

                ImageColumn::make('partner_image')
                    ->label('IMAGE')
                    ->circular()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('partner_name')
                    ->label('NAME')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('partner_phone')
                    ->label('PHONE')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('partner_email')
                    ->label('EMAIL')
                    ->searchable()
                    ->url(fn($record) => $record->partner_email ? "mailto:{$record->partner_email}" : null)
                    ->openUrlInNewTab()
                    ->icon('heroicon-m-envelope')
                    ->iconColor('info')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('partner_type')
                    ->label('TYPE')
                    ->badge()
                    ->colors(['primary' => 'Client', 'danger' => 'Supplier'])
                    ->formatStateUsing(fn($state) => is_array($state) ? implode(', ', $state) : $state)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                IconColumn::make('partner_status')
                    ->label('STATUS')
                    ->icon(fn(string $state): string => match ($state) {
                        'Active' => 'heroicon-o-check-circle',
                        'Inactive' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'Active' => 'success',
                        'Inactive' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                IconColumn::make('partner_footer_status')
                    ->label('SHOW FOOTER')
                    ->icon(fn(string $state): string => match ($state) {
                        'Active' => 'heroicon-o-check-circle',
                        'Inactive' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'Active' => 'success',
                        'Inactive' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('created_at')
                    ->label('LOG')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([])
            ->actions([
                Action::make('view_npwp')
                    ->label('NPWP')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn($record) => $record->partner_NPWP ? Storage::url($record->partner_NPWP) : null)
                    ->openUrlInNewTab()
                    ->disabled(fn($record) => empty($record->partner_NPWP)),

                Action::make('view_nib')
                    ->label('NIB')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->url(fn($record) => $record->partner_NIB ? Storage::url($record->partner_NIB) : null)
                    ->openUrlInNewTab()
                    ->disabled(fn($record) => empty($record->partner_NIB)),

                Action::make('visit')
                    ->icon('heroicon-o-link')
                    ->url(fn($record) => $record->partner_url)
                    ->openUrlInNewTab()
                    ->disabled(fn($record) => empty($record->partner_url)),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    Action::make('exportSelected')
                        ->label('Export Selected')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function ($livewire) {
                            $partners = $livewire->getSelectedTableRecords();

                            // Hitung nomor awal berdasarkan urutan data di database
                            $allPartners = Partner::orderBy('created_at', 'desc')->pluck('id')->toArray();
                            $selectedIds = $partners->pluck('id')->toArray();

                            $firstSelectedIndex = array_search($selectedIds[0], $allPartners);
                            $startNumber = $firstSelectedIndex + 1;

                            return Excel::download(new PartnersExport($partners, $startNumber), 'selected_partners_export_' . date('Y-m-d_H-i-s') . '.xlsx');
                        }),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Action::make('create')
                    ->label('Create Partner')
                    ->url(route('filament.management.resources.partners.create'))
                    ->icon('heroicon-m-plus')
                    ->button(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPartners::route('/'),
            'create' => Pages\CreatePartner::route('/create'),
            'edit' => Pages\EditPartner::route('/{record}/edit'),
        ];
    }
}
