<?php

namespace App\Filament\Clusters\Ticket\Resources;

use \App\Models\TicketStatus;
use \Illuminate\Support\Facades\Auth;
use \Illuminate\Support\Facades\Hash;
use App\Exports\TicketsExport;
use App\Filament\Clusters\Ticket;
use App\Filament\Clusters\Ticket\Resources\TicketResource\Pages;
use App\Models\Ticket as TicketModel;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\View;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Maatwebsite\Excel\Facades\Excel;

class TicketResource extends Resource
{
    protected static ?string $model = TicketModel::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $cluster = Ticket::class;
    protected static ?int $navigationSort = 99;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    /**
     * Tombol Simpan & Batal disembunyikan jika tiket tersimpan berstatus "close"
     * DAN status di form belum diubah. Begitu status diganti (mis. ke open/pending),
     * tombol muncul lagi.
     */
    protected static function hideModalButtons($record, $livewire): bool
    {
        if ($record?->ticketStatus?->ticket_status_name !== 'close') {
            return false;
        }

        $selectedUuid = $livewire->mountedTableActionsData[0]['ticket_status_uuid']
            ?? $record->ticket_status_uuid;

        return TicketStatus::find($selectedUuid)?->ticket_status_name === 'close';
    }

    /**
     * Nama status yang sedang dipilih di form.
     */
    protected static function selectedStatusName(callable $get): ?string
    {
        return TicketStatus::find($get('ticket_status_uuid'))?->ticket_status_name;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        TextInput::make('ticket_code')
                            ->disabled()
                            ->required(),
                        TextInput::make('ticket_whatsapp')
                            ->disabled()
                            ->required(),
                        TextInput::make('ticket_email')
                            ->disabled()
                            ->required(),
                    ])->columns(3)->columnSpanFull(),
                Group::make()
                    ->schema([
                        TextInput::make('ticket_title')
                            ->disabled()
                            ->required(),
                        TextInput::make('ticket_name_client')
                            ->disabled()
                            ->label('Nama Client/Perusahaan')
                            ->required(),
                    ])->columns(2)->columnSpanFull(),
                Textarea::make('ticket_content')
                    ->disabled()
                    ->rows(18)
                    ->required()
                    ->columnSpanFull(),
                FileUpload::make('ticket_document_support')
                    ->label('View Dokumen Pendukung Client')
                    ->disk('public')
                    ->directory('ticket/initial-report_client')
                    ->openable()
                    ->downloadable()
                    ->previewable()
                    ->preserveFilenames()
                    ->disabled()
                    ->dehydrated(false)
                    ->columnSpanFull(),
                Select::make('consultant_specialization_uuid')
                    ->label('Spesialisasi')
                    ->relationship('consultantSpecialization', 'consultant_specialization_name')
                    ->required()
                    ->preload()
                    ->disabled(),
                Select::make('ticket_status_uuid')
                    ->label('Status')
                    ->relationship('ticketStatus', 'ticket_status_name')
                    ->live()
                    ->preload()
                    ->required(),

                // Riwayat tetap tampil untuk status "open" DAN "close"
                View::make('front-end.layouts.components.progress-note')
                    ->label('Riwayat Progress')
                    ->viewData(fn($livewire) => [
                        // Tombol edit/hapus progress terbaru: hanya saat tiket tidak terkunci
                        'canManageProgress' => method_exists($livewire, 'isLockedClosed')
                            && !$livewire->isLockedClosed(),
                    ])
                    ->columnSpanFull()
                    ->hidden(fn(callable $get) => !in_array(static::selectedStatusName($get), ['open', 'close'])),

                // Form tambah progress: hanya saat "open" dan tiket belum selesai
                Group::make([
                    RichEditor::make('progress')
                        ->label('Progress')
                        ->placeholder('Tulis progress terbaru...')
                        ->fileAttachmentsDirectory(function ($get, $record) {
                            if ($record && $record->ticket_code) {
                                return 'tickets/' . $record->ticket_code;
                            }
                            $ticketCode = $get('ticket_code');
                            if ($ticketCode) {
                                return 'tickets/' . $ticketCode;
                            }
                            return 'tickets/temp_' . auth()->id();
                        })
                        ->fileAttachmentsDisk('public')
                        ->fileAttachmentsVisibility('public')
                        ->columnSpanFull(),
                    FileUpload::make('progress_files')
                        ->label('Upload File (Opsional)')
                        ->disk('public')
                        ->directory(function ($get) {
                            $ticketCode = $get('ticket_code');
                            return 'tickets/' . $ticketCode;
                        })
                        ->multiple()
                        ->nullable()
                        ->downloadable()
                        ->openable()
                        ->preserveFilenames(),
                ])
                    ->columnSpanFull()
                    ->columns(1)
                    ->hidden(fn(callable $get) => static::selectedStatusName($get) !== 'open'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ticket_code')
                    ->label('Code')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Kode tiket disalin'),
                TextColumn::make('ticket_title')
                    ->label('Title')
                    ->sortable()
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('ticket_name_client')
                    ->label('Client')
                    ->sortable()
                    ->limit(50)
                    ->searchable(),
                TextColumn::make('consultantSpecialization.consultant_specialization_name')
                    ->label('Spesialisasi')
                    ->sortable()
                    ->searchable()
                    ->limit(50),
                TextColumn::make('ticketStatus.ticket_status_name')
                    ->label('Status')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'open' => 'success',
                        'close' => 'danger',
                        default => 'gray',
                    })
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                \Filament\Tables\Filters\Filter::make('kategori_status')
                    ->label('Filter Status')
                    ->form([
                        \Filament\Forms\Components\CheckboxList::make('status')
                            ->label('Pilih Status')
                            ->options([
                                'open'    => 'Open',
                                'pending' => 'Pending',
                                'close'   => 'Close',
                            ])
                            ->live(),
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['status'])) {
                            $statusUuids = TicketStatus::whereIn('ticket_status_name', $data['status'])
                                ->pluck('uuid')
                                ->toArray();
                            $query->whereIn('ticket_status_uuid', $statusUuids);
                        }
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (!empty($data['status'])) {
                            $indicators['status'] = 'Status: ' . implode(', ', $data['status']);
                        }
                        return $indicators;
                    }),
                SelectFilter::make('consultant_specialization_uuid')
                    ->label('Filter Spesialisasi')
                    ->relationship('consultantSpecialization', 'consultant_specialization_name')
                    ->placeholder('Semua Spesialisasi')
                    ->preload()
                    ->searchable(),
            ])
            ->actions([
                EditAction::make()
                    ->modalFooterActions(fn($action, $record, $livewire): array =>
                    static::hideModalButtons($record, $livewire)
                        ? []
                        : [$action->getModalSubmitAction(), $action->getModalCancelAction()])
                    ->modalWidth('7xl')
                    ->slideOver(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Hapus Data')
                    ->modalDescription('Masukkan password Anda untuk mengkonfirmasi penghapusan data ini.')
                    ->modalSubmitActionLabel('Ya, Hapus')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->required()
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        if (!Hash::check($value, Auth::user()->password)) {
                                            $fail('Password yang Anda masukkan salah.');
                                        }
                                    };
                                },
                            ]),
                    ])
                    ->action(function (array $data, $record) {
                        $record->delete();
                    }),
            ])
            ->headerActions([
                Action::make('exportExcel')
                    ->label('Export List Ticket')
                    ->color('success')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function ($livewire) {
                        $query = $livewire->getFilteredTableQuery()
                            ->with(['ticketStatus', 'consultantSpecialization']);

                        if ($query->count() == 0) {
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('Tidak ada data untuk diexport')
                                ->send();
                            return;
                        }

                        $filters = $livewire->tableFilters;
                        $filterText = 'Semua Data';

                        if (!empty($filters['kategori_status']['status'])) {
                            $filterText = 'Status: ' . implode(' + ', $filters['kategori_status']['status']);
                        }

                        $fileName = 'nuparis.id_export_ticket_' . now()->format('d_m_Y_H-i') . '.xlsx';

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Export Berhasil')
                            ->body("File {$fileName} berhasil diunduh.")
                            ->send();

                        return Excel::download(new TicketsExport($query, $filterText, false), $fileName);
                    }),
            ])
            ->striped()
            ->searchable()
            ->paginated([10, 25, 50, 100, 'all']);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return true;
    }
}
