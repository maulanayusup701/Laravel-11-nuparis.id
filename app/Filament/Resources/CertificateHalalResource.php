<?php

namespace App\Filament\Resources;

use App\Enums\HalalStatus;
use App\Exports\HalalExport;
use App\Filament\Resources\CertificateHalalResource\Pages;
use App\Models\Halal;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Infolists;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\HtmlString;
use Maatwebsite\Excel\Facades\Excel;

class CertificateHalalResource extends Resource
{
    protected static ?string $model = Halal::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-badge';
    protected static ?string $navigationLabel = 'Sertifikat Halal';
    protected static ?string $modelLabel = 'Sertifikat Halal';
    protected static ?string $pluralModelLabel = 'Sertifikat Halal';

    /**
     * Tahapan proses sertifikasi untuk stepper.
     * 'statuses' adalah status database yang berada di tahap tersebut.
     * Tahap tanpa status (Pemeriksaan Lapangan) hanya ditandai selesai
     * ketika status sudah melewatinya.
     */
    private const STEPS = [
        ['label' => 'Pengajuan', 'statuses' => ['pengajuan']],
        ['label' => 'Pemeriksaan Dokumen', 'statuses' => ['review', 'dokumen_belum_lengkap']],
        ['label' => 'Pemeriksaan Lapangan (LPH)', 'statuses' => []],
        ['label' => 'Penetapan Fatwa Halal', 'statuses' => ['diterima']],
        ['label' => 'Sertifikat Diterbitkan', 'statuses' => ['menunggu_sertifikat']],
    ];

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // ================= RINGKASAN STATUS =================
                Infolists\Components\Section::make('Status Pengajuan')
                    ->description('Posisi pengajuan saat ini.')
                    ->icon('heroicon-o-flag')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->label('Status Saat Ini')
                            ->badge()
                            ->weight('bold'),

                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Tanggal Pengajuan')
                            ->dateTime('d M Y H:i'),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Terakhir Diperbarui')
                            ->dateTime('d M Y H:i'),
                    ])
                    ->columns(3),

                // ================= RIWAYAT PER STEP =================
                Infolists\Components\Section::make('Riwayat Status')
                    ->description('Posisi pengajuan dari tahap awal hingga sekarang.')
                    ->icon('heroicon-o-clock')
                    ->headerActions([
                        InfolistAction::make('ubahStatus')
                            ->label('Ubah Status')
                            ->icon('heroicon-o-arrow-path')
                            ->color('warning')
                            ->modalHeading('Ubah Status Pengajuan')
                            ->modalDescription('Status baru akan tercatat di riwayat dan dapat dilihat pemohon.')
                            ->modalSubmitActionLabel('Simpan')
                            ->form(static::ubahStatusForm())
                            ->action(fn(array $data, Halal $record) => static::simpanStatus($data, $record)),
                    ])
                    ->schema([
                        Infolists\Components\TextEntry::make('riwayat_step')
                            ->hiddenLabel()
                            ->html()
                            ->state(fn(Halal $record) => static::renderStepper($record->status))
                            ->extraAttributes([
                                'style' => 'display:flex;justify-content:center;width:100%;',
                            ])
                            ->columnSpanFull(),
                    ]),

                // ================= DATA PELAKU =================
                Infolists\Components\Section::make('Data Pelaku Usaha')
                    ->description('Identitas pemilik dan kontak usaha.')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Infolists\Components\TextEntry::make('nama_pelaku')
                            ->label('Nama Pelaku'),

                        Infolists\Components\TextEntry::make('nama_brand')
                            ->label('Nama Brand'),

                        Infolists\Components\TextEntry::make('nik_ktp')
                            ->label('NIK KTP'),

                        Infolists\Components\TextEntry::make('nib')
                            ->label('NIB'),

                        Infolists\Components\TextEntry::make('npwp')
                            ->label('NPWP'),

                        Infolists\Components\TextEntry::make('no_whatsapp')
                            ->label('No. WhatsApp'),

                        Infolists\Components\TextEntry::make('email')
                            ->label('Email')
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('alamat')
                            ->label('Alamat')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // ================= DATA USAHA =================
                Infolists\Components\Section::make('Data Usaha')
                    ->description('Modal, tahun berdiri, dan omzet usaha.')
                    ->icon('heroicon-o-building-storefront')
                    ->schema([
                        Infolists\Components\TextEntry::make('modal_awal')
                            ->label('Modal Awal')
                            ->money('IDR'),

                        Infolists\Components\TextEntry::make('tahun_berdiri')
                            ->label('Tahun Berdiri'),

                        Infolists\Components\TextEntry::make('luas_usaha')
                            ->label('Luas Usaha'),

                        Infolists\Components\TextEntry::make('pendapatan_minggu')
                            ->label('Pendapatan per Minggu')
                            ->money('IDR'),
                    ])
                    ->columns(2),

                // ================= PRODUK =================
                Infolists\Components\Section::make('Produk')
                    ->description('Bahan dan cara pembuatan produk.')
                    ->icon('heroicon-o-beaker')
                    ->schema([
                        Infolists\Components\TextEntry::make('bahan')
                            ->label('Bahan')
                            ->html()
                            ->formatStateUsing(fn($state) => nl2br(e($state)))
                            ->columnSpanFull(),

                        Infolists\Components\TextEntry::make('cara_pembuatan')
                            ->label('Cara Pembuatan')
                            ->html()
                            ->formatStateUsing(fn($state) => nl2br(e($state)))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Field form untuk modal "Ubah Status".
     * Dipakai bersama oleh action di section dan di tabel.
     */
    private static function ubahStatusForm(): array
    {
        return [
            Forms\Components\Select::make('status')
                ->label('Status Baru')
                ->options(HalalStatus::options())
                ->default(fn(Halal $record) => $record->status instanceof HalalStatus
                    ? $record->status->value
                    : $record->status)
                ->required()
                ->live(),

            Forms\Components\Textarea::make('catatan')
                ->label('Catatan untuk Pemohon')
                ->placeholder('Contoh: Foto NIB belum terbaca, mohon unggah ulang.')
                ->rows(3)
                ->required(fn(Get $get): bool => in_array($get('status'), HalalStatus::requiresNote(), true))
                ->helperText('Wajib diisi untuk status Dokumen Belum Lengkap dan Ditolak.'),
        ];
    }

    /**
     * Menyimpan status baru dan mencatat riwayatnya.
     */
    private static function simpanStatus(array $data, Halal $record): void
    {
        $record->changeStatus(
            HalalStatus::from($data['status']),
            $data['catatan'] ?? null,
        );

        $record->refresh();

        Notification::make()
            ->title('Status berhasil diubah')
            ->body('Status sekarang: ' . HalalStatus::from($data['status'])->getLabel())
            ->success()
            ->send();
    }

    /**
     * Membuat stepper horizontal berdasarkan STEPS, dipusatkan.
     * Tahap sebelum posisi saat ini ditandai selesai (hijau),
     * posisi saat ini memakai warna primary Filament,
     * dan tahap setelahnya abu-abu. Status ditolak ditandai merah.
     */
    private static function renderStepper(mixed $status): HtmlString
    {
        $value = $status instanceof HalalStatus ? $status->value : (string) $status;

        $isRejected = $value === HalalStatus::Ditolak->value;

        $currentIndex = 0;
        foreach (self::STEPS as $i => $step) {
            if (in_array($value, $step['statuses'], true)) {
                $currentIndex = $i;
                break;
            }
        }

        // Status "diterima" dan "menunggu sertifikat" sudah melewati tahap lapangan
        // walaupun tahap itu tidak punya status sendiri.
        if ($value === HalalStatus::Diterima->value || $value === HalalStatus::MenungguSertifikat->value) {
            $currentIndex = $value === HalalStatus::MenungguSertifikat->value ? 4 : 3;
        }

        $primary = 'rgb(var(--primary-600))';
        $danger = '#dc2626';
        $done = '#16a34a';
        $pending = '#d1d5db';

        // Pembungkus rata tengah, lalu isi berupa inline-flex agar tetap di tengah
        $html = '<div style="width:100%;overflow-x:auto;text-align:center;">';
        $html .= '<div style="display:inline-flex;align-items:flex-start;padding:12px 4px;text-align:center;">';

        foreach (self::STEPS as $i => $step) {
            $isDone = $i < $currentIndex;
            $isActive = $i === $currentIndex;

            $activeColor = $isRejected ? $danger : $primary;

            $circleColor = $isDone ? $done : ($isActive ? $activeColor : $pending);
            $textColor = $isActive ? $activeColor : ($isDone ? '#374151' : '#9ca3af');
            $fontWeight = $isActive ? '700' : '500';
            $label = e($step['label']);

            if ($isActive && $isRejected) {
                $marker = '&#10005;';
            } elseif ($isDone) {
                $marker = '&#10003;';
            } else {
                $marker = (string) ($i + 1);
            }

            $html .= '<div style="width:150px;flex:0 0 150px;display:flex;flex-direction:column;align-items:center;text-align:center;position:relative;">';

            // Garis penghubung dari pusat tahap sebelumnya ke pusat tahap ini
            if ($i > 0) {
                $lineColor = $i <= $currentIndex ? $done : $pending;
                $html .= '<div style="position:absolute;top:13px;left:-75px;width:150px;height:2px;background:' . $lineColor . ';"></div>';
            }

            // Lingkaran nomor / centang / silang
            $html .= '<div style="width:28px;height:28px;border-radius:50%;background:' . $circleColor . ';color:#ffffff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;position:relative;z-index:1;">'
                . $marker
                . '</div>';

            // Label tahap
            $html .= '<div style="margin-top:8px;font-size:13px;font-weight:' . $fontWeight . ';color:' . $textColor . ';line-height:1.3;padding:0 6px;">'
                . $label
                . '</div>';

            // Penanda posisi saat ini
            if ($isActive) {
                $caption = $isRejected ? 'Ditolak' : 'Saat ini';
                $html .= '<div style="margin-top:4px;font-size:11px;color:' . $activeColor . ';">' . $caption . '</div>';
            }

            $html .= '</div>';
        }

        $html .= '</div></div>';

        return new HtmlString($html);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nama_brand')
                    ->label('Brand')
                    ->weight('medium')
                    ->description(fn(Halal $record): string => $record->nama_pelaku ?? '-')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nik_ktp')
                    ->label('NIK KTP')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('npwp')
                    ->label('NPWP')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('nib')
                    ->label('NIB')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(HalalStatus::options()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('ubahStatus')
                    ->label('Ubah Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->modalHeading('Ubah Status Pengajuan')
                    ->modalDescription('Status baru akan tercatat di riwayat dan dapat dilihat pemohon.')
                    ->modalSubmitActionLabel('Simpan')
                    ->form(static::ubahStatusForm())
                    ->action(fn(array $data, Halal $record) => static::simpanStatus($data, $record)),

                Tables\Actions\Action::make('deleteWithPassword')
                    ->label('Hapus')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Data')
                    ->modalDescription('Masukkan password admin untuk konfirmasi penghapusan data ini.')
                    ->modalSubmitActionLabel('Hapus')
                    ->form([
                        Forms\Components\TextInput::make('admin_password')
                            ->label('Password Admin')
                            ->password()
                            ->revealable()
                            ->required(),
                    ])
                    ->action(function (array $data, Halal $record) {
                        if (! Hash::check($data['admin_password'], Auth::user()->password)) {
                            Notification::make()
                                ->title('Password salah')
                                ->body('Data tidak jadi dihapus.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->delete();

                        Notification::make()
                            ->title('Data berhasil dihapus')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('exportExcelSelected')
                        ->label('Export Excel (Terpilih)')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('success')
                        ->action(function (Collection $records) {
                            return Excel::download(
                                new HalalExport($records),
                                'sertifikat-halal-terpilih-' . now()->format('Ymd-His') . '.xlsx'
                            );
                        })
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('deleteSelectedWithPassword')
                        ->label('Hapus Terpilih')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Data Terpilih')
                        ->modalDescription('Masukkan password admin untuk konfirmasi penghapusan data yang dipilih.')
                        ->modalSubmitActionLabel('Hapus')
                        ->form([
                            Forms\Components\TextInput::make('admin_password')
                                ->label('Password Admin')
                                ->password()
                                ->revealable()
                                ->required(),
                        ])
                        ->action(function (array $data, Collection $records) {
                            if (! Hash::check($data['admin_password'], Auth::user()->password)) {
                                Notification::make()
                                    ->title('Password salah')
                                    ->body('Data tidak jadi dihapus.')
                                    ->danger()
                                    ->send();

                                return;
                            }

                            $records->each->delete();

                            Notification::make()
                                ->title('Data terpilih berhasil dihapus')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('exportExcelByDate')
                    ->label('Export Excel (by Tanggal)')
                    ->icon('heroicon-o-calendar-days')
                    ->color('primary')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')
                            ->label('Dari Tanggal')
                            ->required(),

                        Forms\Components\DatePicker::make('date_to')
                            ->label('Sampai Tanggal')
                            ->required()
                            ->afterOrEqual('date_from'),
                    ])
                    ->action(function (array $data) {
                        $halals = Halal::query()
                            ->whereDate('created_at', '>=', $data['date_from'])
                            ->whereDate('created_at', '<=', $data['date_to'])
                            ->orderBy('created_at')
                            ->get();

                        return Excel::download(
                            new HalalExport($halals),
                            'sertifikat-halal-' . $data['date_from'] . '-sd-' . $data['date_to'] . '.xlsx'
                        );
                    }),
            ])
            ->emptyStateHeading('Belum ada pengajuan')
            ->emptyStateIcon('heroicon-o-document-magnifying-glass');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCertificateHalals::route('/'),
            'view'  => Pages\ViewCertificateHalal::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
