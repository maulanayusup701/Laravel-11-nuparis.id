@php
    use App\Models\Ticket;
    use Illuminate\Support\Str;
    use Illuminate\Support\Facades\Storage;

    $ticket = isset($getRecord) ? $getRecord() : null;
    $ticket ??= Ticket::where('ticket_code', request()->route('record'))->first();

    $progress = $ticket?->ticket_progress ?? [];
    $isClosed = $ticket?->ticketStatus?->ticket_status_name === 'close';

    if (!function_exists('formatFileSize')) {
        function formatFileSize($bytes)
        {
            if ($bytes <= 0) {
                return '';
            }
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
            $i = (int) floor(log($bytes, 1024));
            return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
        }
    }

    $imageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp'];
    $videoExt = ['mp4', 'webm', 'ogg', 'mov', 'avi', 'mkv', 'flv'];
    $archiveExt = ['zip', 'rar', '7z', 'tar', 'gz'];
    $total = count($progress);

    // Tombol edit/hapus di progress terbaru: hanya jika tiket tidak sedang terkunci (close)
    // Nilai dikirim dari TicketResource lewat ->viewData()
    $canManage = $total > 0 && ($canManageProgress ?? false);

    // Data ringkas per entri untuk pencarian & filter periode (diproses di browser)
    $filterItems = collect($progress)
        ->values()
        ->map(function ($item) {
            $plain = html_entity_decode(preg_replace('/<[^>]+>/', ' ', $item['progress'] ?? ''));
            $fileNames = collect($item['file'] ?? [])
                ->map(fn($f) => basename($f))
                ->implode(' ');

            return [
                'd' => \Carbon\Carbon::parse($item['timestamp'])->format('Y-m-d'),
                's' => mb_strtolower(trim(preg_replace('/\s+/', ' ', $plain . ' ' . $fileNames))),
            ];
        })
        ->all();
@endphp

<script>
    window.tpFilter = window.tpFilter || function(items) {
        const fmt = (d) => new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
        return {
            items: items,
            q: '',
            from: '',
            to: '',
            show(i) {
                const e = this.items[i];
                if (!e) return true;
                const q = this.q.trim().toLowerCase();
                if (q && !e.s.includes(q)) return false;
                if (this.from && e.d < this.from) return false;
                if (this.to && e.d > this.to) return false;
                return true;
            },
            get visible() {
                return this.items.filter((_, i) => this.show(i)).length;
            },
            get filtering() {
                return !!(this.q.trim() || this.from || this.to);
            },
            preset(days) {
                const now = new Date();
                const start = new Date();
                start.setDate(now.getDate() - (days - 1));
                this.from = fmt(start);
                this.to = fmt(now);
            },
            reset() {
                this.q = '';
                this.from = '';
                this.to = '';
            },
        };
    };
</script>

