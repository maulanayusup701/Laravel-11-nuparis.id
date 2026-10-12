<?php

namespace App\Filament\Clusters\Ticket\Resources\TicketResource\Pages;

use App\Filament\Clusters\Ticket\Resources\TicketResource;
use App\Models\TicketStatus;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * True jika tiket tersimpan berstatus "close" DAN dropdown status
     * di form belum diubah (masih "close").
     * Public karena dipanggil dari view progress-note.
     */
    public function isLockedClosed(?array $formData = null): bool
    {
        $savedName = TicketStatus::find($this->record->ticket_status_uuid)?->ticket_status_name;

        if ($savedName !== 'close') {
            return false;
        }

        $selectedUuid = ($formData ?? $this->data)['ticket_status_uuid']
            ?? $this->record->ticket_status_uuid;

        return TicketStatus::find($selectedUuid)?->ticket_status_name === 'close';
    }

    // Tombol Simpan: hilang saat terkunci, muncul lagi jika status diganti
    protected function getSaveFormAction(): Actions\Action
    {
        return parent::getSaveFormAction()
            ->hidden(fn() => $this->isLockedClosed());
    }

    // Tombol Batal: sama seperti Simpan
    protected function getCancelFormAction(): Actions\Action
    {
        return parent::getCancelFormAction()
            ->hidden(fn() => $this->isLockedClosed());
    }

    /* ---------------------------------------------------------------
     |  Edit & hapus progress TERBARU
     | ------------------------------------------------------------- */

    protected function latestProgressKey(): ?int
    {
        $notes = $this->record->ticket_progress ?? [];

        return empty($notes) ? null : array_key_last($notes);
    }

    /**
     * Pengaman di server: tolak jika tiket terkunci atau tidak ada progress.
     */
    protected function canManageLatestProgress(): bool
    {
        if ($this->isLockedClosed()) {
            Notification::make()
                ->warning()
                ->title('Riwayat tidak bisa diubah')
                ->body('Tiket berstatus selesai. Ubah status terlebih dahulu.')
                ->send();

            return false;
        }

        if ($this->latestProgressKey() === null) {
            Notification::make()->warning()->title('Belum ada progress')->send();

            return false;
        }

        return true;
    }

    /**
     * Kumpulkan path file (relatif ke disk "public") milik satu entri progress:
     * file dari FileUpload + gambar/lampiran yang disisipkan di rich editor.
     */
    protected function extractFilePaths(array $entry): array
    {
        $paths = $entry['file'] ?? [];

        $html = $entry['progress'] ?? '';
        if ($html !== '') {
            preg_match_all('/(?:src|href)=(["\'])(.*?)\1/i', $html, $matches);

            foreach ($matches[2] as $url) {
                $urlPath = parse_url(html_entity_decode($url), PHP_URL_PATH) ?: '';
                $pos = strpos($urlPath, '/storage/');

                if ($pos !== false) {
                    $paths[] = urldecode(substr($urlPath, $pos + strlen('/storage/')));
                }
            }
        }

        // Pengaman: hanya file di folder tickets/ dan tanpa path traversal
        return collect($paths)
            ->filter(fn($path) => is_string($path)
                && str_starts_with($path, 'tickets/')
                && !str_contains($path, '..'))
            ->unique()
            ->values()
            ->all();
    }

    public function editLatestProgressAction(): Actions\Action
    {
        return Actions\Action::make('editLatestProgress')
            ->label('Edit Progress Terbaru')
            ->modalHeading('Edit Progress Terbaru')
            ->modalSubmitActionLabel('Simpan Perubahan')
            ->modalWidth('3xl')
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping()
            ->modalCancelActionLabel('Batal')
            ->fillForm(function (): array {
                $key = $this->latestProgressKey();
                $latest = $key !== null ? $this->record->ticket_progress[$key] : [];

                return [
                    'progress' => $latest['progress'] ?? '',
                    'progress_files' => $latest['file'] ?? [],
                ];
            })
            ->form([
                RichEditor::make('progress')
                    ->label('Progress')
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsVisibility('public')
                    ->fileAttachmentsDirectory(fn() => 'tickets/' . $this->record->ticket_code),
                FileUpload::make('progress_files')
                    ->label('File')
                    ->disk('public')
                    ->directory(fn() => 'tickets/' . $this->record->ticket_code)
                    ->multiple()
                    ->nullable()
                    ->downloadable()
                    ->openable()
                    ->preserveFilenames(),
            ])
            ->action(function (array $data): void {
                if (!$this->canManageLatestProgress()) {
                    return;
                }

                $notes = $this->record->ticket_progress ?? [];
                $key = $this->latestProgressKey();

                $notes[$key] = array_merge($notes[$key], [
                    'progress' => $data['progress'] ?? '',
                    'file' => $data['progress_files'] ?? [],
                    'edited_at' => now()->format('Y-m-d H:i:s'),
                ]);

                $this->record->update(['ticket_progress' => $notes]);
                $this->record->refresh();

                Notification::make()->success()->title('Progress berhasil diubah')->send();
            });
    }

    public function deleteLatestProgressAction(): Actions\Action
    {
        return Actions\Action::make('deleteLatestProgress')
            ->label('Hapus Progress Terbaru')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Hapus progress terbaru?')
            ->modalDescription('Progress terbaru beserta file dan gambar di dalamnya akan dihapus dan tidak dapat dikembalikan.')
            ->modalSubmitActionLabel('Ya, Hapus')
            ->closeModalByClickingAway()
            ->closeModalByEscaping()
            ->action(function (): void {
                if (!$this->canManageLatestProgress()) {
                    return;
                }

                $notes = $this->record->ticket_progress ?? [];
                $key = $this->latestProgressKey();

                // File milik entri yang dihapus (upload + gambar/lampiran di editor)
                $filesToDelete = $this->extractFilePaths($notes[$key]);

                unset($notes[$key]);

                // Jangan hapus file yang masih dipakai entri lain
                // (nama file dipertahankan, jadi bisa saja sama)
                $stillUsed = collect($notes)
                    ->flatMap(fn($entry) => $this->extractFilePaths($entry))
                    ->all();

                $filesToDelete = array_values(array_diff($filesToDelete, $stillUsed));

                if (!empty($filesToDelete)) {
                    Storage::disk('public')->delete($filesToDelete);
                }

                $this->record->update(['ticket_progress' => array_values($notes)]);
                $this->record->refresh();

                Notification::make()->success()->title('Progress terbaru dihapus')->send();
            });
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $progressNotes = $this->record->ticket_progress ?? [];

        // Pengaman di server: tiket yang terkunci tidak boleh menambah progress
        $locked = $this->isLockedClosed($data);

        if (!$locked && (!empty($data['progress']) || !empty($data['progress_files']))) {
            $progressNotes[] = [
                'progress' => $data['progress'] ?? '',
                'file' => $data['progress_files'] ?? [],
                'timestamp' => now()->format('Y-m-d H:i:s'),
            ];
        }

        $data['ticket_progress'] = $progressNotes;

        // Hapus field input sementara
        unset($data['progress'], $data['progress_files']);

        return $data;
    }

    // Reload setelah save
    protected function afterSave(): void
    {
        $this->js('window.location.reload()');
    }
}
