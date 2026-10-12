<x-filament-panels::page>

    <div class="mb-6">
        {{ $this->form }}
    </div>

    @if ($this->canPreview)
        <x-filament::section class="mt-6" icon="heroicon-o-document-text">
            <x-slot name="heading">PDF Preview dengan QR Code</x-slot>
            <x-slot name="description">
                Pilih halaman, drag QR code ke posisi yang tepat, lalu klik <strong>Simpan Posisi</strong>.
            </x-slot>

            {{-- URL Info --}}
            <div
                class="mb-4 p-3 bg-blue-50 dark:bg-blue-950 rounded-lg text-xs space-y-1 border border-blue-100 dark:border-blue-900">
                <div><strong class="text-blue-700 dark:text-blue-300">PDF:</strong>
                    <a href="{{ $this->pdfUrl }}" target="_blank"
                        class="text-blue-600 dark:text-blue-400 hover:underline break-all">{{ $this->pdfUrl }}</a>
                </div>
                <div><strong class="text-blue-700 dark:text-blue-300">QR:</strong>
                    <a href="{{ $this->qrUrl }}" target="_blank"
                        class="text-blue-600 dark:text-blue-400 hover:underline break-all">{{ $this->qrUrl }}</a>
                </div>
            </div>

            {{-- Page Info Bar --}}
            <div id="page-info-bar"
                class="hidden mb-3 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                        Halaman
                        <span id="current-page-display"
                            class="text-primary-600 dark:text-primary-400 font-bold text-base">1</span>
                        dari
                        <span id="total-pages-display" class="font-bold text-base">?</span>
                    </div>

                    {{-- Nav buttons --}}
                    <div class="flex items-center gap-1 ml-auto">
                        <button id="btn-first-page" title="Halaman pertama"
                            class="nav-btn p-1.5 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-primary-50 dark:hover:bg-gray-700 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                            </svg>
                        </button>
                        <button id="btn-prev-page" title="Sebelumnya"
                            class="nav-btn p-1.5 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-primary-50 dark:hover:bg-gray-700 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>

                        <input id="page-jump-input" type="number" min="1" value="1"
                            class="w-16 text-center text-sm py-1.5 px-1 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all"
                            title="Ketik nomor halaman lalu tekan Enter" />

                        <button id="btn-next-page" title="Berikutnya"
                            class="nav-btn p-1.5 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-primary-50 dark:hover:bg-gray-700 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                        <button id="btn-last-page" title="Halaman terakhir"
                            class="nav-btn p-1.5 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-primary-50 dark:hover:bg-gray-700 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>

                    {{-- Zoom --}}
                    <div class="flex items-center gap-1 border-l border-gray-300 dark:border-gray-600 pl-3">
                        <button id="btn-zoom-out" title="Perkecil"
                            class="p-1.5 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-primary-50 dark:hover:bg-gray-700 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7" />
                            </svg>
                        </button>
                        <span id="zoom-level-display"
                            class="text-xs font-bold text-gray-600 dark:text-gray-400 w-12 text-center tabular-nums">100%</span>
                        <button id="btn-zoom-in" title="Perbesar"
                            class="p-1.5 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-primary-50 dark:hover:bg-gray-700 transition-all">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                            </svg>
                        </button>
                        <button id="btn-zoom-reset"
                            class="text-xs px-2 py-1.5 rounded border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-primary-50 dark:hover:bg-gray-700 transition-all">
                            Reset
                        </button>
                    </div>
                </div>
            </div>

            {{-- Thumbnail Strip --}}
            <div id="thumbnail-strip-wrapper"
                class="hidden mb-4 p-3 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-widest">Semua
                        Halaman</span>
                    <span id="thumb-loading-badge"
                        class="text-xs px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-300 font-medium">Memuat…</span>
                    <button id="toggle-thumbnails"
                        class="ml-auto text-xs px-2 py-0.5 rounded bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600 transition-all">
                        Sembunyikan
                    </button>
                </div>
                <div id="thumbnail-strip" class="flex gap-2 overflow-x-auto pb-2 scroll-smooth"
                    style="scrollbar-width: thin; min-height: 130px;"></div>
            </div>

            {{-- Loading --}}
            <div id="pdf-loading-overlay"
                class="hidden flex items-center justify-center py-20 bg-gray-50 dark:bg-gray-900 rounded-lg border border-dashed border-gray-300 dark:border-gray-600">
                <div class="text-center">
                    <svg class="animate-spin w-10 h-10 text-primary-500 mx-auto mb-3" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4" />
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    <p id="loading-text" class="text-sm font-semibold text-gray-600 dark:text-gray-300">Memuat PDF…
                    </p>
                    <p id="loading-subtext"
                        class="text-xs text-gray-400 dark:text-gray-500 mt-1 break-all max-w-xs mx-auto"></p>
                </div>
            </div>

            {{-- Canvas --}}
            <div class="bg-gray-200 dark:bg-gray-950 p-4 rounded-lg border border-gray-300 dark:border-gray-700 overflow-auto"
                style="max-height: 82vh;">
                <div id="pdf-container" class="relative inline-block shadow-2xl"
                    style="min-width: 100px; min-height: 100px;">
                    <canvas id="pdf-canvas"
                        class="block bg-white border border-gray-400 dark:border-gray-600"></canvas>

                    {{-- QR: posisi diatur HANYA lewat JS, tidak ada inline top/left --}}
                    <img id="qr-overlay" src="{{ $this->qrUrl }}" alt="QR Code"
                        class="absolute cursor-move select-none"
                        style="width:100px;height:100px;top:0;left:0;z-index:10;
                               border:2px dashed #3b82f6;background:white;padding:4px;
                               box-shadow:0 2px 12px rgba(0,0,0,0.3);pointer-events:auto;
                               transform:translate(0px,0px);">

                    <div id="page-render-indicator"
                        class="hidden absolute inset-0 flex items-center justify-center z-20"
                        style="background:rgba(255,255,255,0.55);">
                        <div class="bg-white dark:bg-gray-800 rounded-xl px-5 py-3 shadow-xl flex items-center gap-3">
                            <svg class="animate-spin w-5 h-5 text-primary-500 shrink-0" fill="none"
                                viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">Merender
                                halaman…</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- QR Notice --}}
            <div id="page-selected-notice"
                class="hidden mt-3 p-2.5 bg-amber-50 dark:bg-amber-950 border border-amber-200 dark:border-amber-800 rounded-lg flex items-center gap-2 text-sm text-amber-700 dark:text-amber-300">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                QR code akan ditempelkan di halaman <strong id="qr-target-page" class="font-bold mx-1">1</strong>.
                Pastikan ini halaman yang benar sebelum menyimpan.
            </div>
        </x-filament::section>

        {{-- Control Bar --}}
        <div class="mt-4 flex flex-wrap items-center gap-3">
            <button id="save-position-btn" type="button"
                class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                </svg>
                Simpan Posisi
            </button>
            <button id="reset-position-btn" type="button"
                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Reset Posisi QR
            </button>
            <button id="refresh-debug-btn" type="button" wire:click="refreshDebug"
                class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-500 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2 transition shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Debug
            </button>

            {{-- QR Size --}}
            <div class="flex items-center gap-2 ml-auto border-l border-gray-300 dark:border-gray-600 pl-4">
                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 whitespace-nowrap">Ukuran
                    QR:</label>
                <input id="qr-size-slider" type="range" min="40" max="300" value="100"
                    class="w-24 accent-primary-600" />
                <span id="qr-size-display"
                    class="text-xs tabular-nums text-gray-600 dark:text-gray-400 w-16 font-mono">100 × 100</span>
            </div>
        </div>

        {{-- Status bar --}}
        <div class="mt-3 flex flex-wrap gap-4 text-xs text-gray-500 dark:text-gray-400 font-mono">
            <span>Posisi: <strong id="current-position" class="text-gray-700 dark:text-gray-200">X=0,
                    Y=0</strong></span>
            <span>Rasio: <strong id="ratio-display" class="text-gray-500 dark:text-gray-400">rx=0.50,
                    ry=0.50</strong></span>
            <span>QR di halaman: <strong id="qr-page-indicator"
                    class="text-primary-600 dark:text-primary-400">—</strong></span>
            <span class="italic text-gray-400 dark:text-gray-600">← → halaman &nbsp;|&nbsp; Ctrl+/- zoom</span>
        </div>
    @else
        <x-filament::section class="mt-6">
            <x-slot name="heading">Preview Tidak Tersedia</x-slot>
            <div class="text-center py-10">
                <div class="text-gray-500 dark:text-gray-400 mb-5 text-sm">
                    @if (!$this->selectedFile)
                        <p>Silakan pilih file PDF dari dropdown di atas.</p>
                    @elseif (!$this->hasPdf)
                        <p>PDF URL tidak tersedia. Coba generate preview lagi.</p>
                    @elseif (!$this->hasQr)
                        <p>QR Code belum di-generate. Coba generate preview lagi.</p>
                    @else
                        <p>Terjadi masalah tidak diketahui.</p>
                    @endif
                </div>
                @if ($this->selectedFile)
                    <button wire:click="generatePreview" type="button"
                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition">
                        Generate Preview
                    </button>
                @endif
            </div>
        </x-filament::section>
    @endif

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
        <script>
            pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
        </script>
        <script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>

        <script>
            // ── Livewire helper ────────────────────────────────────────────────────
            function dispatchToLivewire(eventName, eventData) {
                if (window.Livewire && typeof window.Livewire.dispatch === 'function') {
                    try {
                        window.Livewire.dispatch(eventName, eventData);
                        return;
                    } catch (_) {}
                }
                if (typeof Livewire !== 'undefined' && typeof Livewire.dispatch === 'function') {
                    try {
                        Livewire.dispatch(eventName, eventData);
                        return;
                    } catch (_) {}
                }
                document.dispatchEvent(new CustomEvent(eventName, {
                    detail: eventData,
                    bubbles: true
                }));
            }

            (function() {
                'use strict';

                // ── State ─────────────────────────────────────────────────────────
                var pdfDoc = null;
                var currentPage = 1;
                var totalPages = 0;
                var renderScale = 1.5;
                var BASE_SCALE = 1.5;
                var isRendering = false;
                var pendingPage = null;
                var currentPdfUrl = null;
                var qrDragInst = null;
                var thumbsVisible = true;
                var thumbsDone = 0;

                // ── State loading saat simpan ─────────────────────────────────────
                var isSaving = false;
                var savingTimeout = null;

                // ── QR state: simpan sebagai RASIO (0..1) bukan pixel ─────────────
                // Ini kunci agar QR ikut zoom: konversi rasio → pixel saat apply
                var qrRatioX = 0.5; // posisi tengah secara default
                var qrRatioY = 0.5;
                var qrSize = 100; // pixel, di-scale bareng canvas

                var $ = function(id) {
                    return document.getElementById(id);
                };

                // ── Init ──────────────────────────────────────────────────────────
                function init() {
                    bindAllButtons();
                    bindLivewireEvents();
                    bindKeyboard();

                    var initialUrl = @json($this->pdfUrl ?? '');
                    if (initialUrl && initialUrl !== 'null' && initialUrl !== '') {
                        loadPdf(initialUrl);
                    }
                }

                // ── Event Delegation ──────────────────────────────────────────────
                function bindAllButtons() {
                    document.addEventListener('click', function(e) {
                        var btn = e.target.closest('[id]');
                        if (!btn) return;
                        switch (btn.id) {
                            case 'btn-first-page':
                                e.preventDefault();
                                goToPage(1);
                                break;
                            case 'btn-prev-page':
                                e.preventDefault();
                                goToPage(currentPage - 1);
                                break;
                            case 'btn-next-page':
                                e.preventDefault();
                                goToPage(currentPage + 1);
                                break;
                            case 'btn-last-page':
                                e.preventDefault();
                                goToPage(totalPages);
                                break;
                            case 'btn-zoom-in':
                                e.preventDefault();
                                applyZoom(renderScale + 0.25);
                                break;
                            case 'btn-zoom-out':
                                e.preventDefault();
                                applyZoom(renderScale - 0.25);
                                break;
                            case 'btn-zoom-reset':
                                e.preventDefault();
                                applyZoom(BASE_SCALE);
                                break;
                            case 'save-position-btn':
                                e.preventDefault();
                                saveQrPosition();
                                break;
                            case 'reset-position-btn':
                                e.preventDefault();
                                resetQrToCenter();
                                break;
                            case 'toggle-thumbnails':
                                e.preventDefault();
                                toggleThumbnails();
                                break;
                        }
                        var thumbItem = e.target.closest('.thumb-item');
                        if (thumbItem && thumbItem.dataset.page) {
                            goToPage(parseInt(thumbItem.dataset.page));
                        }
                    });

                    document.addEventListener('keydown', function(e) {
                        if (e.target && e.target.id === 'page-jump-input' && e.key === 'Enter') {
                            e.preventDefault();
                            goToPage(parseInt(e.target.value) || 1);
                        }
                    });
                    document.addEventListener('change', function(e) {
                        if (e.target && e.target.id === 'page-jump-input') {
                            goToPage(parseInt(e.target.value) || 1);
                        }
                    });
                    document.addEventListener('input', function(e) {
                        if (e.target && e.target.id === 'qr-size-slider') {
                            qrSize = parseInt(e.target.value);
                            var display = $('qr-size-display');
                            if (display) display.textContent = qrSize + ' × ' + qrSize;
                            // Terapkan ukuran baru + reposisi berdasarkan rasio yang tersimpan
                            applyQrFromRatio();
                        }
                    });
                }

                // ── Livewire Events ───────────────────────────────────────────────
                function bindLivewireEvents() {
                    window.addEventListener('preview-updated', function(e) {
                        var data = Array.isArray(e.detail) ? e.detail[0] : e.detail;
                        if (data && data.pdfUrl && data.pdfUrl !== currentPdfUrl) loadPdf(data.pdfUrl);
                        if (data && data.qrUrl) {
                            var qrEl = $('qr-overlay');
                            if (qrEl && qrEl.src !== data.qrUrl) qrEl.src = data.qrUrl;
                        }
                    });
                    window.addEventListener('preview-cleared', resetAll);

                    // Proses simpan selesai (berhasil / gagal) → sembunyikan overlay loading
                    window.addEventListener('qr-position-saved', hideSaving);
                }

                // ── Overlay loading saat simpan ───────────────────────────────────
                // Dibuat lewat JS & ditempel ke <body> supaya tidak ikut ter-render
                // ulang / hilang saat Livewire memperbarui DOM komponen.
                function getSavingOverlay() {
                    var el = $('qr-saving-overlay');
                    if (el) return el;

                    el = document.createElement('div');
                    el.id = 'qr-saving-overlay';
                    el.className = 'qr-saving-overlay';
                    el.setAttribute('role', 'alert');
                    el.setAttribute('aria-busy', 'true');
                    el.innerHTML =
                        '<div class="qr-saving-card">' +
                        '<div class="qr-saving-spinner"></div>' +
                        '<div class="qr-saving-title">Menyimpan posisi QR…</div>' +
                        '<div class="qr-saving-desc">Memproses dokumen, mohon tunggu dan jangan tutup halaman ini.</div>' +
                        '</div>';
                    document.body.appendChild(el);
                    return el;
                }

                function showSaving() {
                    isSaving = true;
                    getSavingOverlay().style.display = 'flex';
                    // Pengaman: sembunyikan otomatis kalau server tidak merespons
                    clearTimeout(savingTimeout);
                    savingTimeout = setTimeout(hideSaving, 180000);
                }

                function hideSaving() {
                    isSaving = false;
                    clearTimeout(savingTimeout);
                    var el = $('qr-saving-overlay');
                    if (el) el.style.display = 'none';
                }

                // ── Load PDF ──────────────────────────────────────────────────────
                function loadPdf(url) {
                    currentPdfUrl = url;
                    currentPage = 1;
                    totalPages = 0;
                    thumbsDone = 0;
                    pdfDoc = null;
                    // Reset rasio ke tengah saat PDF baru dimuat
                    qrRatioX = 0.5;
                    qrRatioY = 0.5;

                    showLoading('Memuat PDF…', url.split('/').pop());
                    hideThumbnailStrip();
                    hidePageInfoBar();

                    pdfjsLib.getDocument({
                        url: url,
                        cMapUrl: 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/cmaps/',
                        cMapPacked: true,
                        verbosity: 0
                    }).promise.then(function(pdf) {
                        pdfDoc = pdf;
                        totalPages = pdf.numPages;
                        hideLoading();
                        showPageInfoBar();
                        updatePageUI();
                        return renderPage(1);
                    }).then(function() {
                        setupQrDrag();
                        generateThumbnails();
                        showThumbnailStrip();
                    }).catch(function(err) {
                        console.error('[PDF] Load error:', err);
                        hideLoading();
                    });
                }

                // ── Render Halaman ────────────────────────────────────────────────
                function renderPage(pageNum) {
                    if (!pdfDoc) return Promise.resolve();
                    if (isRendering) {
                        pendingPage = pageNum;
                        return Promise.resolve();
                    }

                    isRendering = true;
                    currentPage = Math.max(1, Math.min(pageNum, totalPages));
                    updatePageUI();
                    showPageSpinner();

                    return pdfDoc.getPage(currentPage).then(function(page) {
                        var vp = page.getViewport({
                            scale: renderScale
                        });
                        var canvas = $('pdf-canvas');
                        var ctx = canvas.getContext('2d');
                        canvas.width = vp.width;
                        canvas.height = vp.height;
                        return page.render({
                            canvasContext: ctx,
                            viewport: vp
                        }).promise;
                    }).then(function() {
                        isRendering = false;
                        hidePageSpinner();
                        highlightThumb(currentPage);

                        // Update notice
                        var notice = $('page-selected-notice');
                        if (notice) notice.classList.remove('hidden');
                        var tgt = $('qr-target-page');
                        if (tgt) tgt.textContent = currentPage;
                        var ind = $('qr-page-indicator');
                        if (ind) ind.textContent = 'Halaman ' + currentPage;

                        // ── KUNCI: setelah canvas di-render ulang (zoom/ganti halaman),
                        //    terapkan ulang posisi QR dari rasio yang tersimpan
                        applyQrFromRatio();

                        if (pendingPage !== null && pendingPage !== currentPage) {
                            var next = pendingPage;
                            pendingPage = null;
                            return renderPage(next);
                        }
                    }).catch(function(err) {
                        isRendering = false;
                        hidePageSpinner();
                        console.error('[PDF] Render error:', err);
                    });
                }

                // ── Navigasi ──────────────────────────────────────────────────────
                function goToPage(page) {
                    if (!pdfDoc) return;
                    page = Math.max(1, Math.min(page, totalPages));
                    if (page === currentPage && pendingPage === null) return;
                    renderPage(page);
                    // TIDAK reset posisi QR saat ganti halaman — rasio tetap sama
                }

                function updatePageUI() {
                    var curEl = $('current-page-display');
                    var totEl = $('total-pages-display');
                    var input = $('page-jump-input');
                    if (curEl) curEl.textContent = currentPage;
                    if (totEl) totEl.textContent = totalPages || '?';
                    if (input) {
                        input.value = currentPage;
                        input.max = totalPages;
                    }

                    setNavDisabled($('btn-first-page'), currentPage <= 1);
                    setNavDisabled($('btn-prev-page'), currentPage <= 1);
                    setNavDisabled($('btn-next-page'), currentPage >= totalPages);
                    setNavDisabled($('btn-last-page'), currentPage >= totalPages);
                }

                function setNavDisabled(btn, disabled) {
                    if (!btn) return;
                    if (disabled) {
                        btn.setAttribute('disabled', 'disabled');
                        btn.classList.add('opacity-40', 'cursor-not-allowed');
                    } else {
                        btn.removeAttribute('disabled');
                        btn.classList.remove('opacity-40', 'cursor-not-allowed');
                    }
                }

                // ── QR: Posisi berbasis RASIO ─────────────────────────────────────
                //
                // Alur:
                //  drag  → catat pixel X,Y → hitung rasio (X/canvasW, Y/canvasH) → simpan
                //  zoom  → render ulang canvas (ukuran berubah) → applyQrFromRatio()
                //           → pixel baru = rasio × canvasW/H baru → terapkan ke QR
                //
                // Dengan cara ini QR selalu berada di posisi RELATIF yang sama
                // terhadap dokumen, tidak peduli skala zoom.

                function applyQrFromRatio() {
                    var canvas = $('pdf-canvas');
                    var qr = $('qr-overlay');
                    if (!canvas || !qr) return;

                    var cw = canvas.width || canvas.offsetWidth || 600;
                    var ch = canvas.height || canvas.offsetHeight || 800;

                    // Hitung ukuran QR proporsional terhadap zoom
                    // qrSize adalah ukuran "base" saat zoom 100% (BASE_SCALE)
                    var scaledQrSize = Math.round(qrSize * (renderScale / BASE_SCALE));
                    qr.style.width = scaledQrSize + 'px';
                    qr.style.height = scaledQrSize + 'px';

                    // Konversi rasio → pixel, clamp supaya tidak keluar canvas
                    var px = Math.max(0, Math.min(qrRatioX * cw, cw - scaledQrSize));
                    var py = Math.max(0, Math.min(qrRatioY * ch, ch - scaledQrSize));

                    qr.style.transform = 'translate(' + px + 'px,' + py + 'px)';
                    qr.setAttribute('data-x', px);
                    qr.setAttribute('data-y', py);

                    updatePositionDisplay(px, py, scaledQrSize);
                }

                function resetQrToCenter() {
                    qrRatioX = 0.5;
                    qrRatioY = 0.5;
                    applyQrFromRatio();
                }

                function setupQrDrag() {
                    var qr = $('qr-overlay');
                    var canvas = $('pdf-canvas');
                    if (!qr || !canvas) return;

                    if (qrDragInst) {
                        qrDragInst.unset();
                        qrDragInst = null;
                    }

                    // Terapkan posisi awal dari rasio
                    applyQrFromRatio();

                    qrDragInst = interact(qr).draggable({
                        listeners: {
                            start: function(e) {
                                e.target.style.opacity = '0.75';
                            },
                            move: function(e) {
                                var t = e.target;
                                var cw = canvas.width || canvas.offsetWidth || 600;
                                var ch = canvas.height || canvas.offsetHeight || 800;
                                var scaledQrSize = parseInt(t.style.width) || qrSize;

                                var x = (parseFloat(t.getAttribute('data-x')) || 0) + e.dx;
                                var y = (parseFloat(t.getAttribute('data-y')) || 0) + e.dy;

                                // Clamp dalam batas canvas
                                x = Math.max(0, Math.min(x, cw - scaledQrSize));
                                y = Math.max(0, Math.min(y, ch - scaledQrSize));

                                // ── Simpan sebagai rasio setiap kali drag ────────
                                qrRatioX = x / cw;
                                qrRatioY = y / ch;

                                t.style.transform = 'translate(' + x + 'px,' + y + 'px)';
                                t.setAttribute('data-x', x);
                                t.setAttribute('data-y', y);

                                updatePositionDisplay(x, y, scaledQrSize);
                            },
                            end: function(e) {
                                e.target.style.opacity = '1';
                            }
                        },
                        modifiers: []
                    });
                }

                function saveQrPosition() {
                    // Cegah klik ganda selama proses berjalan
                    if (isSaving) return;

                    var qr = $('qr-overlay');
                    var canvas = $('pdf-canvas');
                    if (!qr || !canvas) return;

                    // Tampilkan overlay loading
                    showSaving();

                    var x = parseFloat(qr.getAttribute('data-x')) || 0;
                    var y = parseFloat(qr.getAttribute('data-y')) || 0;
                    var scaledQrSize = parseInt(qr.style.width) || qrSize;

                    var rect = qr.getBoundingClientRect();

                    dispatchToLivewire('save-qr-position', {
                        x: x,
                        y: canvas.height - y - scaledQrSize, // pixel absolut
                        ratioX: qrRatioX, // rasio yang dipakai backend
                        ratioY: qrRatioY,
                        scale: rect.width,
                        page: currentPage,
                        canvasWidth: canvas.width,
                        canvasHeight: canvas.height,
                        renderScale: renderScale,
                    });
                }

                function updatePositionDisplay(x, y, size) {
                    var canvas = $('pdf-canvas');
                    var el = $('current-position');
                    var ratioEl = $('ratio-display');
                    if (!el) return;
                    size = size || qrSize;
                    var pdfY = canvas ? (canvas.height - y - size) : 0;
                    el.textContent = 'X=' + Math.round(x) + ', Y=' + Math.round(y) +
                        ' → PDF X=' + Math.round(x) + ', Y=' + Math.round(pdfY);
                    if (ratioEl) {
                        ratioEl.textContent = 'rx=' + qrRatioX.toFixed(3) + ', ry=' + qrRatioY.toFixed(3);
                    }
                }

                // ── Zoom ──────────────────────────────────────────────────────────
                function applyZoom(scale) {
                    renderScale = Math.max(0.5, Math.min(4.0, scale));
                    var pct = Math.round(renderScale / BASE_SCALE * 100);
                    var disp = $('zoom-level-display');
                    if (disp) disp.textContent = pct + '%';
                    // renderPage akan memanggil applyQrFromRatio() setelah canvas dirender
                    if (pdfDoc) renderPage(currentPage);
                }

                // ── Thumbnails ────────────────────────────────────────────────────
                function generateThumbnails() {
                    var strip = $('thumbnail-strip');
                    if (!strip || !pdfDoc) return;

                    strip.innerHTML = '';
                    thumbsDone = 0;
                    var badge = $('thumb-loading-badge');
                    if (badge) badge.classList.remove('hidden');

                    for (var i = 1; i <= totalPages; i++) {
                        (function(pageNum) {
                            var wrapper = document.createElement('div');
                            wrapper.className =
                                'thumb-item flex-shrink-0 flex flex-col items-center gap-1 cursor-pointer';
                            wrapper.dataset.page = pageNum;

                            var tc = document.createElement('canvas');
                            tc.id = 'thumb-' + pageNum;
                            tc.style.cssText =
                                'display:block;width:70px;border:2px solid transparent;border-radius:6px;transition:all 0.15s ease;background:#fff;';

                            var lbl = document.createElement('span');
                            lbl.textContent = pageNum;
                            lbl.className = 'text-xs text-gray-400 dark:text-gray-500 font-medium';

                            wrapper.appendChild(tc);
                            wrapper.appendChild(lbl);
                            strip.appendChild(wrapper);

                            pdfDoc.getPage(pageNum).then(function(page) {
                                var vp = page.getViewport({
                                    scale: 0.25
                                });
                                tc.width = vp.width;
                                tc.height = vp.height;
                                page.render({
                                    canvasContext: tc.getContext('2d'),
                                    viewport: vp
                                }).promise.then(function() {
                                    thumbsDone++;
                                    if (thumbsDone >= totalPages && badge) badge.classList.add(
                                        'hidden');
                                });
                            });
                        })(i);
                    }
                    highlightThumb(currentPage);
                }

                function highlightThumb(page) {
                    document.querySelectorAll('.thumb-item').forEach(function(w) {
                        var active = parseInt(w.dataset.page) === page;
                        var tc = w.querySelector('canvas');
                        var lbl = w.querySelector('span');
                        if (tc) {
                            tc.style.borderColor = active ? '#3b82f6' : 'transparent';
                            tc.style.boxShadow = active ? '0 0 0 1px #3b82f6' : 'none';
                            tc.style.opacity = active ? '1' : '0.55';
                        }
                        if (lbl) {
                            lbl.style.color = active ? '#3b82f6' : '';
                            lbl.style.fontWeight = active ? '700' : '';
                        }
                        if (active) w.scrollIntoView({
                            behavior: 'smooth',
                            block: 'nearest',
                            inline: 'center'
                        });
                    });
                }

                function toggleThumbnails() {
                    thumbsVisible = !thumbsVisible;
                    var strip = $('thumbnail-strip');
                    var btn = $('toggle-thumbnails');
                    if (strip) strip.style.display = thumbsVisible ? '' : 'none';
                    if (btn) btn.textContent = thumbsVisible ? 'Sembunyikan' : 'Tampilkan';
                }

                // ── Keyboard ──────────────────────────────────────────────────────
                function bindKeyboard() {
                    document.addEventListener('keydown', function(e) {
                        var tag = e.target && e.target.tagName;
                        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
                        if (e.key === 'ArrowRight' || e.key === 'ArrowDown') goToPage(currentPage + 1);
                        if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') goToPage(currentPage - 1);
                        if (e.key === 'Home') goToPage(1);
                        if (e.key === 'End') goToPage(totalPages);
                        if ((e.ctrlKey || e.metaKey) && (e.key === '+' || e.key === '=')) {
                            e.preventDefault();
                            applyZoom(renderScale + 0.25);
                        }
                        if ((e.ctrlKey || e.metaKey) && e.key === '-') {
                            e.preventDefault();
                            applyZoom(renderScale - 0.25);
                        }
                        if ((e.ctrlKey || e.metaKey) && e.key === '0') {
                            e.preventDefault();
                            applyZoom(BASE_SCALE);
                        }
                    });
                }

                // ── UI Helpers ────────────────────────────────────────────────────
                function showLoading(msg, sub) {
                    var ov = $('pdf-loading-overlay');
                    if (ov) ov.classList.remove('hidden');
                    var t = $('loading-text');
                    if (t) t.textContent = msg || 'Memuat…';
                    var s = $('loading-subtext');
                    if (s) s.textContent = sub || '';
                    var c = $('pdf-canvas');
                    if (c) {
                        c.width = 0;
                        c.height = 0;
                    }
                }

                function hideLoading() {
                    var o = $('pdf-loading-overlay');
                    if (o) o.classList.add('hidden');
                }

                function showPageInfoBar() {
                    var b = $('page-info-bar');
                    if (b) b.classList.remove('hidden');
                }

                function hidePageInfoBar() {
                    var b = $('page-info-bar');
                    if (b) b.classList.add('hidden');
                }

                function showThumbnailStrip() {
                    var w = $('thumbnail-strip-wrapper');
                    if (w) w.classList.remove('hidden');
                }

                function hideThumbnailStrip() {
                    var w = $('thumbnail-strip-wrapper');
                    if (w) w.classList.add('hidden');
                }

                function showPageSpinner() {
                    var s = $('page-render-indicator');
                    if (s) s.classList.remove('hidden');
                }

                function hidePageSpinner() {
                    var s = $('page-render-indicator');
                    if (s) s.classList.add('hidden');
                }

                function resetAll() {
                    pdfDoc = null;
                    currentPage = 1;
                    totalPages = 0;
                    currentPdfUrl = null;
                    thumbsDone = 0;
                    qrRatioX = 0.5;
                    qrRatioY = 0.5;
                    var c = $('pdf-canvas');
                    if (c) {
                        c.getContext('2d').clearRect(0, 0, c.width, c.height);
                        c.width = 0;
                        c.height = 0;
                    }
                    var strip = $('thumbnail-strip');
                    if (strip) strip.innerHTML = '';
                    if (qrDragInst) {
                        qrDragInst.unset();
                        qrDragInst = null;
                    }
                    hidePageInfoBar();
                    hideThumbnailStrip();
                }

                // ── Bootstrap ─────────────────────────────────────────────────────
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', init);
                } else {
                    init();
                }
                document.addEventListener('livewire:navigated', function() {
                    setTimeout(init, 150);
                });

            })();
        </script>

        <style>
            #thumbnail-strip::-webkit-scrollbar {
                height: 4px;
            }

            #thumbnail-strip::-webkit-scrollbar-track {
                background: transparent;
            }

            #thumbnail-strip::-webkit-scrollbar-thumb {
                background: #94a3b8;
                border-radius: 9999px;
            }

            .thumb-item canvas {
                transition: border-color .15s, box-shadow .15s, opacity .15s;
            }

            .thumb-item:hover canvas {
                opacity: 1 !important;
                border-color: #93c5fd !important;
            }

            #qr-overlay:hover {
                box-shadow: 0 4px 20px rgba(59, 130, 246, 0.45) !important;
            }

            /* Spinner overlay "Menyimpan posisi QR" */
            @keyframes qr-spin {
                to {
                    transform: rotate(360deg);
                }
            }

            .qr-saving-overlay {
                position: fixed;
                inset: 0;
                z-index: 9999;
                display: none;
                align-items: center;
                justify-content: center;
                background: rgba(17, 24, 39, .45);
                backdrop-filter: blur(2px);
            }

            .qr-saving-card {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 14px;
                max-width: 320px;
                padding: 24px 32px;
                text-align: center;
                border-radius: 14px;
                background: #ffffff;
                border: 1px solid #e5e7eb;
                box-shadow: 0 20px 50px rgba(0, 0, 0, .25);
            }

            .qr-saving-title {
                font-size: 14px;
                font-weight: 700;
                color: #111827;
            }

            .qr-saving-desc {
                font-size: 12px;
                color: #6b7280;
            }

            .qr-saving-spinner {
                width: 44px;
                height: 44px;
                border: 4px solid #e5e7eb;
                border-top-color: #3b82f6;
                border-radius: 9999px;
                animation: qr-spin .8s linear infinite;
            }

            /* Mode gelap (Filament memakai class "dark" pada <html>) */
            .dark .qr-saving-overlay {
                background: rgba(0, 0, 0, .65);
            }

            .dark .qr-saving-card {
                background: #1f2937;
                border-color: #374151;
                box-shadow: 0 20px 50px rgba(0, 0, 0, .6);
            }

            .dark .qr-saving-title {
                color: #f9fafb;
            }

            .dark .qr-saving-desc {
                color: #9ca3af;
            }

            .dark .qr-saving-spinner {
                border-color: #374151;
                border-top-color: #60a5fa;
            }
        </style>
    @endpush

</x-filament-panels::page>