<div class="tp" x-data="tpFilter(@js($filterItems))">
    <div class="tp-header">
        <span class="tp-title">Riwayat Progress</span>
        <span class="tp-count"
            x-text="filtering ? visible + ' dari ' + items.length + ' entri' : items.length + ' entri'">{{ $total }}
            entri</span>
    </div>

    @if ($total > 0)
        <div class="tp-toolbar">
            <div class="tp-search">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8" />
                    <path d="m21 21-4.3-4.3" />
                </svg>
                <input type="search" class="tp-input" placeholder="Cari progress atau nama file..."
                    x-model.debounce.200ms="q" autocomplete="off">
            </div>

            <div class="tp-period">
                <input type="date" class="tp-input" x-model="from" :max="to || null" aria-label="Dari tanggal">
                <span class="tp-sep">&ndash;</span>
                <input type="date" class="tp-input" x-model="to" :min="from || null"
                    aria-label="Sampai tanggal">
            </div>

            <div class="tp-presets">
                <button type="button" class="tp-chip" x-on:click="preset(1)">Hari ini</button>
                <button type="button" class="tp-chip" x-on:click="preset(7)">7 hari</button>
                <button type="button" class="tp-chip" x-on:click="preset(30)">30 hari</button>
                <button type="button" class="tp-chip tp-chip-reset" x-show="filtering" x-cloak
                    x-on:click="reset()">Reset</button>
            </div>
        </div>
    @endif

    @if ($isClosed)
        <div class="tp-notice">
            Tiket ini berstatus selesai. Riwayat progress hanya dapat dilihat dan tidak bisa diubah.
        </div>
    @endif

    @if ($total > 0)
        <div class="tp-timeline" x-init="$nextTick(() => {
            $el.scrollTop = $el.scrollHeight;
            setTimeout(() => $el.scrollTop = $el.scrollHeight, 150);
        })">
            @foreach ($progress as $index => $item)
                @php
                    $isLatest = $loop->last;
                    $html = $item['progress'] ?? '';

                    // Gambar yang disisipkan lewat rich editor -> jadi thumbnail
                    preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $html, $m);
                    $richImgs = $m[1] ?? [];

                    // Bersihkan teks dari gambar/link gambar/label file duplikat
                    $text = preg_replace('/<img[^>]+>/i', '', $html);
                    $text = preg_replace(
                        '/<a[^>]*href=["\'][^"\']*\.(jpg|jpeg|png|gif|webp|svg|bmp)["\'][^>]*>.*?<\/a>/is',
                        '',
                        $text,
                    );
                    $text = preg_replace(
                        '/[\w\-\.]+\.(jpg|jpeg|png|gif|webp|svg|bmp)\s*[\d\.,]+\s*(B|KB|MB|GB|TB)/i',
                        '',
                        $text,
                    );
                    $text = trim(preg_replace('/<p[^>]*>\s*<\/p>/i', '', $text));
                    $hasText = trim(strip_tags($text)) !== '';

                    $thumbs = [];
                    $files = [];
                    foreach ($item['file'] ?? [] as $file) {
                        $ext = Str::lower(pathinfo($file, PATHINFO_EXTENSION));
                        $data = [
                            'ext' => $ext,
                            'url' => asset('storage/' . $file),
                            'name' => basename($file),
                            'size' => formatFileSize(
                                Storage::exists('public/' . $file) ? Storage::size('public/' . $file) : 0,
                            ),
                        ];
                        if (in_array($ext, $imageExt)) {
                            $thumbs[] = $data + ['type' => 'image'];
                        } elseif (in_array($ext, $videoExt)) {
                            $thumbs[] = $data + ['type' => 'video'];
                        } else {
                            $files[] = $data;
                        }
                    }
                    $hasMedia = count($thumbs) || count($richImgs);
                @endphp

                <div class="tp-entry {{ $isLatest ? 'is-latest' : '' }}" x-show="show({{ $loop->index }})">
                    <span class="tp-dot"></span>

                    <div class="tp-card">
                        <div class="tp-meta">
                            <span class="tp-badge">{{ $isLatest ? 'Terbaru' : 'Sebelumnya' }}</span>
                            <div class="tp-meta-right">
                                <span class="tp-time">
                                    {{ \Carbon\Carbon::parse($item['timestamp'])->translatedFormat('d F Y, H:i') }}
                                    @if (!empty($item['edited_at']))
                                        &middot; diedit
                                    @endif
                                </span>

                                @if ($isLatest && $canManage)
                                    <div class="tp-actions">
                                        <button type="button" class="tp-act" title="Edit progress terbaru"
                                            wire:click="mountAction('editLatestProgress')" wire:loading.attr="disabled">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <path d="M12 20h9" />
                                                <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                            </svg>
                                            Edit
                                        </button>
                                        <button type="button" class="tp-act tp-act-danger"
                                            title="Hapus progress terbaru"
                                            wire:click="mountAction('deleteLatestProgress')"
                                            wire:loading.attr="disabled">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                stroke-linejoin="round">
                                                <path d="M3 6h18" />
                                                <path d="M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2" />
                                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                                            </svg>
                                            Hapus
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if ($hasText)
                            <div class="tp-body">{!! $text !!}</div>
                        @endif

                        @if ($hasMedia)
                            <div class="tp-thumbs">
                                @foreach ($thumbs as $t)
                                    <button type="button" class="tp-thumb" data-tp-url="{{ $t['url'] }}"
                                        data-tp-type="{{ $t['type'] }}" title="{{ $t['name'] }}">
                                        @if ($t['type'] === 'image')
                                            <img src="{{ $t['url'] }}" alt="{{ $t['name'] }}" loading="lazy">
                                        @else
                                            <video src="{{ $t['url'] }}" muted preload="metadata"></video>
                                            <span class="tp-play">
                                                <svg width="18" height="18" viewBox="0 0 24 24"
                                                    fill="currentColor">
                                                    <path d="M8 5v14l11-7z" />
                                                </svg>
                                            </span>
                                        @endif
                                    </button>
                                @endforeach

                                @foreach ($richImgs as $url)
                                    <button type="button" class="tp-thumb" data-tp-url="{{ $url }}"
                                        data-tp-type="image">
                                        <img src="{{ $url }}" alt="Gambar" loading="lazy">
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        @if (count($files))
                            <div class="tp-files {{ $hasMedia || $hasText ? 'has-gap' : '' }}">
                                @foreach ($files as $f)
                                    <div class="tp-file">
                                        <span
                                            class="tp-file-icon">{{ strtoupper(substr($f['ext'], 0, 3)) ?: 'FIL' }}</span>
                                        <a href="{{ $f['url'] }}" class="tp-file-name"
                                            {{ in_array($f['ext'], $archiveExt) ? 'download' : 'target=_blank rel=noopener' }}>
                                            {{ $f['name'] }}
                                        </a>
                                        @if ($f['size'])
                                            <span class="tp-file-size">{{ $f['size'] }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach

            <div class="tp-empty" x-show="filtering && visible === 0" x-cloak>
                Tidak ada progress yang cocok dengan pencarian atau periode ini
            </div>
        </div>
    @else
        <div class="tp-empty">Belum ada progress yang tercatat</div>
    @endif
</div>

<div id="tp-lightbox" class="tp-lightbox" hidden>
    <div class="tp-lb-inner">
        <button type="button" class="tp-lb-close" aria-label="Tutup">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2">
                <path d="M18 6 6 18M6 6l12 12" />
            </svg>
        </button>
        <img id="tp-lb-img" alt="Preview" hidden>
        <video id="tp-lb-vid" controls hidden></video>
    </div>
</div>

<style>
    .tp {
        --tp-bg: #fff;
        --tp-bg-soft: #f9fafb;
        --tp-border: #e5e7eb;
        --tp-border-soft: #f3f4f6;
        --tp-text: #374151;
        --tp-title: #111827;
        --tp-muted: #6b7280;
        --tp-faint: #9ca3af;
        --tp-accent: #374151;
        --tp-notice-bg: #fffbeb;
        --tp-notice-border: #fde68a;
        --tp-notice-text: #92400e;
        padding: 0.5rem 0;
        font-family: inherit;
    }

    .dark .tp {
        --tp-bg: #09090b;
        --tp-bg-soft: #18181b;
        --tp-border: #27272a;
        --tp-border-soft: #27272a;
        --tp-text: #e4e4e7;
        --tp-title: #fafafa;
        --tp-muted: #a1a1aa;
        --tp-faint: #71717a;
        --tp-accent: #a1a1aa;
        --tp-notice-bg: rgba(251, 191, 36, 0.08);
        --tp-notice-border: rgba(251, 191, 36, 0.25);
        --tp-notice-text: #fcd34d;
    }

    /* Header */
    .tp-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 0.75rem;
        margin-bottom: 1rem;
        border-bottom: 1px solid var(--tp-border);
    }

    .tp-title {
        font-size: 15px;
        font-weight: 600;
        color: var(--tp-title);
    }

    .tp-count {
        font-size: 11px;
        color: var(--tp-muted);
        background: var(--tp-bg-soft);
        border: 1px solid var(--tp-border);
        border-radius: 999px;
        padding: 2px 10px;
    }

    /* Pencarian & filter periode */
    .tp-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        margin-bottom: 1rem;
    }

    .tp-search {
        position: relative;
        flex: 1 1 220px;
        min-width: 180px;
        color: var(--tp-faint);
    }

    .tp-search svg {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
    }

    .tp-search .tp-input {
        width: 100%;
        padding-left: 30px;
    }

    .tp-period {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .tp-sep {
        color: var(--tp-faint);
        font-size: 12px;
    }

    .tp-input {
        height: 32px;
        font-size: 12px;
        color: var(--tp-text);
        background: var(--tp-bg-soft);
        border: 1px solid var(--tp-border);
        border-radius: 8px;
        padding: 0 10px;
        outline: none;
        transition: border-color 0.15s;
    }

    .tp-input:focus {
        border-color: var(--tp-accent);
    }

    .dark .tp-input {
        color-scheme: dark;
    }

    .tp-presets {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .tp-chip {
        height: 32px;
        padding: 0 10px;
        font-size: 11px;
        font-weight: 500;
        color: var(--tp-muted);
        background: transparent;
        border: 1px solid var(--tp-border);
        border-radius: 999px;
        cursor: pointer;
        transition: border-color 0.15s, color 0.15s;
    }

    .tp-chip:hover {
        color: var(--tp-title);
        border-color: var(--tp-faint);
    }

    .tp-chip-reset {
        color: #dc2626;
    }

    .dark .tp-chip-reset {
        color: #f87171;
    }

    [x-cloak] {
        display: none !important;
    }

    .tp-notice {
        font-size: 13px;
        color: var(--tp-notice-text);
        background: var(--tp-notice-bg);
        border: 1px solid var(--tp-notice-border);
        border-radius: 8px;
        padding: 8px 12px;
        margin-bottom: 1rem;
    }

    /* Timeline */
    .tp-timeline {
        position: relative;
        max-height: 70vh;
        overflow-y: auto;
        padding-right: 4px;
    }

    .tp-timeline::before {
        content: '';
        position: absolute;
        left: 7px;
        top: 8px;
        bottom: 8px;
        width: 1px;
        background: var(--tp-border);
    }

    .tp-entry {
        position: relative;
        padding-left: 28px;
        margin-bottom: 1rem;
    }

    .tp-entry:last-child {
        margin-bottom: 0;
    }

    .tp-dot {
        position: absolute;
        left: 0;
        top: 16px;
        width: 15px;
        height: 15px;
        border-radius: 50%;
        background: var(--tp-bg);
        border: 2px solid var(--tp-border);
    }

    .tp-entry.is-latest .tp-dot {
        border-color: var(--tp-accent);
        background: var(--tp-accent);
    }

    /* Card */
    .tp-card {
        background: var(--tp-bg);
        border: 1px solid var(--tp-border);
        border-radius: 12px;
        padding: 0.875rem 1rem;
        transition: border-color 0.2s;
    }

    .tp-card:hover {
        border-color: var(--tp-faint);
    }

    .tp-entry.is-latest .tp-card {
        border-left: 3px solid var(--tp-accent);
    }

    .tp-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        margin-bottom: 0.625rem;
    }

    .tp-badge {
        font-size: 11px;
        font-weight: 500;
        color: var(--tp-muted);
        background: var(--tp-bg-soft);
        border: 1px solid transparent;
        border-radius: 999px;
        padding: 2px 10px;
    }

    .tp-entry.is-latest .tp-badge {
        background: transparent;
        color: var(--tp-title);
        border-color: var(--tp-accent);
    }

    .tp-time {
        font-size: 11px;
        color: var(--tp-faint);
    }

    .tp-meta-right {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .tp-actions {
        display: flex;
        gap: 6px;
    }

    .tp-act {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        font-weight: 500;
        padding: 3px 9px;
        color: var(--tp-text);
        background: var(--tp-bg-soft);
        border: 1px solid var(--tp-border);
        border-radius: 6px;
        cursor: pointer;
        transition: border-color 0.15s, color 0.15s;
    }

    .tp-act:hover {
        border-color: var(--tp-faint);
    }

    .tp-act:disabled {
        opacity: 0.5;
        cursor: wait;
    }

    .tp-act-danger {
        color: #dc2626;
    }

    .tp-act-danger:hover {
        border-color: #dc2626;
    }

    .dark .tp-act-danger {
        color: #f87171;
    }

    .tp-body {
        font-size: 14px;
        line-height: 1.65;
        color: var(--tp-text);
        overflow-wrap: anywhere;
    }

    .tp-body p+p {
        margin-top: 0.5rem;
    }

    /* Thumbnails */
    .tp-thumbs {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 0.75rem;
        padding-top: 0.75rem;
        border-top: 1px solid var(--tp-border-soft);
    }

    .tp-thumb {
        position: relative;
        width: 100px;
        height: 80px;
        padding: 0;
        border: 1px solid var(--tp-border);
        border-radius: 8px;
        overflow: hidden;
        background: var(--tp-bg-soft);
        cursor: pointer;
        flex-shrink: 0;
        transition: transform 0.15s, border-color 0.15s;
    }

    .tp-thumb:hover {
        transform: translateY(-1px);
        border-color: var(--tp-faint);
    }

    .tp-thumb img,
    .tp-thumb video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .tp-play {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        background: rgba(0, 0, 0, 0.35);
    }

    /* File list */
    .tp-files {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .tp-files.has-gap {
        margin-top: 0.75rem;
    }

    .tp-file {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 12px;
        background: var(--tp-bg-soft);
        border: 1px solid var(--tp-border-soft);
        border-radius: 8px;
    }

    .tp-file-icon {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: 600;
        flex-shrink: 0;
        color: var(--tp-text);
        background: color-mix(in srgb, var(--tp-accent) 15%, transparent);
    }

    .tp-file-name {
        flex: 1;
        min-width: 0;
        font-size: 13px;
        color: var(--tp-text);
        text-decoration: none;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .tp-file-name:hover {
        text-decoration: underline;
    }

    .tp-file-size {
        font-size: 11px;
        color: var(--tp-faint);
        white-space: nowrap;
    }

    .tp-empty {
        text-align: center;
        padding: 2.5rem;
        font-size: 14px;
        color: var(--tp-faint);
        border: 1px dashed var(--tp-border);
        border-radius: 12px;
    }

    /* Lightbox */
    .tp-lightbox {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: rgba(0, 0, 0, 0.88);
    }

    .tp-lightbox[hidden] {
        display: none;
    }

    .tp-lb-inner {
        position: relative;
        max-width: 90vw;
        max-height: 90vh;
    }

    .tp-lb-inner img,
    .tp-lb-inner video {
        display: block;
        max-width: 90vw;
        max-height: 88vh;
        object-fit: contain;
        border-radius: 10px;
    }

    .tp-lb-inner [hidden] {
        display: none;
    }

    .tp-lb-close {
        position: absolute;
        top: -36px;
        right: 0;
        background: none;
        border: none;
        color: #fff;
        opacity: 0.75;
        cursor: pointer;
    }

    .tp-lb-close:hover {
        opacity: 1;
    }
</style>

<script>
    (function() {
        if (window.__tpLightboxInit) return;
        window.__tpLightboxInit = true;

        const $ = (id) => document.getElementById(id);

        function openLb(url, type) {
            const img = $('tp-lb-img'),
                vid = $('tp-lb-vid');
            img.hidden = type !== 'image';
            vid.hidden = type !== 'video';
            if (type === 'image') img.src = url;
            else vid.src = url;
            $('tp-lightbox').hidden = false;
            document.body.style.overflow = 'hidden';
        }

        function closeLb() {
            const vid = $('tp-lb-vid');
            if (vid) {
                vid.pause();
                vid.removeAttribute('src');
            }
            $('tp-lb-img').removeAttribute('src');
            $('tp-lightbox').hidden = true;
            document.body.style.overflow = '';
        }

        // Event delegation: tetap jalan walau konten di-render ulang oleh Livewire
        document.addEventListener('click', function(e) {
            const thumb = e.target.closest('[data-tp-url]');
            if (thumb) return openLb(thumb.dataset.tpUrl, thumb.dataset.tpType);

            const lb = $('tp-lightbox');
            if (lb && !lb.hidden && (e.target === lb || e.target.closest('.tp-lb-close'))) closeLb();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && $('tp-lightbox') && !$('tp-lightbox').hidden) closeLb();
        });
    })();
</script>
