<!-- Desktop Navbar -->
<nav class="hidden lg:block fixed top-0 w-full z-50 bg-white shadow-md">
    <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <a href="{{ route('landingpage') }}" class="flex items-center gap-2">
                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm">
                    <img src="{{ asset('storage/' . $infos->meta_image) }}" alt="NUPARIS Logo" class="w-8 h-8">
                </div>
                <span class="font-bold text-2xl tracking-tight text-slate-800">NUPARIS</span>
            </a>

            <div class="flex items-center space-x-8">
                <a href="{{ route('landingpage') }}"
                    class="{{ request()->routeIs('landingpage') ? 'text-primary font-semibold border-b-2 border-primary' : 'text-slate-600 hover:text-primary' }} font-medium transition-colors duration-200">
                    Beranda
                </a>

                <a href="{{ route('news-more') }}"
                    class="{{ request()->is('news*') || request()->is('berita*') ? 'text-primary font-semibold border-b-2 border-primary' : 'text-slate-600 hover:text-primary' }} font-medium transition-colors duration-200">
                    Berita
                </a>

                <a href="{{ route('article-more') }}"
                    class="{{ request()->is('article*') || request()->is('artikel*') ? 'text-primary font-semibold border-b-2 border-primary' : 'text-slate-600 hover:text-primary' }} font-medium transition-colors duration-200">
                    Perizinan & Non Perizinan
                </a>

                <a href="{{ route('activity-more') }}"
                    class="{{ request()->is('activity*') || request()->is('aktivitas*') ? 'text-primary font-semibold border-b-2 border-primary' : 'text-slate-600 hover:text-primary' }} font-medium transition-colors duration-200">
                    Aktivitas
                </a>

                <a href="{{ route('event-more') }}"
                    class="{{ request()->is('event*') ? 'text-primary font-semibold border-b-2 border-primary' : 'text-slate-600 hover:text-primary' }} font-medium transition-colors duration-200">
                    Event
                </a>

                <a href="{{ route('property-more') }}"
                    class="{{ request()->is('property*') || request()->is('properti*') ? 'text-primary font-semibold border-b-2 border-primary' : 'text-slate-600 hover:text-primary' }} font-medium transition-colors duration-200">
                    Properti
                </a>

                <a href="{{ route('about-us') }}"
                    class="{{ request()->is('about-us*') || request()->is('about-us*') ? 'text-primary font-semibold border-b-2 border-primary' : 'text-slate-600 hover:text-primary' }} font-medium transition-colors duration-200">
                    About Us
                </a>

                @if (auth()->check())
                    <a href="https://www.nuparis.id/management"
                        class="bg-primary hover:bg-red-700 text-white px-5 py-2 rounded-full font-medium shadow-lg transition-colors duration-200">
                        Dashboard
                    </a>
                @else
                    <a href="https://www.nuparis.id/management/login"
                        class="bg-primary hover:bg-red-700 text-white px-5 py-2 rounded-full font-medium shadow-lg transition-colors duration-200">
                        Masuk
                    </a>
                @endif
            </div>
        </div>
    </div>
</nav>

<!-- Mobile Header -->
<header class="lg:hidden sticky top-0 z-40 bg-white border-b border-slate-200 pt-0">
    <div class="px-4 py-3">
        <div class="flex items-center justify-between">
            <button onclick="window.history.back()"
                class="flex items-center space-x-2 text-slate-600 lg:hover:text-primary transition-colors">
                <i class="fas fa-arrow-left text-lg"></i>
                <span class="font-medium hidden sm:inline">Kembali</span>
            </button>

            <div class="flex items-center space-x-2">
                <div class="w-8 h-8 bg-white rounded shadow-sm flex items-center justify-center">
                    <img src="{{ asset('storage/' . $infos->meta_image) }}" alt="NUPARIS Logo" class="w-6 h-6">
                </div>
                <span class="font-bold text-slate-900 text-lg">NUPARIS</span>
            </div>

            <div class="w-10"></div>
        </div>
    </div>
</header>
