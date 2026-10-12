<?php

namespace App\Filament\Pages;

use App\Models\DocumentToss;
use App\Models\Ticket;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use setasign\Fpdi\Fpdi;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

class Signature extends Page implements HasForms
{
    use InteractsWithForms;

    private const SOURCE_TICKET     = 'ticket';
    private const SOURCE_UPLOAD     = 'upload';
    private const UPLOAD_TICKET_KEY = 'UPLOAD';
    private const UPLOAD_DIR        = 'toss-uploads';
    private const OUTPUT_DIR        = 'Tosses';
    private const RENDER_DPI        = 150;
    private const QR_SIZE_PX        = 150;
    private const PROCESS_TIMEOUT   = 120;

    protected static ?string $navigationIcon  = 'heroicon-o-document-text';
    protected static string  $view            = 'filament.pages.signature';
    protected static ?string $title           = 'Signature';
    protected static ?string $navigationGroup = 'TOSS';

    public ?string $sourceType      = null;
    public ?string $ticketId        = null;
    public ?string $selectedFile    = null;
    public ?string $pdfUrl          = null;
    public ?string $qrUrl           = null;
    public ?array  $data            = [];
    public array   $debugInfo       = [];
    public ?DocumentToss $currentDocument = null;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->can('page_Signature');
    }

    public function mount(): void
    {
        $this->resetSelection();
        $this->form->fill($this->emptyFormData());
        $this->addDebugInfo('Page mounted', []);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // ================= PILIH FILE =================
                Section::make('Pilih File')
                    ->description('Pilih sumber dokumen, lalu pilih file dari tiket atau upload PDF baru.')
                    ->icon('heroicon-o-folder-open')
                    ->columns(2)
                    ->schema([
                        Radio::make('sourceType')
                            ->label('Sumber Dokumen')
                            ->options([
                                self::SOURCE_TICKET => 'Ambil dari tiket',
                                self::SOURCE_UPLOAD => 'Upload file baru',
                            ])
                            ->inline()
                            ->live()
                            ->columnSpanFull()
                            ->afterStateUpdated(function ($state) {
                                $this->discardUnsavedUpload();
                                $this->resetSelection();
                                $this->sourceType = $state;
                                $this->clearPreview();
                                $this->form->fill(array_merge($this->emptyFormData(), ['sourceType' => $state]));
                            }),

                        Select::make('ticketId')
                            ->label('Pilih Tiket')
                            ->placeholder('Cari kode atau judul tiket...')
                            ->helperText('Tiket yang dipilih menentukan folder file PDF.')
                            ->options(fn() => $this->getTicketOptions())
                            ->searchable()
                            ->live()
                            ->preload()
                            ->columnSpan(fn($get) => $get('ticketId') ? 1 : 'full')
                            ->visible(fn() => $this->sourceType === self::SOURCE_TICKET)
                            ->afterStateUpdated(function ($state) {
                                $this->resetSelection();
                                $this->sourceType = self::SOURCE_TICKET;
                                $this->ticketId   = $state;
                                $this->clearPreview();
                                $this->form->fill(array_merge($this->emptyFormData(), [
                                    'sourceType' => self::SOURCE_TICKET,
                                    'ticketId'   => $state,
                                ]));
                            }),

                        Select::make('selectedFile')
                            ->label('Pilih File PDF')
                            ->placeholder(fn() => $this->ticketId ? 'Pilih file PDF...' : 'Pilih tiket terlebih dahulu')
                            ->helperText('Hanya file PDF yang ada di folder tiket.')
                            ->options(fn() => $this->getFileOptions())
                            ->disabled(fn() => ! $this->ticketId)
                            ->visible(fn() => $this->sourceType === self::SOURCE_TICKET && $this->ticketId !== null)
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state) {
                                if (! $this->ticketId) {
                                    $this->selectedFile = null;
                                    Notification::make()->title('Peringatan')->body('Pilih ticket terlebih dahulu.')->warning()->send();
                                    return;
                                }

                                $this->selectedFile = $state;
                                $this->loadExistingDocument();

                                if ($state) {
                                    $this->generatePreview();
                                } else {
                                    $this->clearPreview();
                                    $this->currentDocument = null;
                                }
                            }),

                        FileUpload::make('uploadedFile')
                            ->label('Upload File PDF')
                            ->helperText('Format PDF, maksimal 20 MB.')
                            ->disk('public')
                            ->directory(self::UPLOAD_DIR)
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(20480)
                            ->visible(fn() => $this->sourceType === self::SOURCE_UPLOAD)
                            ->live()
                            ->dehydrated(false)
                            ->columnSpanFull()
                            ->afterStateUpdated(function ($state) {
                                $file = is_array($state) ? (reset($state) ?: null) : $state;

                                $this->discardUnsavedUpload();
                                $this->selectedFile    = null;
                                $this->currentDocument = null;

                                if (! $file instanceof TemporaryUploadedFile) {
                                    $this->clearPreview();
                                    return;
                                }

                                $stored = $file->storeAs(self::UPLOAD_DIR, Str::uuid() . '.pdf', 'public');
                                if (! $stored) {
                                    Notification::make()->title('Error')->body('Gagal menyimpan file upload.')->danger()->send();
                                    return;
                                }

                                $this->selectedFile = $stored;

                                if (empty($this->data['document_name'])) {
                                    $this->data['document_name'] = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                                }

                                $this->generatePreview();
                            }),

                        Actions::make([
                            Action::make('generatePreview')
                                ->label('Generate Preview')
                                ->icon('heroicon-o-arrow-path')
                                ->color('primary')
                                ->action('generatePreview')
                                ->visible(fn(): bool => $this->hasSourceReady()),
                            Action::make('clearPreview')
                                ->label('Hapus Preview')
                                ->icon('heroicon-o-x-mark')
                                ->color('gray')
                                ->outlined()
                                ->action('clearPreview')
                                ->visible(fn(): bool => ! empty($this->pdfUrl)),
                        ])->columnSpanFull(),
                    ]),

                // ================= IDENTITAS DOKUMEN =================
                Section::make('Identitas Dokumen')
                    ->description('Nama, nomor, dan keterangan singkat dokumen.')
                    ->icon('heroicon-o-identification')
                    ->visible(fn() => $this->hasSelectedFile())
                    ->columns(2)
                    ->schema([
                        TextInput::make('document_name')
                            ->label('Nama Dokumen')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('document_no')
                            ->label('Nomor Dokumen')
                            ->placeholder('Contoh: 001/SK/X/2026')
                            ->required()
                            ->maxLength(100),

                        Textarea::make('document_description')
                            ->label('Deskripsi Dokumen')
                            ->placeholder('Ringkasan singkat isi dokumen')
                            ->rows(3)
                            ->required()
                            ->columnSpanFull(),
                    ]),

                // ================= PENANDA TANGAN =================
                Section::make('Penanda Tangan & Penerima')
                    ->description('Siapa yang menandatangani dan siapa yang menerima dokumen.')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn() => $this->hasSelectedFile())
                    ->columns(2)
                    ->schema([
                        TextInput::make('document_bySign')
                            ->label('Penandatangan')
                            ->placeholder('Contoh: Budi Santoso, S.E.')
                            ->helperText('Nama yang tercantum sebagai penandatangan.')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('document_toReceive')
                            ->label('Penerima Dokumen')
                            ->placeholder('Contoh: PT Nuparis Indonesia')
                            ->helperText('Nama pihak yang menerima dokumen.')
                            ->required()
                            ->maxLength(255),

                        Select::make('document_action')
                            ->label('Tindakan Dokumen')
                            ->options(['Tanda Tangan' => 'Tanda Tangan'])
                            ->default('Tanda Tangan')
                            ->helperText('Tindakan yang diminta dari penerima dokumen.')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                // ================= MASA BERLAKU =================
                Section::make('Masa Berlaku')
                    ->description('Periode dokumen aktif. Kosongkan tanggal berakhir jika tidak ada batas.')
                    ->icon('heroicon-o-calendar-days')
                    ->visible(fn() => $this->hasSelectedFile())
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('document_start')
                            ->label('Tanggal Mulai Aktif')
                            ->native(false)
                            ->closeOnDateSelection(true)
                            ->prefixIcon('heroicon-m-calendar')
                            ->default(now()),

                        DateTimePicker::make('document_end')
                            ->label('Tanggal Berakhir')
                            ->native(false)
                            ->closeOnDateSelection(true)
                            ->prefixIcon('heroicon-m-calendar')
                            ->default(null),
                    ]),

                // ================= CATATAN =================
                Section::make('Catatan')
                    ->description('Catatan tambahan terkait dokumen.')
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->visible(fn() => $this->hasSelectedFile())
                    ->schema([
                        RichEditor::make('document_notes')
                            ->hiddenLabel()
                            ->placeholder('Catatan tambahan...')
                            ->toolbarButtons([
                                'blockquote',
                                'bold',
                                'bulletList',
                                'codeBlock',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'strike',
                                'underline',
                            ])
                            ->extraInputAttributes(['style' => 'min-height: 12rem;'])
                            ->columnSpanFull()
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    private function hasSelectedFile(): bool
    {
        return ! empty($this->selectedFile);
    }

    // ── Sumber dokumen ─────────────────────────────────────────────────────────

    private function hasSourceReady(): bool
    {
        if (empty($this->selectedFile)) {
            return false;
        }

        return $this->sourceType === self::SOURCE_UPLOAD
            || ($this->sourceType === self::SOURCE_TICKET && ! empty($this->ticketId));
    }

    private function resolveTicketCode(): ?string
    {
        if ($this->sourceType === self::SOURCE_UPLOAD) {
            return self::UPLOAD_TICKET_KEY;
        }

        if (! $this->ticketId) {
            return null;
        }

        return Ticket::where('uuid', $this->ticketId)->value('ticket_code');
    }

    private function resetSelection(): void
    {
        $this->sourceType      = null;
        $this->ticketId        = null;
        $this->selectedFile    = null;
        $this->currentDocument = null;
    }

    /** Hapus file upload yang belum pernah disimpan sebagai dokumen. */
    private function discardUnsavedUpload(): void
    {
        if ($this->sourceType !== self::SOURCE_UPLOAD || ! $this->selectedFile) {
            return;
        }

        if (! str_starts_with($this->selectedFile, self::UPLOAD_DIR . '/')) {
            return;
        }

        if (! DocumentToss::where('document_path', $this->selectedFile)->exists()) {
            Storage::disk('public')->delete($this->selectedFile);
        }
    }

    protected function loadExistingDocument(): void
    {
        if (! $this->selectedFile) {
            return;
        }

        try {
            $ticketCode = $this->resolveTicketCode();
            if (! $ticketCode) {
                return;
            }

            $this->currentDocument = DocumentToss::where('ticket_code', $ticketCode)
                ->where('document_path', $this->selectedFile)
                ->first();

            if (! $this->currentDocument) {
                return;
            }

            $this->form->fill([
                'sourceType'           => $this->sourceType,
                'ticketId'             => $this->ticketId,
                'selectedFile'         => $this->selectedFile,
                'document_name'        => $this->currentDocument->document_name        ?? '',
                'document_description' => $this->currentDocument->document_description ?? '',
                'document_bySign'      => $this->currentDocument->document_bySign      ?? '',
                'document_toReceive'   => $this->currentDocument->document_toReceive   ?? '',
                'document_action'      => $this->currentDocument->document_action      ?? '',
                'document_no'          => $this->currentDocument->document_no          ?? '',
                'document_start'       => $this->currentDocument->document_start       ?? null,
                'document_end'         => $this->currentDocument->document_end         ?? null,
                'document_notes'       => $this->currentDocument->document_notes       ?? '',
            ]);

            Notification::make()->title('Dokumen Ditemukan')->body('Data dokumen yang sudah ada telah dimuat.')->info()->send();
        } catch (Throwable $e) {
            Log::warning('[Signature] loadExistingDocument gagal', ['error' => $e->getMessage()]);
        }
    }

    protected function getTicketOptions(): array
    {
        try {
            return Ticket::select('uuid', 'ticket_code', 'ticket_title')
                ->whereNotNull('uuid')->whereNotNull('ticket_code')
                ->where('uuid', '!=', '')->where('ticket_code', '!=', '')
                ->orderBy('created_at', 'desc')
                ->get()
                ->mapWithKeys(fn($ticket) => [$ticket->uuid => $ticket->ticket_code . ' - ' . $ticket->ticket_title])
                ->all();
        } catch (Throwable $e) {
            Log::error('[Signature] Gagal memuat tiket', ['error' => $e->getMessage()]);
            return [];
        }
    }

    protected function getFileOptions(): array
    {
        try {
            if (! $this->ticketId) {
                return [];
            }

            $ticketCode = Ticket::where('uuid', $this->ticketId)->value('ticket_code');
            if (! $ticketCode) {
                return [];
            }

            $disk   = Storage::disk('public');
            $folder = "tickets/{$ticketCode}";
            $files  = [];

            if ($disk->exists($folder)) {
                foreach ($disk->files($folder) as $file) {
                    if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'pdf') {
                        $files[$file] = basename($file);
                    }
                }
            }

            return $files;
        } catch (Throwable $e) {
            Log::error('[Signature] Gagal memuat file tiket', ['error' => $e->getMessage()]);
            return [];
        }
    }

    // ── Preview ────────────────────────────────────────────────────────────────

    private function generateQrCode(string $content, string $path): void
    {
        $qrCode = Builder::create()
            ->writer(new PngWriter())
            ->writerOptions([])
            ->data($content)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::High)
            ->size(300)
            ->margin(10)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build();

        Storage::disk('public')->put($path, $qrCode->getString());
    }

    public function generatePreview(): void
    {
        try {
            if (! $this->hasSourceReady()) {
                Notification::make()->title('Warning')->body('Pilih sumber dan file terlebih dahulu.')->warning()->send();
                return;
            }

            $ticketCode = $this->resolveTicketCode();
            if (! $ticketCode) {
                throw new \RuntimeException('Ticket tidak ditemukan.');
            }

            $disk = Storage::disk('public');
            if (! $disk->exists($this->selectedFile)) {
                throw new \RuntimeException('File PDF tidak ditemukan.');
            }

            $slug   = $this->buildSlug($ticketCode);
            $qrPath = "qrcodes/{$slug}.png";

            $disk->makeDirectory('qrcodes');
            $this->generateQrCode(url('toss/' . $slug), $qrPath);

            $this->pdfUrl = $disk->url($this->selectedFile);
            $this->qrUrl  = $disk->url($qrPath);

            $this->dispatch('preview-updated', ['pdfUrl' => $this->pdfUrl, 'qrUrl' => $this->qrUrl]);
            Notification::make()->title('Success')->body('Preview berhasil di-generate!')->success()->send();
        } catch (\RuntimeException $e) {
            Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
        } catch (Throwable $e) {
            Log::error('[Signature] generatePreview gagal', ['error' => $e->getMessage()]);
            Notification::make()->title('Error')->body('Gagal generate preview. Silakan coba lagi.')->danger()->send();
        }
    }

    public function clearPreview(): void
    {
        $this->pdfUrl = null;
        $this->qrUrl  = null;
        $this->dispatch('preview-cleared');
    }

    // ── Eksekusi proses eksternal ──────────────────────────────────────────────

    private function gs(): string
    {
        static $path = null;

        if ($path !== null) {
            return $path;
        }

        foreach (['/usr/bin/gs', '/bin/gs', '/usr/local/bin/gs', '/opt/homebrew/bin/gs', '/usr/local/opt/ghostscript/bin/gs'] as $candidate) {
            if (is_executable($candidate)) {
                return $path = $candidate;
            }
        }

        return $path = (new ExecutableFinder())->find('gs') ?? 'gs';
    }

    /** @return array{0: bool, 1: string} [berhasil, output] */
    private function run(array $command): array
    {
        $process = new Process($command);
        $process->setTimeout(self::PROCESS_TIMEOUT);

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            Log::error('[Signature] Proses melebihi batas waktu', ['bin' => $command[0] ?? '']);
            return [false, 'timeout'];
        }

        return [$process->isSuccessful(), trim($process->getOutput() . $process->getErrorOutput())];
    }

    /** @return array{0: bool, 1: string} */
    private function runGs(array $args): array
    {
        return $this->run([$this->gs(), '-dQUIET', '-dSAFER', '-dBATCH', '-dNOPAUSE', ...$args]);
    }

    // ── Pemrosesan PDF ─────────────────────────────────────────────────────────

    /**
     * Mengembalikan path PDF yang siap diproses. Jika PDF terenkripsi (hanya restriksi,
     * tanpa password buka), ditulis ulang tanpa proteksi via Ghostscript.
     */
    private function decryptPdfIfNeeded(string $pdfAbsPath): string
    {
        try {
            (new Fpdi())->setSourceFile($pdfAbsPath);
            return $pdfAbsPath;
        } catch (Throwable $e) {
            if (stripos($e->getMessage(), 'encrypt') === false) {
                return $pdfAbsPath;
            }
        }

        $decryptedPath = dirname($pdfAbsPath) . '/'
            . pathinfo($pdfAbsPath, PATHINFO_FILENAME)
            . '_decrypted_' . uniqid() . '.pdf';

        [$ok, $output] = $this->runGs(['-sDEVICE=pdfwrite', '-sOutputFile=' . $decryptedPath, $pdfAbsPath]);

        if (! $ok || ! file_exists($decryptedPath) || filesize($decryptedPath) === 0) {
            Log::error('[Signature] Gagal decrypt PDF', ['file' => $pdfAbsPath, 'output' => $output]);
            @unlink($decryptedPath);

            throw new \RuntimeException(
                'PDF ini terproteksi dan tidak bisa dibuka otomatis oleh sistem. ' .
                    'Kemungkinan PDF butuh password untuk dibuka. Simpan ulang tanpa password ' .
                    '("Print to PDF" atau export ulang), lalu upload kembali.'
            );
        }

        return $decryptedPath;
    }

    private function renderPdfToPng(string $pdfPath, string $pngPath, int $page): void
    {
        @unlink($pngPath);

        [$ok, $output] = $this->runGs([
            '-dNOPROMPT',
            '-sDEVICE=png16m',
            '-r' . self::RENDER_DPI,
            "-dFirstPage={$page}",
            "-dLastPage={$page}",
            '-sOutputFile=' . $pngPath,
            $pdfPath,
        ]);

        if (! $ok || ! file_exists($pngPath) || filesize($pngPath) === 0) {
            Log::error('[Signature] Render PNG gagal', ['page' => $page, 'output' => $output]);
            throw new \RuntimeException("Gagal merender halaman {$page} dari dokumen.");
        }
    }

    private function compositeQrToPng(string $pageImgPath, string $qrPath, string $outputPath, float $ratioX, float $ratioY): void
    {
        $img = @imagecreatefrompng($pageImgPath);
        $qr  = @imagecreatefrompng($qrPath);

        if (! $img || ! $qr) {
            throw new \RuntimeException('Gagal memuat gambar halaman atau QR Code.');
        }

        $imgW = imagesx($img);
        $imgH = imagesy($img);
        $size = self::QR_SIZE_PX;

        $resizedQr = imagecreatetruecolor($size, $size);
        imagealphablending($resizedQr, false);
        imagesavealpha($resizedQr, true);
        imagefill($resizedQr, 0, 0, imagecolorallocatealpha($resizedQr, 0, 0, 0, 127));
        imagecopyresampled($resizedQr, $qr, 0, 0, 0, 0, $size, $size, imagesx($qr), imagesy($qr));

        $xPx = max(0, min((int) ($ratioX * $imgW), $imgW - $size));
        $yPx = max(0, min((int) ($ratioY * $imgH), $imgH - $size));

        imagealphablending($img, true);
        imagecopy($img, $resizedQr, $xPx, $yPx, 0, 0, $size, $size);

        if (! imagepng($img, $outputPath) || ! file_exists($outputPath) || filesize($outputPath) === 0) {
            throw new \RuntimeException('Gagal menempelkan QR Code ke halaman dokumen.');
        }
    }

    private function convertPngToPdf(string $pngPath, string $pdfPath): void
    {
        if (
            $this->convertWithGhostscript($pngPath, $pdfPath)
            || $this->convertWithImageMagick($pngPath, $pdfPath)
            || $this->convertWithFpdf($pngPath, $pdfPath)
        ) {
            return;
        }

        Log::error('[Signature] Semua metode konversi PNG ke PDF gagal', ['png' => $pngPath]);
        throw new \RuntimeException('Gagal mengonversi halaman bertanda tangan menjadi PDF.');
    }

    private function convertWithGhostscript(string $pngPath, string $pdfPath): bool
    {
        $variants = [
            [],
            ['-dHaveTransparency=false'],
            ['-dColorConversionStrategy=/sRGB', '-dProcessColorModel=/DeviceRGB'],
        ];

        foreach ($variants as $extra) {
            @unlink($pdfPath);

            [$ok] = $this->runGs([
                '-sDEVICE=pdfwrite',
                '-dCompatibilityLevel=1.4',
                ...$extra,
                '-sOutputFile=' . $pdfPath,
                $pngPath,
            ]);

            if ($ok && file_exists($pdfPath) && filesize($pdfPath) > 0) {
                return true;
            }
        }

        return false;
    }

    private function convertWithImageMagick(string $pngPath, string $pdfPath): bool
    {
        $convert = (new ExecutableFinder())->find('convert');
        if (! $convert) {
            return false;
        }

        $variants = [
            [],
            ['-density', (string) self::RENDER_DPI, '-quality', '100'],
            ['-flatten'],
            ['-background', 'white', '-alpha', 'remove', '-alpha', 'off'],
        ];

        foreach ($variants as $options) {
            @unlink($pdfPath);

            [$ok] = $this->run([$convert, ...$options, $pngPath, $pdfPath]);

            if ($ok && file_exists($pdfPath) && filesize($pdfPath) > 0) {
                return true;
            }
        }

        return false;
    }

    private function convertWithFpdf(string $pngPath, string $pdfPath): bool
    {
        if (! class_exists('FPDF')) {
            $fpdfPath = base_path('vendor/setasign/fpdf/fpdf.php');
            if (! file_exists($fpdfPath)) {
                return false;
            }
            require_once $fpdfPath;
        }

        try {
            @unlink($pdfPath);

            [$width, $height] = getimagesize($pngPath);
            $widthMm  = ($width / self::RENDER_DPI) * 25.4;
            $heightMm = ($height / self::RENDER_DPI) * 25.4;

            $pdf = new \FPDF();
            $pdf->AddPage($widthMm > $heightMm ? 'L' : 'P', [$widthMm, $heightMm]);
            $pdf->Image($pngPath, 0, 0, $widthMm, $heightMm);
            $pdf->Output('F', $pdfPath);

            return file_exists($pdfPath) && filesize($pdfPath) > 0;
        } catch (Throwable $e) {
            Log::warning('[Signature] Konversi FPDF gagal', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function extractPdfPages(string $sourcePdf, string $outputPdf, int $startPage, int $endPage): void
    {
        @unlink($outputPdf);

        [$ok, $output] = $this->runGs([
            '-sDEVICE=pdfwrite',
            "-dFirstPage={$startPage}",
            "-dLastPage={$endPage}",
            '-sOutputFile=' . $outputPdf,
            $sourcePdf,
        ]);

        if (! $ok || ! file_exists($outputPdf) || filesize($outputPdf) === 0) {
            Log::error('[Signature] Extract halaman gagal', ['range' => "{$startPage}-{$endPage}", 'output' => $output]);
            throw new \RuntimeException("Gagal mengambil halaman {$startPage}-{$endPage} dari dokumen asli.");
        }
    }

    private function mergePdfParts(array $parts, string $outputPath): void
    {
        if (empty($parts)) {
            throw new \RuntimeException('Tidak ada bagian PDF untuk digabungkan.');
        }

        @unlink($outputPath);

        [$ok, $output] = $this->runGs(['-sDEVICE=pdfwrite', '-sOutputFile=' . $outputPath, ...$parts]);

        if (! $ok || ! file_exists($outputPath) || filesize($outputPath) === 0) {
            Log::error('[Signature] Merge PDF gagal', ['output' => $output]);
            throw new \RuntimeException('Gagal menggabungkan halaman dokumen.');
        }
    }

    /**
     * Melempar exception jika semua metode gagal, supaya tidak menghasilkan
     * dokumen final yang terpotong.
     */
    private function getPdfPageCount(string $pdfAbsPath): int
    {
        try {
            $count = (new Fpdi())->setSourceFile($pdfAbsPath);
            if ($count > 0) {
                return $count;
            }
        } catch (Throwable) {
            // lanjut ke metode berikutnya
        }

        $psPath = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $pdfAbsPath);
        [$ok, $output] = $this->runGs([
            '-dNODISPLAY',
            '--permit-file-read=' . $pdfAbsPath,
            '-c',
            "({$psPath}) (r) file runpdfbegin pdfpagecount = quit",
        ]);

        if ($ok && ctype_digit($output) && (int) $output > 0) {
            return (int) $output;
        }

        if ($pdfinfo = (new ExecutableFinder())->find('pdfinfo')) {
            [, $info] = $this->run([$pdfinfo, $pdfAbsPath]);
            if (preg_match('/Pages:\s*(\d+)/', $info, $matches)) {
                return (int) $matches[1];
            }
        }

        Log::error('[Signature] Semua metode hitung halaman gagal', ['file' => $pdfAbsPath]);

        throw new \RuntimeException(
            'Gagal mendeteksi jumlah halaman PDF. Proses dibatalkan agar tidak ada halaman yang hilang.'
        );
    }

    // ── Simpan posisi QR ───────────────────────────────────────────────────────

    #[On('save-qr-position')]
    public function savePosition(
        float $x,
        float $y,
        float $scale,
        int   $page         = 1,
        float $canvasWidth  = 892,
        float $canvasHeight = 1262,
        float $renderScale  = 1.5,
        float $ratioX       = 0.5,
        float $ratioY       = 0.5
    ): void {
        $tempFiles = [];

        try {
            if (! $this->hasSourceReady()) {
                throw new \RuntimeException('Pilih sumber dokumen dan file terlebih dahulu.');
            }

            $formData = $this->form->getState();

            $ticketCode = $this->resolveTicketCode();
            if (! $ticketCode) {
                throw new \RuntimeException('Ticket tidak ditemukan.');
            }

            $disk = Storage::disk('public');
            $slug = $this->buildSlug($ticketCode);

            $qrRelPath    = "qrcodes/{$slug}.png";
            $finalRelPath = self::OUTPUT_DIR . "/{$slug}_final.pdf";

            $pdfAbs     = $disk->path($this->selectedFile);
            $qrAbs      = $disk->path($qrRelPath);
            $outputDir  = $disk->path(self::OUTPUT_DIR);
            $finalAbs   = $disk->path($finalRelPath);

            $pageImgAbs   = "{$outputDir}/{$slug}_page{$page}.png";
            $mergedPngAbs = "{$outputDir}/{$slug}_merged{$page}.png";
            $mergedPdfAbs = "{$outputDir}/{$slug}_merged{$page}.pdf";
            $beforePdfAbs = "{$outputDir}/{$slug}_before.pdf";
            $afterPdfAbs  = "{$outputDir}/{$slug}_after.pdf";
            $finalTmpAbs  = "{$outputDir}/{$slug}_final.tmp.pdf";

            $tempFiles = [$pageImgAbs, $mergedPngAbs, $mergedPdfAbs, $beforePdfAbs, $afterPdfAbs, $finalTmpAbs];

            if (! is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            if (! file_exists($pdfAbs)) {
                throw new \RuntimeException('File PDF tidak ditemukan.');
            }

            if (! file_exists($qrAbs)) {
                $disk->makeDirectory('qrcodes');
                $this->generateQrCode(url('toss/' . $slug), $qrRelPath);
            }

            $sourcePdf = $this->decryptPdfIfNeeded($pdfAbs);
            if ($sourcePdf !== $pdfAbs) {
                $tempFiles[] = $sourcePdf;
            }

            $totalPages = $this->getPdfPageCount($sourcePdf);

            if ($page < 1 || $page > $totalPages) {
                throw new \RuntimeException("Halaman yang dipilih ({$page}) di luar jangkauan dokumen (1-{$totalPages}).");
            }

            $this->renderPdfToPng($sourcePdf, $pageImgAbs, $page);
            $this->compositeQrToPng($pageImgAbs, $qrAbs, $mergedPngAbs, $ratioX, $ratioY);
            $this->convertPngToPdf($mergedPngAbs, $mergedPdfAbs);

            $parts = [];

            if ($page > 1) {
                $this->extractPdfPages($sourcePdf, $beforePdfAbs, 1, $page - 1);
                $parts[] = $beforePdfAbs;
            }

            $parts[] = $mergedPdfAbs;

            if ($page < $totalPages) {
                $this->extractPdfPages($sourcePdf, $afterPdfAbs, $page + 1, $totalPages);
                $parts[] = $afterPdfAbs;
            }

            $this->mergePdfParts($parts, $finalTmpAbs);

            $finalPageCount = $this->getPdfPageCount($finalTmpAbs);
            if ($finalPageCount !== $totalPages) {
                Log::error('[Signature] Jumlah halaman final tidak cocok', [
                    'expected' => $totalPages,
                    'actual'   => $finalPageCount,
                    'slug'     => $slug,
                ]);
                throw new \RuntimeException(
                    "Hasil akhir memiliki {$finalPageCount} halaman, seharusnya {$totalPages}. Dokumen tidak disimpan."
                );
            }

            if (! rename($finalTmpAbs, $finalAbs)) {
                throw new \RuntimeException('Gagal menyimpan file dokumen final.');
            }

            DocumentToss::updateOrCreate(
                [
                    'ticket_code'   => $ticketCode,
                    'document_path' => $this->selectedFile,
                ],
                [
                    'qr_position_x'        => $ratioX,
                    'qr_position_y'        => $ratioY,
                    'qr_scale'             => $scale,
                    'qr_page'              => $page,
                    'qr_path'              => $qrRelPath,
                    'document_name'        => $formData['document_name']        ?? '',
                    'document_description' => $formData['document_description'] ?? '',
                    'document_bySign'      => $formData['document_bySign']      ?? '',
                    'document_toReceive'   => $formData['document_toReceive']   ?? '',
                    'document_action'      => $formData['document_action']      ?? '',
                    'document_no'          => $formData['document_no']          ?? '',
                    'document_notes'       => $formData['document_notes']       ?? '',
                    'document_final_path'  => $finalRelPath,
                    'document_slug'        => $slug,
                    'document_start'       => $formData['document_start']       ?? null,
                    'document_end'         => $formData['document_end']         ?? null,
                ]
            );

            Notification::make()
                ->title('Berhasil Disimpan')
                ->body("File: {$finalRelPath} ({$totalPages} halaman, " . round(filesize($finalAbs) / 1024) . ' KB)')
                ->success()
                ->send();
        } catch (ValidationException $e) {
            throw $e;
        } catch (\RuntimeException $e) {
            Notification::make()->title('Gagal Menyimpan')->body($e->getMessage())->danger()->send();
        } catch (Throwable $e) {
            Log::error('[Signature] savePosition gagal', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            Notification::make()
                ->title('Gagal Menyimpan')
                ->body('Terjadi kesalahan saat memproses dokumen. Silakan coba lagi atau hubungi administrator.')
                ->danger()
                ->send();
        } finally {
            foreach ($tempFiles as $tmp) {
                if ($tmp && file_exists($tmp)) {
                    @unlink($tmp);
                }
            }

            // Beri tahu frontend bahwa proses selesai (berhasil maupun gagal)
            // supaya overlay loading disembunyikan.
            $this->dispatch('qr-position-saved');
        }
    }

    private function buildSlug(string $ticketCode): string
    {
        $fileName = pathinfo($this->selectedFile, PATHINFO_FILENAME);

        return "{$ticketCode}_progress{$this->extractProgressIndex($fileName)}_{$this->sanitizeFileName($fileName)}";
    }

    // ── Debug & refresh ────────────────────────────────────────────────────────

    #[On('refresh-debug')]
    public function refreshDebug(): void
    {
        $this->addDebugInfo('Debug refreshed', ['timestamp' => now()->toDateTimeString()]);
    }

    public function refreshTickets(): void
    {
        $this->discardUnsavedUpload();
        $this->resetSelection();
        $this->form->fill($this->emptyFormData());
        $this->dispatch('$refresh');
        Notification::make()->title('Refreshed')->body('Daftar tiket diperbarui.')->info()->send();
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function emptyFormData(): array
    {
        return [
            'sourceType'           => null,
            'uploadedFile'         => null,
            'ticketId'             => null,
            'selectedFile'         => null,
            'document_name'        => '',
            'document_description' => '',
            'document_bySign'      => '',
            'document_toReceive'   => '',
            'document_action'      => 'Tanda Tangan',
            'document_no'          => '',
            'document_start'       => null,
            'document_end'         => null,
            'document_notes'       => '',
        ];
    }

    private function extractProgressIndex(string $fileName): int
    {
        return preg_match('/progress-?(\d+)/', $fileName, $matches) ? (int) $matches[1] : 1;
    }

    private function sanitizeFileName(string $fileName): string
    {
        $clean = str_replace(' ', '_', $fileName);
        $clean = preg_replace('/[^\w\-\.]/', '_', $clean);
        $clean = preg_replace('/_+/', '_', $clean);

        return trim($clean, '_');
    }

    private function addDebugInfo(string $action, array $data): void
    {
        $this->debugInfo[] = ['time' => now()->format('H:i:s.u'), 'action' => $action, 'data' => $data];

        if (count($this->debugInfo) > 15) {
            $this->debugInfo = array_slice($this->debugInfo, -15);
        }
    }

    // ── Computed ───────────────────────────────────────────────────────────────

    #[Computed] public function hasPdf(): bool
    {
        return ! empty($this->pdfUrl);
    }

    #[Computed] public function hasQr(): bool
    {
        return ! empty($this->qrUrl);
    }

    #[Computed] public function canPreview(): bool
    {
        return $this->hasPdf && $this->hasQr;
    }

    #[Computed] public function isTicketSelected(): bool
    {
        return ! empty($this->ticketId);
    }

    #[Computed] public function hasCurrentDocument(): bool
    {
        return $this->currentDocument !== null;
    }

    #[Computed] public function canSaveDocument(): bool
    {
        return $this->hasSourceReady();
    }

    #[Computed] public function availableFilesCount(): int
    {
        return count($this->getFileOptions());
    }
}
