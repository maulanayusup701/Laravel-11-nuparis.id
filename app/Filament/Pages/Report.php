<?php

namespace App\Filament\Pages;

use App\Jobs\SendReportEmailJob;
use App\Mail\ReportExportMail;
use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action as PageAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
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
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Report extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static string $view = 'filament.pages.report';
    protected static ?string $title = 'Report';
    protected static ?string $navigationLabel = 'Report';
    protected static ?string $slug = 'Report';

    public ?array $data = [];
    public array $reportSummary = [];
    public bool $hasReport = false;
    public bool $isLoading = false;
    public array $debugInfo = [];

    // Properties untuk modal
    public bool $showEmailModal = false;
    public string $emailTo = '';
    public string $emailCc = '';
    public string $emailSubject = '';
    public string $emailBody = '';

    // Properties untuk mencegah double sending
    public bool $isSendingEmail = false;
    public bool $isGeneratingReport = false;

    private string $cacheKey;

    public function boot(): void
    {
        $this->cacheKey = 'report_data_' . auth()->id() . '_' . session()->getId();
    }

    public function mount(): void
    {
        $this->hasReport = false;
        $this->reportSummary = [];
        $this->debugInfo = [];
        $this->showEmailModal = false;
        $this->isSendingEmail = false;
        $this->isGeneratingReport = false;
        $this->form->fill($this->defaultFormState());
    }

    protected function getViewData(): array
    {
        $reportData = $this->hasReport ? cache()->get($this->cacheKey, []) : [];

        return [
            'reportData' => $reportData,
            'reportSummary' => $this->reportSummary,
            'debugInfo' => $this->debugInfo,
            'isSendingEmail' => $this->isSendingEmail,
            'isGeneratingReport' => $this->isGeneratingReport,
        ];
    }

    // ============================================================
    // HEADER ACTIONS
    // ============================================================

    protected function getHeaderActions(): array
    {
        return [
            PageAction::make('generateReport')
                ->label('Lihat Progress')
                ->color('primary')
                ->icon('heroicon-o-document-chart-bar')
                ->action('generateReport')
                ->requiresConfirmation()
                ->modalHeading('Lihat Progress')
                ->modalDescription('Tampilkan progress tiket sesuai filter yang dipilih?')
                ->modalSubmitActionLabel('Ya, Tampilkan')
                ->modalCancelActionLabel('Batal')
                ->visible(fn() => !empty($this->data['selected_tickets']) && !empty($this->data['date_range'])),

            PageAction::make('exportReport')
                ->label('Export PDF')
                ->color('success')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(fn() => $this->openEmailModal())
                ->visible(fn() => $this->hasReport),

            PageAction::make('resetFilters')
                ->label('Reset Filter')
                ->color('gray')
                ->icon('heroicon-o-arrow-path')
                ->outlined()
                ->action('resetFilters')
                ->visible(fn() => !empty($this->data['selected_tickets'])),
        ];
    }

    // ============================================================
    // MULTIPLE CC METHODS
    // ============================================================

    /**
     * Get default CC list dynamically
     */
    protected function getDefaultCcList(): string
    {
        $ccList = [];

        // 1. CC ke tim internal (statis)
        $ccList[] = 'markom@nuparis.id';
        $ccList[] = 'support@nuparis.id';
        $ccList[] = 'admin@nuparis.id';

        // 2. CC ke user yang sedang login (pembuat laporan)
        if (auth()->check() && auth()->user()->email) {
            $ccList[] = auth()->user()->email;
        }

        // 4. Remove duplicate dan email kosong
        $ccList = array_filter(array_unique($ccList));

        return implode(', ', $ccList);
    }

    /**
     * Validate CC list
     */
    protected function validateCcList(string $ccString): array
    {
        $emails = array_filter(array_map('trim', explode(',', $ccString)));
        $validEmails = [];
        $invalidEmails = [];

        foreach ($emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $validEmails[] = $email;
            } else {
                $invalidEmails[] = $email;
            }
        }

        if (!empty($invalidEmails)) {
            Log::warning('Invalid CC emails detected', ['invalid' => $invalidEmails]);
        }

        return $validEmails;
    }

    // ============================================================
    // MODAL METHODS
    // ============================================================

    public function openEmailModal(): void
    {
        Log::info('=== MEMBUKA MODAL EMAIL ===');

        if (!$this->hasReport) {
            Log::warning('Tidak ada data report saat membuka modal');
            Notification::make()->title('Informasi')
                ->body('Tidak ada data untuk diexport. Tampilkan laporan terlebih dahulu.')
                ->warning()->send();
            return;
        }

        $reportData = cache()->get($this->cacheKey, []);
        Log::info('Data dari cache saat buka modal', [
            'cache_key' => $this->cacheKey,
            'has_report_data' => !empty($reportData),
            'report_data_keys' => array_keys($reportData),
        ]);

        if (empty($reportData)) {
            Log::warning('Sesi laporan habis', ['cache_key' => $this->cacheKey]);
            Notification::make()->title('Informasi')
                ->body('Sesi laporan telah habis. Silakan tampilkan laporan kembali.')
                ->warning()->send();
            return;
        }

        // Simpan ke cache
        cache()->put($this->cacheKey . '_summary', $reportData['summary'] ?? [], now()->addMinutes(30));
        cache()->put($this->cacheKey . '_data', $reportData['data'] ?? [], now()->addMinutes(30));
        cache()->put($this->cacheKey . '_notes', $this->data['admin_notes'] ?? '', now()->addMinutes(30));

        Log::info('Data disimpan ke cache terpisah', [
            'summary_size' => count($reportData['summary'] ?? []),
            'data_size' => count($reportData['data'] ?? []),
            'notes' => strlen($this->data['admin_notes'] ?? ''),
        ]);

        // Set default values untuk modal
        $summary = $reportData['summary'] ?? [];
        $ticketCode = $summary['ticket_code'] ?? '-';
        $ticketTitle = $summary['ticket_title'] ?? '-';
        $clientName = $summary['client_name'] ?? '-';
        $dateRange = $summary['date_range'] ?? '-';
        $ticketEmail = $this->getTicketEmail();

        Log::info('Data untuk email', [
            'ticket_code' => $ticketCode,
            'client_name' => $clientName,
            'date_range' => $dateRange,
            'ticket_title' => $summary['ticket_title'] ?? '-',
            'ticket_email' => $ticketEmail,
        ]);

        $this->emailTo = $ticketEmail;

        // SET DEFAULT CC DINAMIS (MULTIPLE CC)
        $this->emailCc = $this->getDefaultCcList();

        $this->emailSubject = "Progress Report – {$ticketCode} - {$ticketTitle} | {$clientName}";
        $this->emailBody = "Yth. Tim / Klien,\n\n";
        $this->emailBody .= "Terlampir progress report untuk:\n";
        $this->emailBody .= "• Tiket    : {$ticketCode} - {$ticketTitle}\n";
        $this->emailBody .= "• Klien    : {$clientName}\n";
        $this->emailBody .= "• Periode  : {$dateRange}\n\n";
        $this->emailBody .= "Silakan hubungi kami jika ada pertanyaan.\n\n";
        $this->emailBody .= "Hormat kami,\n";
        $this->emailBody .= 'Tim Support Nuparis.id';

        // Buka modal
        $this->showEmailModal = true;

        Log::info('Modal email berhasil dibuka', [
            'to' => $this->emailTo,
            'cc' => $this->emailCc,
            'subject' => $this->emailSubject
        ]);
    }

    public function closeEmailModal(): void
    {
        $this->showEmailModal = false;
        $this->isSendingEmail = false;
    }

    public function sendEmail(): void
    {
        if ($this->isSendingEmail) {
            return;
        }

        Log::info('=== MEMULAI PROSES PENGIRIMAN EMAIL ===');
        $this->isSendingEmail = true;

        try {
            $summary = cache()->get($this->cacheKey . '_summary', []);
            $data = cache()->get($this->cacheKey . '_data', []);
            $notes = cache()->get($this->cacheKey . '_notes', '');

            if (empty($data)) {
                throw new \Exception('Data laporan tidak ditemukan');
            }

            // Generate PDF
            $pdfContent = self::renderPdfStatic($summary, $data, $notes);

            // Siapkan data email
            $to = trim($this->emailTo);
            $ccRaw = trim($this->emailCc);
            $subject = trim($this->emailSubject);
            $body = trim($this->emailBody);
            $validCcList = $this->validateCcList($ccRaw);

            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception("Invalid recipient email: {$to}");
            }

            $ticketCode = $summary['ticket_code'] ?? 'report';
            $fileName = 'Progress_Report_' . $ticketCode . '_' . now()->format('Ymd_His') . '.pdf';

            // Kirim email
            Mail::to($to)
                ->cc($validCcList)
                ->send(new ReportExportMail($subject, $body, $pdfContent, $fileName));

            Log::info('Email langsung terkirim');

            Notification::make()
                ->title('✅ Email Berhasil Terkirim!')
                ->success()
                ->send();

            $this->closeEmailModal();
        } catch (\Exception $e) {
            Log::error('❌ Gagal Mengirim Email', ['error' => $e->getMessage()]);

            Notification::make()
                ->title('❌ Gagal Mengirim Email')
                ->body('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isSendingEmail = false;
            Log::info('=== SELESAI PROSES PENGIRIMAN EMAIL ===');
        }
    }

    public function downloadPdf(): void
    {
        if ($this->isLoading) {
            Notification::make()
                ->title('Proses Berjalan')
                ->body('Harap tunggu, sedang memproses...')
                ->warning()
                ->send();
            return;
        }

        $this->isLoading = true;

        try {
            $reportData = cache()->get($this->cacheKey, []);

            if (empty($reportData)) {
                Notification::make()->title('Sesi Habis')
                    ->body('Silakan tampilkan laporan terlebih dahulu.')
                    ->warning()->send();
                return;
            }

            cache()->put($this->cacheKey . '_summary', $reportData['summary'] ?? [], now()->addMinutes(5));
            cache()->put($this->cacheKey . '_data', $reportData['data'] ?? [], now()->addMinutes(5));
            cache()->put($this->cacheKey . '_notes', $this->data['admin_notes'] ?? '', now()->addMinutes(5));

            $this->closeEmailModal();

            $this->dispatch('open-download-url', url: route('report.download', [
                'key' => $this->cacheKey,
            ]));
        } finally {
            $this->isLoading = false;
        }
    }

    protected function getTicketEmail(): string
    {
        $uuid = $this->data['selected_tickets'] ?? null;
        if (empty($uuid)) return '';

        $ticket = Ticket::where('uuid', $uuid)->first();
        if (!$ticket) return '';

        return $ticket->ticket_email ?? $ticket->email ?? $ticket->client_email ?? '';
    }

    // ============================================================
    // MAIN FORM
    // ============================================================

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // ================= FILTER =================
                Section::make('Filter Progress')
                    ->description('Pilih tiket dan periode laporan yang ingin ditampilkan.')
                    ->icon('heroicon-o-funnel')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('selected_tickets')
                                    ->label('Tiket')
                                    ->placeholder('Cari kode, judul, atau nama client...')
                                    ->helperText('Ketik minimal kode tiket, judul, atau nama client.')
                                    ->options(function () {
                                        $result = $this->getTicketOptions();
                                        \Log::info('Ticket options count: ' . count($result));
                                        return $result;
                                    })
                                    ->optionsLimit(1000)
                                    ->searchable()
                                    ->getSearchResultsUsing(fn(?string $search) => $this->getTicketOptions($search))
                                    ->live()
                                    ->columnSpan(fn($get) => !empty($get('selected_tickets')) ? 1 : 2)
                                    ->afterStateUpdated(function () {
                                        $this->clearReport();
                                    }),

                                Select::make('date_range')
                                    ->label('Periode Laporan')
                                    ->placeholder('Pilih periode...')
                                    ->options([
                                        'today' => 'Hari Ini',
                                        'yesterday' => 'Kemarin',
                                        'this_week' => 'Minggu Ini',
                                        'last_week' => 'Minggu Lalu',
                                        'this_month' => 'Bulan Ini',
                                        'last_month' => 'Bulan Lalu',
                                        'this_year' => 'Tahun Ini',
                                        'last_year' => 'Tahun Lalu',
                                        'custom' => 'Kustom',
                                    ])
                                    ->default(null)
                                    ->live()
                                    ->hidden(fn($get) => empty($get('selected_tickets')))
                                    ->afterStateUpdated(function ($state, $get) {
                                        if ($state !== null) {
                                            $this->updateDateRange($state);
                                            $this->populateTicketFields($get('selected_tickets'));
                                        }
                                        $this->clearReport();
                                    }),
                            ])
                            ->columnSpanFull(),

                        // Tanggal kustom: tampil berdampingan hanya saat periode "Kustom" dipilih
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('start_date')
                                    ->label('Tanggal Mulai')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->prefixIcon('heroicon-m-calendar-days')
                                    ->live()
                                    ->afterStateUpdated(fn() => $this->clearReport()),

                                DatePicker::make('end_date')
                                    ->label('Tanggal Akhir')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->prefixIcon('heroicon-m-calendar-days')
                                    ->live()
                                    ->afterStateUpdated(fn() => $this->clearReport()),
                            ])
                            ->columnSpanFull()
                            ->visible(fn($get) => !empty($get('selected_tickets')) && $get('date_range') === 'custom'),

                        // Data laporan: muncul setelah tiket dan periode dipilih
                        Grid::make(2)
                            ->schema([
                                TextInput::make('client_name')
                                    ->label('Nama Client')
                                    ->prefixIcon('heroicon-m-user')
                                    ->placeholder(function ($get) {
                                        $originalName = $this->getOriginalClientName($get('selected_tickets'));
                                        return !empty($originalName) ? $originalName : 'Masukkan nama client...';
                                    })
                                    ->helperText(function ($get) {
                                        $originalName = $this->getOriginalClientName($get('selected_tickets'));
                                        if (!empty($originalName)) {
                                            return 'Diambil dari data tiket.';
                                        }
                                        return 'Data client kosong, silakan isi manual.';
                                    })
                                    ->default(function ($get) {
                                        return $this->getOriginalClientName($get('selected_tickets'));
                                    })
                                    ->columnSpan(1),

                                TextInput::make('proposal_for')
                                    ->label('Proposal For')
                                    ->prefixIcon('heroicon-m-document-text')
                                    ->placeholder('Otomatis terisi dari tiket...')
                                    ->readOnly()
                                    ->columnSpan(1),

                                TextInput::make('proposal_id')
                                    ->label('Proposal ID')
                                    ->prefixIcon('heroicon-m-hashtag')
                                    ->placeholder('Contoh: PRP-2026-001')
                                    ->columnSpan(1),

                                TextInput::make('enquiry')
                                    ->label('Enquiry')
                                    ->prefixIcon('heroicon-m-chat-bubble-left-ellipsis')
                                    ->placeholder('Nomor atau keterangan enquiry')
                                    ->columnSpan(1),

                                TextInput::make('generated_by')
                                    ->label('Dibuat Oleh')
                                    ->prefixIcon('heroicon-m-pencil-square')
                                    ->placeholder('Nama pembuat laporan')
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull()
                            ->visible(fn($get) => !empty($get('selected_tickets')) && !empty($get('date_range'))),
                    ])
                    ->columns(2),

                // ================= SUMMARY =================
                Section::make('Summary')
                    ->description('Tambahkan ringkasan atau keterangan untuk laporan ini.')
                    ->icon('heroicon-o-pencil-square')
                    ->schema([
                        RichEditor::make('admin_notes')
                            ->hiddenLabel()
                            ->placeholder('Tulis summary di sini...')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'bulletList',
                                'orderedList',
                                'h2',
                                'h3',
                                'blockquote',
                                'link',
                                'undo',
                                'redo',
                            ])
                            ->columnSpanFull(),
                    ])
                    ->visible(fn() => $this->hasReport)
                    ->collapsible()
                    ->collapsed(false),
            ])
            ->statePath('data');
    }

    // ============================================================
    // REPORT GENERATION METHODS
    // ============================================================

    public function generateReport(): void
    {
        if ($this->isGeneratingReport) {
            Log::warning('⚠️ GENERATE REPORT SEDANG BERLANGSUNG - Request ditolak');
            Notification::make()
                ->title('⏳ Proses Berjalan')
                ->body('Sedang membuat laporan, harap tunggu...')
                ->warning()
                ->send();
            return;
        }

        $this->isGeneratingReport = true;
        $this->isLoading = true;

        try {
            Log::info('=== GENERATE REPORT START ===');

            $tickets = $this->getFilteredTickets();

            if ($tickets->isEmpty()) {
                Notification::make()->title('Informasi')
                    ->body('Tidak ada data tiket yang sesuai dengan filter yang dipilih.')
                    ->warning()->send();
                return;
            }

            $reportData = $this->buildReport($tickets);

            if (empty($reportData['data'])) {
                Notification::make()->title('Informasi')
                    ->body('Tidak ada progress tiket dalam periode yang dipilih.')
                    ->warning()->send();
                return;
            }

            cache()->put($this->cacheKey, $reportData, now()->addHours(2));
            $this->reportSummary = $reportData['summary'];
            $this->hasReport = true;

            Notification::make()->title('Sukses')
                ->body("Laporan berhasil ditampilkan. Total tiket: {$tickets->count()}")
                ->success()->send();

            Log::info('=== GENERATE REPORT SUCCESS ===', [
                'total_tickets' => $tickets->count(),
                'total_progress' => count($reportData['data']),
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating report', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            Notification::make()->title('Error')
                ->body('Gagal menampilkan laporan: ' . $e->getMessage())
                ->danger()->send();
        } finally {
            $this->isGeneratingReport = false;
            $this->isLoading = false;
        }
    }

    public function resetFilters(): void
    {
        $this->form->fill($this->defaultFormState());
        $this->clearReport();

        Notification::make()->title('Filter Direset')
            ->body('Semua filter telah dikembalikan ke pengaturan awal.')
            ->info()->send();
    }

    protected function clearReport(): void
    {
        $this->hasReport = false;
        $this->reportSummary = [];
        $this->debugInfo = [];
        cache()->forget($this->cacheKey);

        $pdfCacheKeys = cache()->get($this->cacheKey . '_pdf_keys', []);
        foreach ($pdfCacheKeys as $key) {
            cache()->forget($key);
        }
        cache()->forget($this->cacheKey . '_pdf_keys');
    }

    protected function getOriginalClientName(?string $uuid): ?string
    {
        if (empty($uuid)) return null;
        $ticket = Ticket::where('uuid', $uuid)->first();
        return $ticket?->ticket_name_client ?? null;
    }

    protected function populateTicketFields(?string $uuid): void
    {
        if (empty($uuid)) {
            $this->form->fill(array_merge($this->data ?? [], [
                'client_name' => null,
                'proposal_for' => null,
            ]));
            return;
        }

        $ticket = Ticket::where('uuid', $uuid)->first();
        if (!$ticket) return;

        $this->form->fill(array_merge($this->data ?? [], [
            'client_name' => $ticket->ticket_name_client ?? '',
            'proposal_for' => $ticket->ticket_title ?? '-',
        ]));
    }

    protected function getFilteredTickets(): Collection
    {
        $state = $this->form->getState();
        $query = Ticket::query()->orderBy('created_at', 'desc')->limit(1000);

        if (!empty($state['selected_tickets'])) {
            $query->where('uuid', $state['selected_tickets']);
        }

        $query->whereNotNull('ticket_progress')
            ->where('ticket_progress', '!=', '[]')
            ->where('ticket_progress', '!=', 'null');

        return $query->get();
    }

    protected function filterProgressesByDate(array $progresses, array $state): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($state);

        return array_values(array_filter($progresses, function ($p) use ($startDate, $endDate) {
            if (empty($p['timestamp'])) return false;
            return Carbon::parse($p['timestamp'])->between($startDate, $endDate);
        }));
    }

    protected function resolveDateRange(array $state): array
    {
        $range = $state['date_range'] ?? 'custom';

        if ($range !== 'custom') {
            [$start, $end] = match ($range) {
                'today' => [Carbon::today(), Carbon::today()],
                'yesterday' => [Carbon::yesterday(), Carbon::yesterday()],
                'this_week' => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
                'last_week' => [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()],
                'this_month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
                'last_month' => [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()],
                'this_year' => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
                'last_year' => [Carbon::now()->subYear()->startOfYear(), Carbon::now()->subYear()->endOfYear()],
                default => [Carbon::now()->startOfMonth(), Carbon::now()],
            };
            return [$start->startOfDay(), $end->endOfDay()];
        }

        return [
            Carbon::parse($state['start_date'] ?? now()->startOfMonth())->startOfDay(),
            Carbon::parse($state['end_date'] ?? now())->endOfDay(),
        ];
    }

    protected function updateDateRange(?string $range): void
    {
        if ($range === null || $range === 'custom') return;

        [$startDate, $endDate] = match ($range) {
            'today' => [Carbon::today(), Carbon::today()],
            'yesterday' => [Carbon::yesterday(), Carbon::yesterday()],
            'this_week' => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'last_week' => [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()],
            'this_month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'last_month' => [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()],
            'this_year' => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            'last_year' => [Carbon::now()->subYear()->startOfYear(), Carbon::now()->subYear()->endOfYear()],
            default => [Carbon::now()->startOfMonth(), Carbon::now()],
        };

        $this->form->fill(array_merge($this->data, [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
        ]));
    }

    protected function buildReport(Collection $tickets): array
    {
        $state = $this->form->getState();
        $data = $this->groupByTicket($tickets);

        $ticketCode = '-';
        if (!empty($data) && isset($data[0]['ticket_code'])) {
            $ticketCode = $data[0]['ticket_code'];
        }

        $generatedByName = $state['generated_by'] ?? auth()->user()?->name ?? 'System';
        $generatedAt = Carbon::now();
        $generatedByWithTimestamp = $generatedByName . '; ' . $generatedAt->translatedFormat('d F Y H:i');

        return [
            'summary' => [
                'total_tickets' => count($data),
                'ticket_code' => $ticketCode,
                'date_range' => (function () use ($state) {
                    [$start, $end] = $this->resolveDateRange($state);
                    return $start->translatedFormat('d M Y') . ' s/d ' . $end->translatedFormat('d M Y');
                })(),
                'generated_at' => $generatedAt->translatedFormat('d M Y H:i:s'),
                'generated_by' => $generatedByWithTimestamp,
                'generated_by_name' => $generatedByName,
                'generated_timestamp' => $generatedAt->translatedFormat('d F Y H:i'),
                'client_name' => $state['client_name'] ?? '-',
                'ticket_title' => $data[0]['ticket_title'] ?? '-',
                'proposal_id' => $state['proposal_id'] ?? '-',
                'enquiry' => $state['enquiry'] ?? '-',
                'proposal_for' => $state['proposal_for'] ?? '-',
            ],
            'data' => $data,
        ];
    }

    protected function groupByTicket(Collection $tickets): array
    {
        $state = $this->form->getState();

        return $tickets
            ->map(function (Ticket $ticket) use ($state) {
                $allProgresses = $this->parseProgresses($ticket->ticket_progress);
                $filteredProgresses = $this->filterProgressesByDate($allProgresses, $state);
                $clientFiles = $this->extractClientFiles($ticket);

                $progressDocuments = array_map(function ($p) use ($ticket) {
                    return $this->resolveProgressDocument($p, $ticket->ticket_code);
                }, $filteredProgresses);

                $documents = [];

                if (!empty($clientFiles)) {
                    $documents[] = [
                        'text' => '',
                        'text_html' => '',
                        'embedded_images' => [],
                        'thumbnail_files' => [],
                        'other_files' => $clientFiles,
                        'pdf_files' => [],
                        'timestamp' => $ticket->created_at?->toISOString() ?? now()->toISOString(),
                        'is_client_document' => true,
                        'is_progress' => false,
                    ];
                }

                foreach ($progressDocuments as $progressDoc) {
                    $progressDoc['is_progress'] = true;
                    $progressDoc['is_client_document'] = false;
                    $progressDoc['text'] = $progressDoc['text_html'];
                    $documents[] = $progressDoc;
                }

                if (empty($documents)) return null;

                return [
                    'ticket_code' => $ticket->ticket_code,
                    'ticket_title' => $ticket->ticket_title,
                    'status' => $ticket->status ?? '',
                    'created_at' => $ticket->created_at?->format('d/m/Y H:i') ?? '-',
                    'documents_count' => count($progressDocuments),
                    'has_client_files' => !empty($clientFiles),
                    'documents' => $documents,
                ];
            })
            ->filter()
            ->values()
            ->toArray();
    }

    protected function extractClientFiles(Ticket $ticket): array
    {
        $clientFiles = [];

        if (empty($ticket->ticket_document_support)) {
            return $clientFiles;
        }

        $rawClientFiles = $ticket->ticket_document_support;

        if (is_string($rawClientFiles)) {
            $decoded = json_decode($rawClientFiles, true);
            $rawClientFiles = (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
                ? $decoded
                : [$rawClientFiles];
        }

        if (!is_array($rawClientFiles)) {
            $rawClientFiles = [$rawClientFiles];
        }

        $ticketCode = $ticket->ticket_code;

        foreach ($rawClientFiles as $cf) {
            if (empty($cf)) continue;

            $fileName = '';
            if (is_string($cf)) {
                $fileName = basename($cf);
            } elseif (is_array($cf)) {
                $filePath = $cf['path'] ?? $cf['url'] ?? '';
                $fileName = $cf['name'] ?? basename($filePath);
            }

            if (empty($fileName)) continue;

            $localPath = storage_path("app/public/tickets/{$ticketCode}/{$fileName}");
            $fileExists = file_exists($localPath);
            $encodedName = rawurlencode($fileName);
            $url = asset("storage/tickets/{$ticketCode}/{$encodedName}");
            $fileSize = $fileExists ? filesize($localPath) : 0;

            $clientFiles[] = [
                'url' => $url,
                'name' => $fileName,
                'ext' => strtolower(pathinfo($fileName, PATHINFO_EXTENSION)),
                'size_formatted' => $fileSize > 0 ? self::formatFileSize($fileSize) : '',
                'local_path' => $fileExists ? $localPath : null,
                'type' => 'client',
                'file' => $fileName,
                'ticket_code' => $ticketCode,
            ];
        }

        return $clientFiles;
    }

    protected function resolveProgressDocument(array $p, string $ticketCode): array
    {
        $rawHtml = $p['progress'] ?? '';
        $allFiles = is_array($p['file'] ?? []) ? ($p['file'] ?? []) : [$p['file'] ?? ''];

        $videoExt = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', 'flv'];
        $imageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];

        $thumbnailFiles = [];
        $otherFiles = [];
        $pdfFiles = [];

        foreach ($allFiles as $file) {
            if (empty($file)) continue;

            $fileName = basename($file);
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $localPath = storage_path("app/public/tickets/{$ticketCode}/{$fileName}");
            $fileExists = file_exists($localPath);
            $fileSize = $fileExists ? filesize($localPath) : 0;
            $url = asset("storage/tickets/{$ticketCode}/" . rawurlencode($fileName));

            $meta = [
                'file' => $file,
                'url' => $url,
                'name' => $fileName,
                'ext' => $ext,
                'size_bytes' => $fileSize,
                'size_formatted' => $fileSize > 0 ? self::formatFileSize($fileSize) : '',
                'local_path' => $fileExists ? $localPath : null,
                'type' => 'internal',
            ];

            if ($ext === 'pdf') {
                $pdfFiles[] = $meta;
            } elseif (in_array($ext, $videoExt) || (in_array($ext, $imageExt) && $fileSize < 5_000_000)) {
                $thumbnailFiles[] = $meta;
            } else {
                $otherFiles[] = $meta;
            }
        }

        preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $rawHtml, $matches);
        $embeddedImages = [];
        foreach ($matches[1] ?? [] as $imgUrl) {
            $imgName = rawurldecode(basename(parse_url($imgUrl, PHP_URL_PATH)));
            $imgLocal = storage_path("app/public/tickets/{$ticketCode}/{$imgName}");
            if (file_exists($imgLocal)) {
                $embeddedImages[] = [
                    'url' => $imgUrl,
                    'path' => $imgLocal,
                    'name' => basename($imgLocal),
                    'ext' => strtolower(pathinfo($imgLocal, PATHINFO_EXTENSION)),
                ];
            }
        }

        return [
            'text' => $rawHtml,
            'text_html' => $rawHtml,
            'embedded_images' => $embeddedImages,
            'thumbnail_files' => $thumbnailFiles,
            'other_files' => $otherFiles,
            'pdf_files' => $pdfFiles,
            'timestamp' => $p['timestamp'] ?? null,
        ];
    }

    protected function parseProgresses(mixed $raw): array
    {
        if (is_array($raw)) return $raw;
        if (is_string($raw)) return json_decode($raw, true) ?? [];
        return [];
    }

    protected function getTicketOptions(?string $search = null): array
    {
        try {
            $query = Ticket::select('uuid', 'ticket_code', 'ticket_title', 'ticket_name_client', 'created_at')
                ->whereNotNull('uuid')
                ->whereNotNull('ticket_code');

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('ticket_code', 'like', "%{$search}%")
                        ->orWhere('ticket_title', 'like', "%{$search}%")
                        ->orWhere('ticket_name_client', 'like', "%{$search}%");
                });
            }

            $tickets = $query->orderBy('ticket_code')
                ->limit(100)
                ->get();

            $duplicateCodes = $tickets
                ->groupBy('ticket_code')
                ->filter(fn($group) => $group->count() > 1)
                ->keys()
                ->toArray();

            return $tickets
                ->mapWithKeys(function ($t) use ($duplicateCodes) {
                    $label = $t->ticket_code . ' - ' . $t->ticket_title;

                    if (!empty(trim($t->ticket_name_client ?? ''))) {
                        $label .= ' | ' . trim($t->ticket_name_client);
                    }

                    if (in_array($t->ticket_code, $duplicateCodes)) {
                        // ✅ Ganti id dengan uuid
                        $label .= ' (' . ($t->created_at?->format('d/m/Y H:i') ?? '-') . ' #' . $t->uuid . ')';
                    }

                    return [$t->uuid => $label];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error loading ticket options', ['error' => $e->getMessage()]);
            return [];
        }
    }

    protected function defaultFormState(): array
    {
        return [
            'selected_tickets' => null,
            'date_range' => null,
            'start_date' => null,
            'end_date' => null,
            'client_name' => null,
            'proposal_id' => null,
            'enquiry' => null,
            'proposal_for' => null,
            'generated_by' => null,
            'admin_notes' => null,
        ];
    }

    // ============================================================
    // PDF RENDER METHODS
    // ============================================================

    public static function renderPdfStatic(array $summary, array $data, ?string $notes): string
    {
        $attachedPdfs = [];

        foreach ($data as $ticket) {
            $ticketCode = $ticket['ticket_code'] ?? '';

            foreach ($ticket['documents'] ?? [] as $doc) {
                $isClientDoc = $doc['is_client_document'] ?? false;

                if ($isClientDoc) {
                    foreach ($doc['other_files'] ?? [] as $cf) {
                        if (strtolower($cf['ext'] ?? '') !== 'pdf') continue;
                        $localPath = $cf['local_path'] ?? null;
                        if (!$localPath || !file_exists($localPath)) {
                            $fileName = $cf['name'] ?? '';
                            $localPath = $ticketCode && $fileName
                                ? storage_path("app/public/tickets/{$ticketCode}/{$fileName}")
                                : null;
                        }
                        if ($localPath && file_exists($localPath)) {
                            $attachedPdfs[] = [
                                'name' => $cf['name'] ?? basename($localPath),
                                'path' => $localPath,
                                'url' => $cf['url'] ?? '',
                                'ticket_code' => $ticketCode,
                                'type' => 'client_document',
                                'size_formatted' => $cf['size_formatted'] ?? '',
                            ];
                        }
                    }
                    continue;
                }

                foreach ($doc['pdf_files'] ?? [] as $pf) {
                    $localPath = $pf['local_path'] ?? null;
                    if (!$localPath || !file_exists($localPath)) {
                        $fileName = $pf['name'] ?? '';
                        $localPath = $ticketCode && $fileName
                            ? storage_path("app/public/tickets/{$ticketCode}/{$fileName}")
                            : null;
                    }
                    if ($localPath && file_exists($localPath)) {
                        $attachedPdfs[] = [
                            'name' => $pf['name'] ?? basename($localPath),
                            'path' => $localPath,
                            'url' => $pf['url'] ?? '',
                            'ticket_code' => $ticketCode,
                            'type' => 'progress_document',
                            'size_formatted' => $pf['size_formatted'] ?? '',
                        ];
                    }
                }
            }
        }

        $uniquePdfs = [];
        foreach ($attachedPdfs as $pdf) {
            if (!isset($uniquePdfs[$pdf['path']])) {
                $uniquePdfs[$pdf['path']] = $pdf;
            }
        }
        $attachedPdfs = array_values($uniquePdfs);

        $convertedPdfPaths = [];
        foreach ($attachedPdfs as $index => $pdfData) {
            $converted = self::convertPdfWithGhostscript($pdfData['path']);
            $attachedPdfs[$index]['converted_path'] = $converted ?? $pdfData['path'];
            if ($converted) $convertedPdfPaths[] = $converted;
        }

        $html = view('filament.pages.pdf.report', compact('summary', 'data', 'notes', 'attachedPdfs'))->render();
        $html = self::replaceImageUrlsWithLocalPaths($html);

        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');
        $pdf->set_option('isPhpEnabled', true);
        $pdf->set_option('isRemoteEnabled', false);

        $mainPdfPath = tempnam(sys_get_temp_dir(), 'report_main_') . '.pdf';
        file_put_contents($mainPdfPath, $pdf->output());

        $hasValidPdfs = !empty(array_filter($attachedPdfs, fn($p) => file_exists($p['converted_path'])));

        if (!$hasValidPdfs) {
            $output = file_get_contents($mainPdfPath);
            @unlink($mainPdfPath);
            return $output;
        }

        try {
            $fpdi = new \setasign\Fpdi\Fpdi();
            $mainPageCount = $fpdi->setSourceFile($mainPdfPath);

            for ($i = 1; $i <= $mainPageCount; $i++) {
                $tpl = $fpdi->importPage($i);
                $size = $fpdi->getTemplateSize($tpl);
                $fpdi->AddPage($size['orientation'] ?? 'P', [$size['width'], $size['height']]);
                $fpdi->useTemplate($tpl);
            }

            foreach ($attachedPdfs as $pdfData) {
                $pdfPath = $pdfData['converted_path'];
                if (!file_exists($pdfPath)) continue;

                try {
                    $typeLabel = $pdfData['type'] === 'client_document' ? 'Dokumen Client' : 'Dokumen Progress';
                    $pageCount = $fpdi->setSourceFile($pdfPath);

                    for ($p = 1; $p <= $pageCount; $p++) {
                        $tpl = $fpdi->importPage($p);
                        $size = $fpdi->getTemplateSize($tpl);
                        $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                        $pageW = $orientation === 'L' ? 297 : 210;
                        $pageH = $orientation === 'L' ? 210 : 297;

                        $fpdi->AddPage($orientation);

                        $fpdi->SetFillColor(243, 244, 246);
                        $fpdi->Rect(0, 0, $pageW, 13, 'F');
                        $fpdi->SetDrawColor(209, 213, 219);
                        $fpdi->Line(0, 13, $pageW, 13);

                        $fpdi->SetFont('Helvetica', 'B', 7.5);
                        $fpdi->SetTextColor(31, 41, 55);
                        $fpdi->SetXY(10, 3.5);
                        $dispName = strlen($pdfData['name']) > 60 ? substr($pdfData['name'], 0, 57) . '...' : $pdfData['name'];
                        $fpdi->Cell(120, 4, mb_convert_encoding($dispName, 'ISO-8859-1', 'UTF-8'), 0, 0, 'L');

                        $fpdi->SetFont('Helvetica', 'I', 6.5);
                        $fpdi->SetTextColor(107, 114, 128);
                        $fpdi->SetXY($pageW - 80, 3.5);
                        $fpdi->Cell(30, 4, $typeLabel, 0, 0, 'R');

                        $fpdi->SetFont('Helvetica', '', 6.5);
                        $fpdi->SetXY($pageW - 46, 3.5);
                        $fpdi->Cell(36, 4, $pdfData['ticket_code'] . '  ' . $p . '/' . $pageCount, 0, 0, 'R');

                        $margin = 8;
                        $headerH = 15;
                        $availW = $pageW - ($margin * 2);
                        $availH = $pageH - $headerH - $margin;
                        $scale = min($availW / $size['width'], $availH / $size['height'], 1.0);
                        $drawW = $size['width'] * $scale;
                        $drawH = $size['height'] * $scale;
                        $drawX = $margin + (($availW - $drawW) / 2);

                        $fpdi->useTemplate($tpl, $drawX, $headerH, $drawW, $drawH);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to merge PDF', ['name' => $pdfData['name'], 'error' => $e->getMessage()]);
                    self::addErrorPage($fpdi, $pdfData);
                }
            }

            $finalOutput = $fpdi->Output('S');

            @unlink($mainPdfPath);
            foreach ($convertedPdfPaths as $tempFile) {
                @unlink($tempFile);
            }

            return $finalOutput;
        } catch (\Exception $e) {
            Log::error('Fatal error during PDF merge', ['error' => $e->getMessage()]);
            $output = file_get_contents($mainPdfPath);
            @unlink($mainPdfPath);
            return $output;
        }
    }

    protected static function convertPdfWithGhostscript(string $inputPath, ?string $outputPath = null): ?string
    {
        if (!file_exists($inputPath)) return null;

        if (empty($outputPath)) {
            $outputPath = sys_get_temp_dir() . '/gs_converted_' . uniqid() . '.pdf';
        }

        $command = sprintf(
            'gs -dQUIET -dSAFER -dBATCH -dNOPAUSE -dNOPROMPT -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -sOutputFile=%s %s 2>&1',
            escapeshellarg($outputPath),
            escapeshellarg($inputPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode === 0 && file_exists($outputPath) && filesize($outputPath) > 0) {
            return $outputPath;
        }

        Log::error('Ghostscript conversion failed', ['input' => $inputPath, 'return_code' => $returnCode]);
        return null;
    }

    protected static function replaceImageUrlsWithLocalPaths(string $html): string
    {
        $ticketsDir = storage_path('app/public/tickets');

        return preg_replace_callback('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', function ($matches) use ($ticketsDir) {
            $url = $matches[1];
            $fileName = rawurldecode(basename(parse_url($url, PHP_URL_PATH)));

            if (is_dir($ticketsDir)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($ticketsDir, \RecursiveDirectoryIterator::SKIP_DOTS)
                );
                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getFilename() === $fileName) {
                        return str_replace($url, $file->getPathname(), $matches[0]);
                    }
                }
            }

            return $matches[0];
        }, $html);
    }

    protected static function addErrorPage($fpdi, array $att): void
    {
        $fpdi->AddPage();
        $fpdi->SetFont('Helvetica', 'B', 11);
        $fpdi->SetTextColor(185, 28, 28);
        $fpdi->SetY(120);
        $fpdi->Cell(0, 8, 'Gagal memuat lampiran:', 0, 1, 'C');
        $fpdi->SetFont('Helvetica', '', 9);
        $fpdi->SetTextColor(55, 65, 81);
        $fpdi->Cell(0, 6, mb_convert_encoding($att['name'] ?? '-', 'ISO-8859-1', 'UTF-8'), 0, 1, 'C');
    }

    public static function formatFileSize(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    public function getSummaryStats(): array
    {
        if (!$this->hasReport) return [];

        $reportData = cache()->get($this->cacheKey, []);
        $totalDocs = 0;

        foreach ($reportData['data'] ?? [] as $item) {
            $totalDocs += $item['documents_count'] ?? 0;
        }

        return [
            'total_tickets' => $this->reportSummary['total_tickets'] ?? 0,
            'total_documents' => $totalDocs,
        ];
    }
}
