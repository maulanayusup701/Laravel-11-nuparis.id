<!DOCTYPE html>
<html lang="id">

<head>
    @include('front-end.layouts.components.seo-meta')
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css" rel="stylesheet" />
    <link href="{{ asset('assets/front-end/css/style.css') }}" rel="stylesheet" />
    <meta name="title" content="{{ $infos->meta_title }}" />
    <meta name="description" content="{{ $infos->meta_description }}" />

    <!-- FilePond CSS -->
    <link href="https://unpkg.com/filepond@^4/dist/filepond.css" rel="stylesheet">

    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        .hero-pagination {
            display: flex;
            align-items: center;
            gap: 6px;
            justify-content: center;
            bottom: 20px !important;
        }

        .hero-pagination .swiper-pagination-bullet {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 1;
            transition: width 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .hero-pagination .swiper-pagination-bullet-active {
            width: 40px;
            background: rgba(255, 255, 255, 0.4);
        }

        .hero-pagination .swiper-pagination-bullet-active::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 0%;
            background: white;
            border-radius: 999px;
            animation: bulletProgress 4s linear forwards;
        }

        .hero-pagination .swiper-pagination-bullet-active.reset-anim::after {
            animation: none;
        }

        @keyframes bulletProgress {
            from {
                width: 0%;
            }

            to {
                width: 100%;
            }
        }
    </style>

    <script src="{{ asset('assets/front-end/js/configtailwind.js') }}"></script>
</head>

<body class="font-sans text-slate-800 bg-slate-50 min-h-screen overflow-x-hidden">
    <!-- Desktop Navbar -->
    <nav id="main-navbar" class="hidden lg:block fixed top-0 w-full z-50 transition-all duration-300 bg-transparent">
        <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-8">
            <div id="navbar-content" class="flex justify-between h-16 py-2 transition-all duration-300">
                <div class="flex items-center">
                    <a href="{{ route('landingpage') }}" class="flex-shrink-0 flex items-center gap-2">
                        <div id="logo-container"
                            class="w-10 h-10 bg-white/90 rounded-lg flex items-center justify-center shadow-sm transition-all duration-300">
                            <img src="{{ asset('storage/' . $infos->meta_image) }}" alt="NUPARIS Logo" class="w-8 h-8">
                        </div>
                        <span id="logo-text"
                            class="font-bold text-2xl tracking-tight text-white transition-all duration-300">NUPARIS</span>
                    </a>
                </div>

                <div class="flex items-center space-x-8">
                    <a href="{{ route('landingpage') }}" id="nav-home"
                        class="nav-link {{ request()->routeIs('landingpage') ? 'text-white font-semibold border-b-2 border-white' : 'text-white/90 hover:text-white font-medium' }} transition-all duration-300">
                        Beranda
                    </a>

                    <a href="{{ route('news-more') }}"
                        class="nav-link {{ request()->routeIs('news-more') ? 'text-white font-semibold border-b-2 border-white' : 'text-white/90 hover:text-white font-medium' }} transition-all duration-300">
                        Berita
                    </a>

                    <a href="{{ route('article-more') }}"
                        class="nav-link {{ request()->routeIs('article-more') ? 'text-white font-semibold border-b-2 border-white' : 'text-white/90 hover:text-white font-medium' }} transition-all duration-300">
                        Perizinan & Non Perizinan
                    </a>

                    <a href="{{ route('activity-more') }}"
                        class="nav-link {{ request()->routeIs('activity-more') ? 'text-white font-semibold border-b-2 border-white' : 'text-white/90 hover:text-white font-medium' }} transition-all duration-300">
                        Aktivitas
                    </a>

                    <a href="{{ route('event-more') }}"
                        class="nav-link {{ request()->routeIs('event-more') ? 'text-white font-semibold border-b-2 border-white' : 'text-white/90 hover:text-white font-medium' }} transition-all duration-300">
                        Event
                    </a>

                    <a href="{{ route('property-more') }}"
                        class="nav-link {{ request()->routeIs('property-more') ? 'text-white font-semibold border-b-2 border-white' : 'text-white/90 hover:text-white font-medium' }} transition-all duration-300">
                        Properti
                    </a>

                    <a href="{{ route('about-us') }}"
                        class="nav-link {{ request()->routeIs('about-us') ? 'text-white font-semibold border-b-2 border-white' : 'text-white/90 hover:text-white font-medium' }} transition-all duration-300">
                        About Us
                    </a>

                    @if (auth()->check())
                        <a href="https://www.nuparis.id/management" id="login-button"
                            class="inline-flex items-center justify-center bg-white text-primary px-5 py-2 rounded-full font-medium hover:bg-white/90 transition-all duration-200 shadow-lg">
                            Dashboard
                        </a>
                    @else
                        <a href="https://www.nuparis.id/management/login" id="login-button"
                            class="inline-flex items-center justify-center bg-white text-primary px-5 py-2 rounded-full font-medium hover:bg-white/90 transition-all duration-200 shadow-lg">
                            Masuk
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </nav>

    @php
        $heroAssets = $heroes;
        $isVideo = is_array($heroAssets) && isset($heroAssets['url']);
        $isSlider = !$isVideo && (is_object($heroAssets) || is_array($heroAssets)) && count((array) $heroAssets) > 0;
    @endphp

    <!-- Main Content -->
    <div class="min-h-screen">
        <section class="hidden lg:block relative w-full overflow-hidden pt-0">
            @if ($isVideo)
                <div id="videoContainer" class="relative w-full max-h-[calc(100vh-200px)] aspect-video bg-black">
                    <video id="videoPlayer" class="w-full h-full object-cover" autoplay muted loop playsinline>
                        <source src="{{ $heroAssets['url'] }}" type="video/mp4">
                        Browser Anda tidak mendukung tag video.
                    </video>

                    <div class="absolute inset-0 bg-gradient-to-b from-black/30 to-transparent pointer-events-none">
                    </div>

                    <button id="fullscreenBtn"
                        class="absolute bottom-5 right-5 bg-black/60 hover:bg-black/80 text-white px-4 py-3 rounded-lg flex items-center gap-2 transition-all duration-300 hover:scale-105 z-10">
                        <svg id="expandIcon" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path
                                d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3" />
                        </svg>
                        <svg id="collapseIcon" class="w-5 h-5 hidden" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path
                                d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3" />
                        </svg>
                    </button>
                </div>
            @elseif ($isSlider)
                <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

                <div class="relative w-full max-h-[calc(100vh-200px)] aspect-video bg-black">
                    <div class="swiper hero-swiper w-full h-full">
                        <div class="swiper-wrapper">
                            @foreach ($heroAssets as $imageUrl)
                                <div class="swiper-slide">
                                    <img src="{{ $imageUrl }}" alt="Hero Image" class="w-full h-full object-cover">
                                </div>
                            @endforeach
                        </div>

                        <div class="swiper-pagination hero-pagination"></div>

                        <div class="swiper-button-prev !text-white !w-10 !h-10 after:!text-sm"></div>
                        <div class="swiper-button-next !text-white !w-10 !h-10 after:!text-sm"></div>
                    </div>

                    <div
                        class="absolute inset-0 bg-gradient-to-b from-black/30 to-transparent pointer-events-none z-10">
                    </div>
                </div>

                <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
                <script>
                    const heroSwiper = new Swiper('.hero-swiper', {
                        loop: true,
                        autoplay: {
                            delay: 4000,
                            disableOnInteraction: false,
                        },
                        effect: 'fade',
                        fadeEffect: {
                            crossFade: true
                        },
                        navigation: {
                            nextEl: '.swiper-button-next',
                            prevEl: '.swiper-button-prev',
                        },
                        pagination: {
                            el: '.hero-pagination',
                            clickable: true,
                        },
                    });

                    heroSwiper.on('slideChange', function() {
                        const activeBullet = document.querySelector('.hero-pagination .swiper-pagination-bullet-active');
                        if (!activeBullet) return;
                        activeBullet.classList.add('reset-anim');

                        requestAnimationFrame(() => {
                            requestAnimationFrame(() => {
                                activeBullet.classList.remove('reset-anim');
                            });
                        });
                    });
                </script>
            @else
                <div
                    class="relative w-full max-h-[calc(100vh-200px)] aspect-video bg-gray-900 flex items-center justify-center">
                    <p class="text-white/50 text-sm">Tidak ada media tersedia</p>
                </div>
            @endif

        </section>

        <!-- Main Content Area -->
        <main class="max-w-[1920px] mx-auto px-4 sm:px-8 lg:px-12 lg:py-8 space-y-12 pt-4 lg:pt-6">

            <!-- Hero Section -->
            <section class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <a href="{{ route('article-detail', $articles[0]->article_slug) }}"
                    class="block lg:col-span-2 order-2 lg:order-1">
                    <div
                        class="relative rounded-2xl overflow-hidden group shadow-xl h-96 cursor-pointer animate-fade-in-up">
                        <img src="{{ asset('storage/' . $articles[0]->article_image) }}"
                            alt="{{ $articles[0]->article_title }}"
                            class="w-full h-full object-cover lg:group-hover:scale-105 transition-transform duration-500">

                        <div class="absolute top-4 left-4 z-20 opacity-50">
                            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
                                <img src="{{ asset('storage/' . $infos->meta_image) }}" alt="NUPARIS Logo"
                                    class="w-8 h-8">
                            </div>
                        </div>

                        <div
                            class="absolute top-4 right-4 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-md text-sm font-bold text-slate-800 shadow-sm z-20">
                            <i class="far fa-calendar-alt mr-1 text-primary"></i>
                            {{ $articles[0]->created_at->translatedFormat('d M Y') }}
                        </div>

                        <div
                            class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent flex flex-col justify-end p-6">
                            <span
                                class="bg-primary text-white text-xs font-bold px-3 py-1 rounded-full w-fit mb-3">Berita
                                Utama</span>
                            <h1
                                class="text-white text-2xl md:text-3xl font-bold leading-tight mb-2 lg:group-hover:underline decoration-2 underline-offset-4">
                                {{ $articles[0]->article_title }}</h1>
                            <p class="text-slate-200 text-sm line-clamp-2">
                                {{ strip_tags($articles[0]->article_description) }}
                            </p>
                        </div>
                    </div>
                </a>

                <div class="flex flex-col gap-4 order-1 lg:order-2">
                    <div
                        class="bg-blue-600 rounded-xl p-6 text-white shadow-lg flex-1 flex flex-col justify-center items-center text-center lg:hover:-translate-y-1 transition-transform duration-300 lg:hover:shadow-2xl cursor-pointer animate-slide-in-right">
                        <i class="fas fa-bullhorn text-4xl mb-3 opacity-90 animate-float"></i>
                        <h3 class="text-xl font-bold mb-2">Pengumuman Terbaru</h3>
                        <p class="text-blue-100 text-sm">Mohon maaf, sistem kami sedang dalam pemeliharaan berkala</p>
                    </div>

                    @foreach ($activities->take(2) as $item)
                        <a href="{{ route('activity-detail', $item->activity_slug) }}" class="block">
                            <div
                                class="bg-white rounded-xl overflow-hidden shadow-md flex max-h-[140px] lg:hover:-translate-y-1 transition-all duration-300 lg:hover:shadow-xl cursor-pointer relative animate-slide-in-right group">
                                <div class="absolute top-2 left-2 z-10 opacity-50">
                                    <div class="w-8 h-8 bg-white rounded flex items-center justify-center">
                                        <img src="{{ asset('storage/' . $infos->meta_image) }}" alt="NUPARIS Logo"
                                            class="w-6 h-6">
                                    </div>
                                </div>
                                <div
                                    class="absolute top-2 left-12 bg-white/90 backdrop-blur-sm px-2 py-1 rounded text-[10px] font-bold text-slate-700 z-10 pl-3">
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    {{ $item->activity_date ? \Carbon\Carbon::parse($item->activity_date)->translatedFormat('d M Y') : $item->created_at->translatedFormat('d M Y') }}
                                </div>

                                <div class="relative w-1/3 overflow-hidden rounded-l-xl min-h-[140px]">
                                    <img src="{{ asset('storage/' . $item->activity_image) }}"
                                        class="w-full h-full object-cover lg:group-hover:scale-105 transition-transform duration-500"
                                        alt="{{ $item->activity_title }}">
                                </div>

                                <div class="p-4 w-2/3 flex flex-col justify-center">
                                    <span
                                        class="text-primary text-xs font-bold mb-1">{{ $item->activityCategory->activity_category_name }}</span>
                                    <h4
                                        class="font-semibold text-slate-800 text-sm leading-snug line-clamp-2 lg:group-hover:text-primary transition">
                                        {{ $item->activity_title }}</h4>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>

            <!-- Berita Section -->
            <section>
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3 border-l-4 border-primary pl-3">
                        <h2 class="text-xl lg:text-2xl font-bold text-slate-800">Berita Terkini</h2>
                    </div>
                    @if ($news->count() > 8)
                        <a href="{{ route('news-more') }}"
                            class="text-primary font-medium text-sm hover:underline lg:hover:translate-x-1 transition-transform duration-200">Lihat
                            Semua <i class="fas fa-arrow-right ml-1"></i></a>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach ($news->take(8) as $item)
                        <a href="{{ $item->news_url }}" class="block h-full">
                            <article
                                class="group bg-white rounded-xl overflow-hidden shadow-sm border border-slate-100 flex flex-col h-full animate-fade-in-up lg:hover:-translate-y-1 transition-all duration-300 lg:hover:shadow-xl">

                                <div class="relative h-48 overflow-hidden">
                                    <img src="{{ asset('storage/' . $item->news_image) }}"
                                        alt="{{ $item->news_title }}"
                                        class="w-full h-full object-cover lg:group-hover:scale-105 transition-transform duration-500">

                                    <div class="absolute top-2 left-2 z-10 opacity-50">
                                        <div
                                            class="w-8 h-8 bg-white rounded-lg flex items-center justify-center shadow-sm">
                                            <img src="{{ asset('storage/' . $infos->meta_image) }}"
                                                alt="NUPARIS Logo" class="w-6 h-6">
                                        </div>
                                    </div>

                                    <div
                                        class="absolute top-2 right-2 bg-white/90 backdrop-blur-sm px-2 py-1 rounded text-xs font-bold text-slate-700">
                                        <i class="far fa-calendar-alt mr-1"></i>
                                        {{ $item->created_at->translatedFormat('d M Y') }}
                                    </div>
                                </div>

                                <div class="p-4 flex flex-col flex-grow">
                                    <span class="text-xs font-bold text-primary uppercase tracking-wider">
                                        {{ $item->news_source }}
                                    </span>

                                    <h3
                                        class="font-bold text-slate-800 mt-2 mb-2 text-sm line-clamp-2 lg:group-hover:text-primary transition">
                                        {{ $item->news_title }}
                                    </h3>

                                    <p class="text-slate-500 text-xs line-clamp-3">
                                        {{ strip_tags($item->news_content) }}
                                    </p>

                                    <div class="mt-auto pt-4"></div>
                                </div>
                            </article>
                        </a>
                    @endforeach
                </div>
            </section>

            <!-- Perizinan & Non Perizinan Section -->
            <section>
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3 border-l-4 border-primary pl-3">
                        <h2 class="text-xl lg:text-2xl font-bold text-slate-800">Perizinan & Non Perizinan</h2>
                    </div>
                    @if ($articles->count() > 8)
                        <a href="{{ route('article-more') }}"
                            class="text-primary font-medium text-sm hover:underline lg:hover:translate-x-1 transition-transform duration-200">Lihat
                            Semua <i class="fas fa-arrow-right ml-1"></i></a>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach ($articles->take(8) as $item)
                        <a href="{{ route('article-detail', $item->article_slug) }}" class="block h-full">
                            <article
                                class="group bg-white rounded-xl overflow-hidden shadow-sm border border-slate-100 flex flex-col h-full animate-fade-in-up lg:hover:-translate-y-1 transition-all duration-300 lg:hover:shadow-xl">

                                <div class="relative h-48 overflow-hidden">
                                    <img src="{{ asset('storage/' . $item->article_image) }}"
                                        alt="{{ $item->article_title }}"
                                        class="w-full h-full object-cover lg:group-hover:scale-105 transition-transform duration-500">

                                    <div class="absolute top-2 left-2 z-10 opacity-50">
                                        <div
                                            class="w-8 h-8 bg-white rounded-lg flex items-center justify-center shadow-sm">
                                            <img src="{{ asset('storage/' . $infos->meta_image) }}"
                                                alt="NUPARIS Logo" class="w-6 h-6">
                                        </div>
                                    </div>

                                    <div
                                        class="absolute top-2 right-2 bg-white/90 backdrop-blur-sm px-2 py-1 rounded text-xs font-bold text-slate-700">
                                        <i class="far fa-calendar-alt mr-1"></i>
                                        {{ $item->created_at->translatedFormat('d M Y') }}
                                    </div>
                                </div>

                                <div class="p-4 flex flex-col flex-grow">
                                    <span class="text-xs font-bold text-primary uppercase tracking-wider">
                                        {{ $item->article_badge['label'] }}
                                    </span>

                                    <h3
                                        class="font-bold text-slate-800 mt-2 mb-2 text-sm line-clamp-2 lg:group-hover:text-primary transition">
                                        {{ $item->article_title }}
                                    </h3>

                                    <p class="text-slate-500 text-xs line-clamp-3">
                                        {{ strip_tags($item->article_description) }}
                                    </p>

                                    <div class="mt-auto"></div>
                                </div>
                            </article>
                        </a>
                    @endforeach
                </div>
            </section>

            <!-- Aktivitas Section -->
            <section>
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3 border-l-4 border-primary pl-3">
                        <h2 class="text-xl lg:text-2xl font-bold text-slate-800">Aktivitas</h2>
                    </div>
                    @if ($activities->count() > 8)
                        <a href="{{ route('activity-more') }}"
                            class="text-primary font-medium text-sm hover:underline lg:hover:translate-x-1 transition-transform duration-200">Lihat
                            Semua <i class="fas fa-arrow-right ml-1"></i></a>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 auto-rows-fr">
                    @foreach ($activities->sortByDesc('created_at')->take(8) as $item)
                        <a href="{{ route('activity-detail', $item->activity_slug) }}" class="block h-full">
                            <article
                                class="group bg-white rounded-xl overflow-hidden shadow-sm border border-slate-100 flex flex-col h-full animate-fade-in-up lg:hover:-translate-y-1 transition-all duration-300 lg:hover:shadow-xl cursor-pointer">

                                <div class="relative h-48 overflow-hidden rounded-t-xl">
                                    <img src="{{ asset('storage/' . $item->activity_image) }}"
                                        alt="{{ $item->activity_title }}"
                                        class="w-full h-full object-cover lg:group-hover:scale-105 transition-transform duration-500">

                                    <div class="absolute top-2 left-2 z-10 opacity-50">
                                        <div
                                            class="w-8 h-8 bg-white rounded-lg flex items-center justify-center shadow-sm">
                                            <img src="{{ asset('storage/' . $infos->meta_image) }}"
                                                alt="NUPARIS Logo" class="w-6 h-6">
                                        </div>
                                    </div>

                                    <div
                                        class="absolute top-2 right-2 bg-white/90 backdrop-blur-sm px-2 py-1 rounded text-xs font-bold text-slate-700">
                                        <i
                                            class="far fa-calendar-alt mr-1"></i>{{ $item->created_at->translatedFormat('d M Y') }}
                                    </div>
                                </div>

                                <div class="p-4 flex flex-col flex-1">
                                    <div class="flex flex-col flex-1">
                                        <h3
                                            class="font-bold text-slate-800 mt-2 mb-2 text-sm line-clamp-2 lg:group-hover:text-primary transition">
                                            {{ $item->activity_title }}
                                        </h3>
                                        <p class="text-slate-500 text-xs line-clamp-3 flex-1">
                                            {{ strip_tags($item->activity_description) }}
                                        </p>
                                    </div>
                                </div>
                            </article>
                        </a>
                    @endforeach
                </div>
            </section>

            <!-- Event Section -->
            <section>
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3 border-l-4 border-primary pl-3">
                        <h2 class="text-xl lg:text-2xl font-bold text-slate-800">Event</h2>
                    </div>
                    @if ($events->count() > 3)
                        <a href="{{ route('event-more') }}"
                            class="text-primary font-medium text-sm hover:underline lg:hover:translate-x-1 transition-transform duration-200">Lihat
                            Semua <i class="fas fa-arrow-right ml-1"></i></a>
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($events->take(3) as $item)
                        @php
                            $start = \Carbon\Carbon::parse($item->event_date_start ?? $item->event_start_date);
                            $end = \Carbon\Carbon::parse($item->event_date_end ?? $item->event_end_date);
                            $now = now();

                            if ($now->between($start, $end)) {
                                $statusClass = 'bg-green-500 text-white';
                                $statusText = 'Ongoing';
                                $canRegister = true;
                            } elseif ($now->lt($start)) {
                                $statusClass = 'bg-blue-500 text-white';
                                $statusText = 'Upcoming';
                                $canRegister = true;
                            } else {
                                $statusClass = 'bg-red-500 text-white';
                                $statusText = 'End';
                                $canRegister = false;
                            }

                            $isFree = (int) ($item->event_price ?? 0) === 0;
                            $priceText = $isFree
                                ? 'GRATIS'
                                : 'Rp ' . number_format((int) $item->event_price, 0, ',', '.');

                            $rawQuota = (int) ($item->event_quota ?? 0);
                            $rawRegistered =
                                (int) ($item->registeredCount ??
                                    ($item->event_registered ?? ($item->participants_count ?? 0)));
                            $hasQuota = $rawQuota > 0;
                            $quota = $hasQuota ? $rawQuota : max($rawRegistered, 20);
                            $registered = max(0, $rawRegistered);
                            if ($registered > $quota) {
                                $registered = $quota;
                            }
                            $quotaPercentage = $quota > 0 ? min(100, round(($registered / $quota) * 100)) : 0;
                            $remainingQuota = max(0, $quota - $registered);
                            $quotaText = $remainingQuota > 0 ? 'Tersisa ' . $remainingQuota : 'Habis';
                            $quotaClass =
                                $remainingQuota > 0
                                    ? 'bg-blue-50 text-blue-700 border-blue-100'
                                    : 'bg-gray-100 text-gray-400 border-gray-200';
                            $progressBarColor =
                                $quotaPercentage >= 90
                                    ? 'bg-red-500'
                                    : ($quotaPercentage >= 70
                                        ? 'bg-yellow-500'
                                        : 'bg-green-500');
                        @endphp

                        <article onclick="window.location='{{ route('event-detail', $item->event_slug) }}'"
                            class="cursor-pointer bg-white rounded-xl overflow-hidden shadow-sm border border-gray-100 flex flex-row h-full animate-fade-in-up lg:hover:-translate-y-1 transition-all duration-300 lg:hover:shadow-xl group">

                            <div class="relative w-1/3 min-w-[140px] h-auto overflow-hidden">
                                <img src="{{ asset('storage/' . $item->event_image) }}"
                                    alt="{{ $item->event_title }}"
                                    class="w-full h-full object-cover lg:group-hover:scale-105 transition-transform duration-500">

                                <div class="absolute top-2 left-2 z-10 opacity-50">
                                    <div
                                        class="w-8 h-8 bg-white rounded-lg flex items-center justify-center shadow-sm">
                                        <img src="{{ asset('storage/' . $infos->meta_image) }}" alt="NUPARIS Logo"
                                            class="w-6 h-6">
                                    </div>
                                </div>

                                <div
                                    class="absolute bottom-2 left-2 z-10 {{ $statusClass }} text-xs font-semibold px-2 py-1 rounded shadow-sm">
                                    {{ $statusText }}
                                </div>
                            </div>

                            <div class="p-5 w-2/3 flex flex-col h-full">
                                <div class="flex-grow">
                                    <span class="text-xs font-bold text-primary uppercase tracking-wider mb-1">
                                        UMKM
                                    </span>
                                    <h3
                                        class="font-bold text-gray-800 text-lg mb-1 lg:group-hover:text-primary transition">
                                        {{ $item->short_title }}
                                    </h3>
                                    <div class="flex items-center gap-2 text-gray-500 text-xs mb-2">
                                        <i class="far fa-calendar-check"></i>
                                        <span>{{ $start->translatedFormat('d M Y') }}</span>
                                    </div>

                                    <div class="mb-2">
                                        <span
                                            class="inline-block {{ $isFree ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }} text-xs font-bold px-2 py-1 rounded">
                                            {{ $priceText }}
                                        </span>
                                    </div>

                                    <div class="mb-3">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-xs font-medium text-gray-700">Kuota Terisi:</span>
                                            <span
                                                class="text-xs font-bold {{ $quotaPercentage >= 90 ? 'text-red-600' : ($quotaPercentage >= 70 ? 'text-yellow-600' : 'text-green-600') }}">
                                                {{ $quotaPercentage }}%
                                            </span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                                            <div class="{{ $progressBarColor }} h-2 rounded-full"
                                                style="width: {{ $quotaPercentage }}%"></div>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <div class="text-xs text-gray-600">
                                                <i class="fas fa-users mr-1"></i>
                                                {{ $registered }}/{{ $quota }} peserta
                                            </div>
                                            <div
                                                class="{{ $quotaClass }} text-[10px] font-bold px-2 py-1 rounded border">
                                                {{ $quotaText }}
                                            </div>
                                        </div>
                                    </div>
                                    <p class="text-gray-500 text-sm line-clamp-2">
                                        {{ strip_tags($item->event_description) }}
                                    </p>
                                </div>

                                <div class="flex gap-3 mt-4">
                                    <a href="{{ route('event-detail', $item->event_slug) }}"
                                        class="flex-1 flex items-center justify-center bg-red-500 text-white font-semibold py-3 px-4 rounded-lg text-center transition duration-200 lg:hover:bg-red-700">
                                        Lihat Detail
                                    </a>
                                    @if ($canRegister)
                                        <a href="#"
                                            class="flex-[0_0_25%] flex items-center justify-center bg-white border-2 border-primary text-primary font-semibold py-3 rounded-lg transition duration-200 lg:hover:bg-primary lg:hover:text-white">
                                            <i class="fas fa-shopping-cart text-sm"></i>
                                        </a>
                                    @else
                                        <button disabled
                                            class="flex-[0_0_25%] flex items-center justify-center bg-gray-300 text-gray-500 font-semibold py-3 rounded-lg cursor-not-allowed">
                                            <i class="fas fa-shopping-cart text-sm"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <!-- Property Section -->
            <section>
                <div class="flex items-center justify-between mb-6">
                    @if ($properties->count() > 0)
                        <div class="flex items-center gap-3 border-l-4 border-primary pl-3">
                            <h2 class="text-xl lg:text-2xl font-bold text-slate-800">Property</h2>
                        </div>
                    @endif
                    @if ($properties->count() > 4)
                        <a href="{{ route('property-more') }}"
                            class="text-primary font-medium text-sm hover:underline lg:hover:translate-x-1 transition-transform duration-200">Lihat
                            Semua <i class="fas fa-arrow-right ml-1"></i></a>
                    @endif
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach ($properties->take(4) as $item)
                        <div
                            class="bg-white rounded-xl overflow-hidden shadow-lg cursor-pointer animate-fade-in-up lg:hover:-translate-y-1 transition-all duration-300 lg:hover:shadow-xl group">
                            <div class="relative h-56 overflow-hidden">
                                <img src="{{ asset('storage/' . $item->property_image[0]) }}"
                                    alt="{{ $item->property_title }}"
                                    class="w-full h-full object-cover lg:group-hover:scale-105 transition-transform duration-500"
                                    onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1568605114967-8130f3a36994?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'">

                                <div class="absolute top-3 left-3 z-10 opacity-50">
                                    <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center">
                                        <img src="{{ asset('storage/' . $infos->meta_image) }}" alt="NUPARIS Logo"
                                            class="w-6 h-6">
                                    </div>
                                </div>

                                <div
                                    class="absolute top-3 right-3 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-lg shadow-sm">
                                    <div class="text-sm font-bold text-red-600"
                                        data-price="{{ $item->property_price }}"></div>
                                </div>
                            </div>

                            <div class="p-5">
                                <h3
                                    class="text-lg font-bold text-slate-800 mb-2 lg:group-hover:text-red-600 transition-colors">
                                    {{ $item->property_name }}
                                </h3>
                                <p class="text-slate-600 text-sm mb-4 line-clamp-2">
                                    {{ strip_tags($item->property_description) }}
                                </p>

                                <div class="grid grid-cols-2 gap-2 mb-4">
                                    <div class="text-center bg-slate-50 py-2 rounded-lg border border-slate-100">
                                        <div class="text-red-600 text-sm font-bold">{{ $item->property_land_area }}
                                        </div>
                                        <div class="text-slate-500 text-[10px]">Luas Tanah</div>
                                    </div>

                                    <div class="text-center bg-slate-50 py-2 rounded-lg border border-slate-100">
                                        <div class="text-red-600 text-sm font-bold">
                                            {{ $item->property_building_area }}</div>
                                        <div class="text-slate-500 text-[10px]">Luas Bangunan</div>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3 mb-5 p-3 bg-slate-50 rounded-lg">
                                    <i class="fas fa-map-marker-alt text-red-600 mt-1"></i>
                                    <div>
                                        <p class="text-slate-700 text-sm font-medium mb-1">Lokasi</p>
                                        <p class="text-slate-500 text-xs line-clamp-2">{{ $item->property_address }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex gap-3">
                                    <a href="{{ route('property-detail', $item->property_slug) }}"
                                        class="w-[85%] bg-red-600 lg:hover:bg-red-700 text-white font-semibold py-3 px-4 rounded-lg transition-colors duration-200 text-center lg:hover:-translate-y-0.5 lg:hover:shadow-md">
                                        Lihat Detail
                                    </a>

                                    <a href="https://wa.me/{{ str_replace(' ', '', $item->property_no_whatsapp ?: env('NO_WHATSAPP')) }}?text=Halo, saya tertarik dengan properti &quot;{{ urlencode($item->property_name) }}&quot; di NUPARIS.ID.%0A%0ALink detail: {{ url()->current() }}%0A%0AMohon info lebih lanjut. Terima kasih."
                                        target="_blank"
                                        class="w-[15%] bg-white border-2 border-red-600 text-red-600 lg:hover:bg-red-600 lg:hover:text-white font-semibold py-3 rounded-lg transition-colors duration-200 flex items-center justify-center lg:hover:-translate-y-0.5 lg:hover:shadow-md">
                                        <i class="fab fa-whatsapp text-sm"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <!-- Ulasan Section -->
            <section>
                <div class="flex items-center mb-6">
                    <div class="flex items-center gap-3 border-l-4 border-primary pl-3">
                        <h2 class="text-xl lg:text-2xl font-bold text-slate-800">Ulasan</h2>
                    </div>
                </div>

                <div class="relative">
                    <div class="swiper reviewSwiper w-full">
                        <div class="swiper-wrapper py-4">
                            @foreach ($reviews->chunk(4) as $chunk)
                                <div class="swiper-slide">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 px-2">
                                        @foreach ($chunk as $item)
                                            <div
                                                class="bg-white rounded-xl p-6 shadow-md border border-slate-100 animate-fade-in-up lg:hover:-translate-y-1 transition-all duration-300 lg:hover:shadow-xl">
                                                <div class="flex items-start gap-4 mb-4">
                                                    <div class="relative">
                                                        <img src="{{ $item->review_avatar
                                                            ? asset('storage/' . $item->review_avatar)
                                                            : 'https://ui-avatars.com/api/?name=' . urlencode($item->review_fullname) }}"
                                                            class="w-12 h-12 rounded-full object-cover border-2 border-white shadow-sm">
                                                        <div
                                                            class="w-3 h-3 bg-green-500 rounded-full border-2 border-white absolute -bottom-1 -right-1">
                                                        </div>
                                                    </div>
                                                    <div class="flex-1">
                                                        <div class="flex items-center justify-between mb-1">
                                                            <h4 class="font-bold text-slate-800 text-sm">
                                                                {{ $item->review_fullname }}
                                                            </h4>
                                                            <div class="flex text-xs">
                                                                @for ($i = 1; $i <= 5; $i++)
                                                                    <i
                                                                        class="{{ $i <= $item->review_rating ? 'fas fa-star text-yellow-500' : 'far fa-star text-gray-300' }}"></i>
                                                                @endfor
                                                            </div>
                                                        </div>
                                                        <span
                                                            class="bg-indigo-100 text-indigo-700 text-[10px] font-bold px-2 py-0.5 rounded">
                                                            Pojok Wirausaha Purwakarta
                                                        </span>
                                                    </div>
                                                </div>
                                                <p class="text-slate-500 text-sm italic line-clamp-4">
                                                    "{{ strip_tags($item->short_content) }}"
                                                </p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            <!-- Dukungan & Kerja Sama Section -->
            <section class="py-12 bg-gradient-to-b from-white to-slate-50 rounded-2xl border border-slate-100">
                <div class="max-w-[1920px] mx-auto px-4 sm:px-8 lg:px-12">
                    <div class="text-center mb-10">
                        <div class="flex items-center justify-center gap-3 mb-4">
                            <div
                                class="w-10 h-10 bg-primary text-white rounded-lg flex items-center justify-center font-bold text-xl">
                                <i class="fas fa-handshake"></i>
                            </div>
                            <h2 class="text-xl lg:text-2xl font-bold text-slate-800">Dukungan & Kerja Sama</h2>
                        </div>
                        <p class="text-slate-600 max-w-3xl mx-auto text-md animate-fade-in-up">NUPARIS | Support Your
                            Company Goals</p>
                    </div>

                    <div class="space-y-6 mb-8 overflow-hidden">
                        @foreach ($partnerLayers as $index => $layer)
                            <div class="marquee-container overflow-hidden relative">
                                <div
                                    class="marquee-track flex {{ $index % 2 === 0 ? 'animate-scroll-right' : 'animate-scroll-left' }} lg:hover:[animation-play-state:paused]">

                                    <div class="marquee-set flex">
                                        @foreach ($layer as $partner)
                                            <a href="{{ $partner->partner_url ?? '#' }}" target="_blank"
                                                class="partner-card bg-white rounded-xl p-6 shadow-sm border border-slate-200 lg:hover:shadow-xl lg:hover:scale-105 transition-all duration-300 flex items-center justify-center w-64 h-40 mx-4 flex-shrink-0">
                                                <div class="text-center">
                                                    <img src="{{ asset('storage/' . $partner->partner_image) }}"
                                                        alt="{{ $partner->partner_name }}"
                                                        class="w-20 h-20 object-contain mx-auto mb-2">

                                                    <h4 class="font-bold text-slate-800">
                                                        {{ $partner->partner_name }}
                                                    </h4>

                                                    @if ($partner->partner_category)
                                                        <p class="text-xs text-slate-500 mt-1">
                                                            {{ $partner->partner_category }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="text-center mt-12 text-sm text-slate-500 mb-6">
                        <p><i class="fas fa-hand-pointer mr-2"></i>Hover untuk pause • Klik logo untuk mengunjungi
                            website</p>
                    </div>
                </div>
            </section>

            <section>
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-3 border-l-4 border-primary pl-3">
                        <h2 class="text-xl lg:text-2xl font-bold text-slate-800">Lokasi Kantor</h2>
                    </div>
                </div>

                <div class="hidden lg:grid grid-cols-5 gap-6">
                    <div class="lg:col-span-2 lg:max-h-[560px] lg:overflow-y-auto lg:pr-1">
                        @foreach ($offices->grouped as $region => $regionOffices)
                            <div class="sticky top-0 z-10 bg-slate-50/95 backdrop-blur-sm px-1 py-2 mb-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-primary">
                                    {{ $region }}
                                </span>
                                <span class="text-[10px] text-slate-400 ml-1">
                                    ({{ $regionOffices->count() }} kantor)
                                </span>
                            </div>

                            <div class="flex flex-col gap-3 mb-4">
                                @foreach ($regionOffices as $item)
                                    <button type="button"
                                        class="office-list-item {{ $loop->parent->first && $loop->first ? 'border-primary bg-primary/5 shadow-md' : '' }} w-full text-left bg-white rounded-xl border border-slate-100 shadow-sm p-3 sm:p-4 flex items-center justify-between transition-all duration-300 hover:shadow-md hover:border-primary/30 outline-none focus:outline-none focus:ring-0 focus-visible:outline-none active:outline-none active:shadow-sm group"
                                        data-map-src="{{ $item['office_embed_src'] }}"
                                        data-map-external="{{ $item['office_external_url'] }}">

                                        <div class="flex-1 min-w-0">
                                            <h3 class="font-bold text-slate-800 text-sm mb-1">
                                                {{ $item['office_name'] }}
                                            </h3>
                                            <p class="text-slate-500 text-xs mb-2">
                                                <i
                                                    class="fas fa-map-marker-alt text-primary mr-1"></i>{{ $item['office_address'] }}
                                            </p>
                                            @if (!empty($item['office_phone']))
                                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                                                    <a href="tel:{{ $item['office_phone'] }}"
                                                        onclick="event.stopPropagation()"
                                                        class="text-slate-500 text-xs hover:text-primary transition">
                                                        <i
                                                            class="fas fa-phone-alt mr-1"></i>{{ $item['office_phone'] }}
                                                    </a>
                                                </div>
                                            @endif
                                        </div>

                                        <a href="{{ $item['office_external_url'] }}" target="_blank"
                                            onclick="event.stopPropagation()"
                                            class="flex-shrink-0 w-10 h-10 rounded-full bg-primary/10 text-primary hover:bg-primary hover:text-white transition-all duration-200 flex items-center justify-center ml-3">
                                            <i
                                                class="fas fa-chevron-right text-sm group-hover:translate-x-0.5 transition-transform duration-200"></i>
                                        </a>
                                    </button>
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                    <div class="lg:col-span-3">
                        <div class="lg:sticky lg:top-24">
                            <div id="sharedOfficeMapWrap"
                                class="relative rounded-xl shadow-sm border border-slate-100 h-[560px] bg-slate-100">
                                <iframe id="sharedOfficeMap" src="{{ $offices->first_embed }}"
                                    class="w-full h-full border-0 pointer-events-auto" loading="lazy"
                                    referrerpolicy="no-referrer-when-downgrade" allowfullscreen
                                    title="Peta Lokasi Kantor">
                                </iframe>
                                <a href="{{ $offices->first_external }}" id="sharedOfficeMapExternal"
                                    target="_blank"
                                    class="absolute bottom-3 right-3 bg-white/95 backdrop-blur-sm text-primary text-xs font-bold px-3 py-2 rounded-lg shadow-sm hover:bg-white transition z-10">
                                    <i class="fas fa-up-right-from-square mr-1"></i>Buka di Google Maps
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:hidden swiper pb-9" id="lokasiMobileSwiper">
                    <div class="swiper-wrapper">
                        @foreach ($offices->offices as $index => $item)
                            <div class="swiper-slide">
                                <div
                                    class="relative rounded-2xl overflow-hidden shadow-[0_12px_32px_rgba(0,0,0,0.12)] h-[260px] after:content-[''] after:absolute after:inset-0 after:bg-gradient-to-t after:from-slate-900/40 after:to-transparent after:pointer-events-none">
                                    <iframe src="{{ $item['office_embed_src'] }}"
                                        class="w-full h-full border-0 pointer-events-none md:pointer-events-auto"
                                        loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen
                                        title="{{ $item['office_name'] }}">
                                    </iframe>

                                    <span
                                        class="absolute top-3 right-3 z-10 bg-slate-900/65 text-white text-[11px] font-bold px-2.5 py-1 rounded-full backdrop-blur-sm">
                                        {{ $index + 1 }}/{{ $offices->total }}
                                    </span>

                                    <span
                                        class="md:hidden absolute top-3 left-3 z-10 bg-slate-900/65 text-white text-[10px] font-semibold px-2.5 py-1 rounded-full backdrop-blur-sm">
                                        <i class="fas fa-lock mr-1"></i>Peta terkunci di HP
                                    </span>

                                    <span
                                        class="absolute left-3 bottom-3 z-10 bg-white/95 text-slate-900 text-xs font-semibold px-3 py-1.5 rounded-full inline-flex items-center gap-1.5 shadow-lg max-w-[calc(100%-24px)]">
                                        <i class="fas fa-map-marker-alt text-primary"></i>
                                        <span class="truncate">{{ $item['office_name'] }}@if (!empty($item['office_region']))
                                                — {{ $item['office_region'] }}
                                            @endif
                                        </span>
                                    </span>

                                    <a href="{{ $item['office_external_url'] }}" target="_blank"
                                        class="absolute bottom-3 right-3 z-10 bg-white/95 text-red-600 text-[11px] font-bold px-2.5 py-1.5 rounded-full shadow-lg">
                                        <i class="fas fa-up-right-from-square mr-1"></i>Buka
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="swiper-pagination mt-2 relative"></div>
                </div>
            </section>

            <div class="mt-12 text-center">
                <div class="bg-gradient-to-r from-primary to-red-500 rounded-2xl p-8 text-white">
                    <h3 class="text-xl lg:text-3xl font-bold mb-4">Informasi Seputar nuparis</h3>
                    <p class="mb-6 max-w-2xl mx-auto">Bergabunglah dengan jaringan mitra kami untuk
                        bersama-sama membangun layanan publik yang lebih baik.</p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <button id="openProposalModal"
                            class="bg-white text-primary font-bold px-6 py-3 rounded-lg hover:bg-slate-100 hover:shadow-md transition-all duration-300 cursor-pointer">
                            <i class="fas fa-envelope mr-2"></i>
                            <span class="text-md font-bold lg:text-md">Ajukan Proposal Kerja Sama</span>
                        </button>
                        <a href="{{ asset('storage/' . $infos->partner_guide) }}"
                            class="bg-transparent border-2 border-white text-white font-bold px-6 py-3 rounded-lg hover:bg-white/10 hover:shadow-md transition-all duration-300">
                            <i class="fas fa-download mr-2"></i>
                            <span class="text-md font-bold lg:text-md">Download Panduan Mitra</span>
                        </a>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        @include('front-end.layouts.components.footer')
    </div>

    <!-- MODAL POPUP PROPOSAL -->
    <div id="proposalModal" class="fixed inset-0 z-[100] hidden overflow-y-auto">
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity"></div>

        <div class="flex min-h-full items-center justify-center p-4 text-center">
            <div
                class="relative w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all">

                <div class="border-b border-gray-200 bg-white px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-primary to-red-500">
                                <i class="fas fa-envelope text-white text-lg"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Ajukan Proposal Kerja Sama</h3>
                                <p class="text-sm text-gray-500">
                                    Kepada: <span class="font-medium text-gray-700">{{ $infos->email }}</span>
                                </p>
                            </div>
                        </div>
                        <button id="closeModal" type="button"
                            class="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>

                <form id="proposalForm" class="bg-white">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <div class="flex items-center space-x-3">
                            <span class="text-sm font-medium text-gray-700">Judul:</span>
                            <input type="text" id="judul" name="judul" placeholder="Judul Proposal"
                                class="flex-1 border-0 text-lg font-semibold text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-0"
                                required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 border-b border-gray-200 p-6 md:grid-cols-2">
                        <div>
                            <label for="email" class="mb-2 block text-sm font-medium text-gray-700">
                                <i class="fas fa-envelope text-primary mr-2"></i>Email Anda
                            </label>
                            <input type="email" id="email" name="email"
                                class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:ring-2 focus:ring-primary/20"
                                placeholder="nama@email.com" required>
                        </div>
                        <div>
                            <label for="no_hp" class="mb-2 block text-sm font-medium text-gray-700">
                                <i class="fas fa-phone text-primary mr-2"></i>Nomor WhatsApp
                            </label>
                            <input type="tel" id="no_hp" name="no_hp"
                                class="w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-primary focus:ring-2 focus:ring-primary/20"
                                placeholder="0812-3456-7890" required>
                        </div>
                    </div>

                    <div class="p-6">
                        <label for="body" class="mb-3 block text-sm font-medium text-gray-700">
                            Isi Proposal
                        </label>
                        <div class="rounded-lg border border-gray-300">
                            <textarea id="body" name="body" rows="8"
                                class="w-full resize-none rounded-lg border-0 p-4 text-gray-700 placeholder:text-gray-400 focus:outline-none focus:ring-0"
                                placeholder="Tuliskan detail proposal kerja sama Anda di sini..." required></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-between border-t border-gray-200 bg-gray-50 px-6 py-4">
                        <div class="text-sm text-gray-500">
                            <i class="fas fa-info-circle mr-2"></i>
                            Data akan dikirim ke sistem NUPARIS
                        </div>
                        <div class="flex space-x-3">
                            <button type="button" id="cancelBtn"
                                class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Batal
                            </button>
                            <button type="submit"
                                class="rounded-lg bg-gradient-to-r from-primary to-red-500 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:opacity-90">
                                <i class="fas fa-paper-plane mr-2"></i>Kirim Proposal
                            </button>
                        </div>
                    </div>
                </form>

                <div id="loadingState" class="hidden bg-white p-12">
                    <div class="flex flex-col items-center justify-center space-y-4">
                        <div class="h-12 w-12 animate-spin rounded-full border-4 border-primary border-t-transparent">
                        </div>
                        <div class="text-center">
                            <p class="font-medium text-gray-900">Mengirim proposal...</p>
                            <p class="mt-1 text-sm text-gray-500">Harap tunggu sebentar</p>
                        </div>
                    </div>
                </div>

                <div id="successState" class="hidden bg-white p-12">
                    <div class="flex flex-col items-center justify-center space-y-6">
                        <div class="flex h-20 w-20 items-center justify-center rounded-full bg-green-100">
                            <i class="fas fa-check text-3xl text-green-600"></i>
                        </div>
                        <div class="text-center">
                            <h3 class="text-xl font-semibold text-gray-900">Proposal Terkirim!</h3>
                            <p class="mt-2 text-gray-600">
                                Proposal Anda telah berhasil dikirim ke tim NUPARIS.<br>
                                Kami akan menghubungi Anda dalam 1-2 hari kerja melalui email atau WhatsApp.
                            </p>
                            <div class="mt-4 p-4 bg-green-50 rounded-lg">
                                <p class="text-sm text-gray-700">
                                    <strong>No. Referensi:</strong> <span id="referenceNumber"></span><br>
                                    <strong>Waktu:</strong> <span id="submissionTime"></span>
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-3">
                            <button id="closeSuccess" type="button"
                                class="rounded-lg bg-primary px-6 py-3 font-medium text-white hover:bg-primary-dark">
                                Tutup
                            </button>
                            <button id="printReceipt" type="button"
                                class="rounded-lg border border-primary bg-white px-6 py-3 font-medium text-primary hover:bg-primary/10">
                                <i class="fas fa-print mr-2"></i>Cetak
                            </button>
                        </div>
                    </div>
                </div>

                <div id="errorState" class="hidden bg-white p-12">
                    <div class="flex flex-col items-center justify-center space-y-6">
                        <div class="flex h-20 w-20 items-center justify-center rounded-full bg-red-100">
                            <i class="fas fa-exclamation-triangle text-3xl text-red-600"></i>
                        </div>
                        <div class="text-center">
                            <h3 class="text-xl font-semibold text-gray-900">Gagal Mengirim</h3>
                            <p class="mt-2 text-gray-600" id="errorMessage">
                                Terjadi kesalahan saat mengirim proposal.
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <button id="retryButton" type="button"
                                class="rounded-lg bg-primary px-6 py-3 font-medium text-white hover:bg-primary-dark">
                                Coba Lagi
                            </button>
                            <button id="closeError" type="button"
                                class="rounded-lg border border-gray-300 bg-white px-6 py-3 font-medium text-gray-700 hover:bg-gray-50">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- CHAT COMPONENT -->
    @include('front-end.layouts.components.chat')

    <!-- Bottom Bar -->
    @include('front-end.layouts.components.bottom-bar')

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script src="{{ asset('assets/front-end/js/script.js') }}"></script>

    <!-- Modal JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('proposalModal');
            const form = document.getElementById('proposalForm');
            const loadingState = document.getElementById('loadingState');
            const successState = document.getElementById('successState');
            const errorState = document.getElementById('errorState');
            const errorMessage = document.getElementById('errorMessage');
            const referenceNumber = document.getElementById('referenceNumber');
            const submissionTime = document.getElementById('submissionTime');

            let currentFormData = null;

            function generateReferenceNumber() {
                const timestamp = Date.now();
                const random = Math.floor(Math.random() * 1000);
                return `NUP-${timestamp}-${random.toString().padStart(3, '0')}`;
            }

            function formatTime(date) {
                return date.toLocaleString('id-ID', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
            }

            document.getElementById('openProposalModal').addEventListener('click', function(e) {
                e.preventDefault();
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                resetForm();
            });

            const closeModal = () => {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
                resetForm();
            };

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });

            document.getElementById('closeModal').addEventListener('click', closeModal);
            document.getElementById('cancelBtn').addEventListener('click', closeModal);
            document.getElementById('closeSuccess').addEventListener('click', closeModal);
            document.getElementById('closeError').addEventListener('click', closeModal);

            document.getElementById('printReceipt').addEventListener('click', function() {
                if (currentFormData) {
                    printReceipt(currentFormData);
                }
            });

            document.getElementById('retryButton').addEventListener('click', function() {
                if (currentFormData) {
                    submitProposal(currentFormData);
                }
            });

            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                if (!validateForm()) return;

                const formData = {
                    judul: document.getElementById('judul').value.trim(),
                    email: document.getElementById('email').value.trim(),
                    no_hp: document.getElementById('no_hp').value.trim(),
                    body: document.getElementById('body').value.trim(),
                    timestamp: new Date().toLocaleString('id-ID'),
                    date: new Date().toISOString().split('T')[0],
                    time: new Date().toLocaleTimeString('id-ID'),
                    source: window.location.href,
                    page_title: document.title,
                    reference: generateReferenceNumber()
                };

                currentFormData = formData;

                submitProposal(formData);
            });

            async function submitProposal(formData) {
                form.classList.add('hidden');
                loadingState.classList.remove('hidden');
                successState.classList.add('hidden');
                errorState.classList.add('hidden');

                try {
                    await simulateAPICall();

                    const response = await saveToDatabase(formData);

                    if (response.success) {
                        loadingState.classList.add('hidden');
                        successState.classList.remove('hidden');

                        referenceNumber.textContent = formData.reference;
                        submissionTime.textContent = formatTime(new Date());

                        storeInLocalStorage(formData);

                        sendConfirmationEmail(formData);

                        console.log('✅ Proposal submitted successfully:', formData);
                    } else {
                        throw new Error(response.message || 'Gagal menyimpan data');
                    }
                } catch (error) {
                    console.error('❌ Error submitting proposal:', error);

                    loadingState.classList.add('hidden');
                    errorState.classList.remove('hidden');
                    errorMessage.textContent = error.message || 'Gagal mengirim proposal. Silakan coba lagi.';
                }
            }

            function simulateAPICall() {
                return new Promise((resolve) => {
                    setTimeout(() => {
                        resolve(true);
                    }, 1500);
                });
            }

            async function saveToDatabase(formData) {
                try {
                    const response = await fetch('/api/proposals', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                ?.content || ''
                        },
                        body: JSON.stringify({
                            title: formData.judul,
                            email: formData.email,
                            phone: formData.no_hp,
                            content: formData.body,
                            reference: formData.reference,
                            source_url: formData.source
                        })
                    });

                    const data = await response.json();
                    return data;
                } catch (error) {
                    console.log('⚠️ Using localStorage fallback');
                    return {
                        success: true,
                        message: 'Disimpan di lokal'
                    };
                }
            }

            function sendConfirmationEmail(formData) {
                fetch('/api/send-proposal-confirmation', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        email: formData.email,
                        reference: formData.reference,
                        title: formData.judul
                    })
                }).catch(err => console.log('⚠️ Email confirmation skipped:', err));
            }

            function storeInLocalStorage(data) {
                try {
                    const proposals = JSON.parse(localStorage.getItem('nuparis_proposals') || '[]');
                    proposals.push({
                        ...data,
                        id: Date.now(),
                        status: 'submitted',
                        method: 'web_form',
                        created_at: new Date().toISOString()
                    });
                    localStorage.setItem('nuparis_proposals', JSON.stringify(proposals));
                    console.log('💾 Data disimpan di localStorage');
                } catch (e) {
                    console.log('⚠️ Gagal menyimpan ke localStorage');
                }
            }

            function printReceipt(data) {
                const receiptWindow = window.open('', '_blank');
                const receiptHTML = `
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <title>Receipt Proposal - ${data.reference}</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 40px; }
                            .header { text-align: center; margin-bottom: 30px; }
                            .reference { font-size: 18px; font-weight: bold; color: #333; }
                            .info { margin: 20px 0; }
                            .label { font-weight: bold; color: #666; }
                            .value { margin-bottom: 10px; }
                            .timestamp { text-align: center; margin-top: 30px; color: #999; }
                        </style>
                    </head>
                    <body>
                        <div class="header">
                            <h2>NUPARIS - Proposal Kerja Sama</h2>
                            <div class="reference">No. Referensi: ${data.reference}</div>
                        </div>
                        <div class="info">
                            <div class="value"><span class="label">Judul:</span> ${data.judul}</div>
                            <div class="value"><span class="label">Email:</span> ${data.email}</div>
                            <div class="value"><span class="label">WhatsApp:</span> ${data.no_hp}</div>
                            <div class="value"><span class="label">Isi Proposal:</span><br>${data.body}</div>
                        </div>
                        <div class="timestamp">
                            Dikirim pada: ${data.timestamp}<br>
                            Status: Diterima
                        </div>
                    </body>
                    </html>
                `;

                receiptWindow.document.write(receiptHTML);
                receiptWindow.document.close();
                receiptWindow.print();
            }

            function validateForm() {
                const email = document.getElementById('email').value.trim();
                const phone = document.getElementById('no_hp').value.trim();
                const judul = document.getElementById('judul').value.trim();
                const body = document.getElementById('body').value.trim();

                clearErrors();

                let isValid = true;

                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!email) {
                    showError('email', 'Email wajib diisi');
                    isValid = false;
                } else if (!emailRegex.test(email)) {
                    showError('email', 'Format email tidak valid');
                    isValid = false;
                }

                const phoneRegex = /^[0-9+\-\s()]{10,15}$/;
                const cleanPhone = phone.replace(/\s/g, '');
                if (!phone) {
                    showError('no_hp', 'Nomor WhatsApp wajib diisi');
                    isValid = false;
                } else if (!phoneRegex.test(cleanPhone)) {
                    showError('no_hp', 'Format nomor tidak valid (10-15 digit)');
                    isValid = false;
                }

                if (!judul) {
                    showError('judul', 'Judul proposal wajib diisi');
                    isValid = false;
                } else if (judul.length < 5) {
                    showError('judul', 'Judul minimal 5 karakter');
                    isValid = false;
                }

                if (!body) {
                    showError('body', 'Isi proposal wajib diisi');
                    isValid = false;
                } else if (body.length < 20) {
                    showError('body', 'Isi proposal minimal 20 karakter');
                    isValid = false;
                }

                if (!isValid) {
                    const firstError = document.querySelector('.border-red-500');
                    if (firstError) {
                        firstError.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                    }
                }

                return isValid;
            }

            function showError(fieldId, message) {
                const field = document.getElementById(fieldId);
                const container = field.closest('div');

                field.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-200');
                container.classList.add('text-red-600');

                const errorDiv = document.createElement('div');
                errorDiv.className = 'mt-1 text-sm text-red-600';
                errorDiv.innerHTML = `<i class="fas fa-exclamation-circle mr-1"></i>${message}`;

                if (fieldId === 'body') {
                    field.parentNode.parentNode.appendChild(errorDiv);
                } else {
                    field.parentNode.appendChild(errorDiv);
                }
            }

            function clearErrors() {
                document.querySelectorAll('input, textarea').forEach(el => {
                    el.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-200');
                });

                document.querySelectorAll('.text-red-600').forEach(el => {
                    if (el.classList.contains('mt-1')) {
                        el.remove();
                    }
                });
            }

            function resetForm() {
                form.reset();
                form.classList.remove('hidden');
                loadingState.classList.add('hidden');
                successState.classList.add('hidden');
                errorState.classList.add('hidden');
                clearErrors();
                currentFormData = null;
            }
        });

        function formatShortRupiah(amount) {
            const units = [
                [1e9, ' M'],
                [1e6, ' Jt']
            ];
            for (let [divisor, unit] of units) {
                if (amount >= divisor) {
                    let val = amount / divisor;
                    let decimals = Number.isInteger(val) ? 0 : 1;
                    return 'Rp ' + val.toFixed(decimals).replace('.', ',') + unit;
                }
            }
            return 'Rp ' + amount.toLocaleString('id-ID');
        }

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('[data-price]').forEach(el => {
                const price = parseInt(el.dataset.price, 10);
                el.innerText = formatShortRupiah(price);
            });
        });


        document.addEventListener('DOMContentLoaded', function() {
            // ===== Desktop: klik item kontak -> update peta bersama =====
            const sharedMap = document.getElementById('sharedOfficeMap');
            const sharedMapExternal = document.getElementById('sharedOfficeMapExternal');

            document.querySelectorAll('.office-list-item').forEach(item => {
                item.addEventListener('click', function() {
                    document.querySelectorAll('.office-list-item.active').forEach(li => {
                        li.classList.remove('active', 'border-primary', 'bg-primary/5',
                            'shadow-md');
                    });
                    this.classList.add('active', 'border-primary', 'bg-primary/5', 'shadow-md');

                    if (sharedMap) {
                        sharedMap.src = this.dataset.mapSrc;
                    }
                    if (sharedMapExternal) {
                        sharedMapExternal.href = this.dataset.mapExternal;
                    }
                });
            });

            // ===== Mobile: init slider lokasi kantor =====
            if (document.getElementById('lokasiMobileSwiper')) {
                new Swiper('#lokasiMobileSwiper', {
                    slidesPerView: 1.08,
                    spaceBetween: 14,
                    centeredSlides: false,
                    pagination: {
                        el: '#lokasiMobileSwiper .swiper-pagination',
                        clickable: true,
                        renderBullet: function(index, className) {
                            return '<span class="' + className +
                                ' inline-block w-2 h-2 rounded-full bg-slate-400/50 transition-all duration-300 [&.swiper-pagination-bullet-active]:w-5 [&.swiper-pagination-bullet-active]:!bg-primary [&.swiper-pagination-bullet-active]:!opacity-100"></span>';
                        }
                    },
                    breakpoints: {
                        480: {
                            slidesPerView: 1.2
                        }
                    }
                });
            }
        });
    </script>
</body>

</html>
