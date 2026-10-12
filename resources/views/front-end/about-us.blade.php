<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    @include('front-end.layouts.components.seo-meta')
    <link rel="icon" type="image/x-icon" href="{{ asset('storage/' . $infos->meta_image) }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="{{ asset('assets/front-end/js/configtailwind.js') }}"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="{{ asset('assets/front-end/css/style.css') }}" rel="stylesheet" />
</head>

<body class="font-sans text-gray-800 bg-gray-50 h-full overflow-x-hidden">

    @include('front-end.layouts.components.header')

    <div class="h-full overflow-y-auto pt-0 lg:pt-0">

        <!-- Hero -->
        <section class="bg-gradient-to-r from-red-600 to-red-700 text-white pt-6 pb-12 lg:pt-24 lg:pb-16">
            <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between gap-4 mb-2">
                    <nav class="flex items-center text-sm text-red-100">
                        <a href="{{ route('landingpage') }}" class="lg:hover:text-white transition-colors">
                            <x-bi id="Beranda" en="Home" />
                        </a>
                        <i class="fas fa-chevron-right mx-2 text-xs"></i>
                        <span><x-bi id="Tentang Kami" en="About Us" /></span>
                    </nav>

                    <!-- Pilihan bahasa -->
                    <div class="inline-flex rounded-lg bg-white/10 p-1 text-sm font-semibold" role="group"
                        aria-label="Bahasa / Language">
                        <button type="button" data-lang-btn="id"
                            class="px-3 py-1 rounded-md transition-colors">ID</button>
                        <button type="button" data-lang-btn="en"
                            class="px-3 py-1 rounded-md transition-colors">EN</button>
                    </div>
                </div>

                <h1 class="text-3xl lg:text-4xl font-bold mb-3"><x-bi id="Tentang Kami" en="About Us" /></h1>
                <p class="text-red-100 max-w-3xl">
                    <x-bi :text="$about['hero']" />
                </p>
            </div>
        </section>

        <main class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-12">
            <!-- Profil Singkat -->
            <section class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                <div class="relative rounded-2xl overflow-hidden shadow-lg h-72 sm:h-96">
                    <img src="{{ $about['image'] }}" alt="NUPARIS" class="w-full h-full object-cover">
                    <div class="absolute top-4 left-4 z-10 opacity-80">
                        <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center shadow-sm">
                            <img src="{{ asset('storage/' . $infos->meta_image) }}" alt="NUPARIS Logo" class="w-8 h-8">
                        </div>
                    </div>
                </div>

                <div>
                    <div class="flex items-center gap-3 border-l-4 border-primary pl-3 mb-5">
                        <h2 class="text-xl lg:text-2xl font-bold text-gray-800">
                            <x-bi id="Siapa Kami" en="Who We Are" />
                        </h2>
                    </div>
                    <div class="space-y-4 text-gray-600 leading-relaxed">
                        @foreach ($about['who']['id'] as $i => $paragraph)
                            <p><x-bi :id="$paragraph" :en="$about['who']['en'][$i] ?? $paragraph" /></p>
                        @endforeach
                    </div>
                </div>
            </section>

            <!-- Layanan Utama -->
            @if ($about['services'])
                <section id="layanan" class="scroll-mt-24">
                    <div class="flex items-center gap-3 border-l-4 border-primary pl-3 mb-6">
                        <h2 class="text-xl lg:text-2xl font-bold text-gray-800">
                            <x-bi id="Layanan Utama Kami" en="Our Core Services" />
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        @foreach ($about['services'] as $service)
                            <div
                                class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 lg:hover:-translate-y-1 lg:hover:shadow-xl transition-all duration-300">
                                <div class="flex items-start justify-between gap-4 mb-4">
                                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                        <i class="fas {{ $service['icon'] ?? 'fa-circle' }} text-red-600 text-lg"></i>
                                    </div>
                                    @if (!empty($service['kbli']))
                                        <span
                                            class="text-xs font-semibold text-red-700 bg-red-50 border border-red-100 rounded-full px-3 py-1">
                                            {{ $service['kbli'] }}
                                        </span>
                                    @endif
                                </div>
                                <h3 class="font-bold text-gray-800 text-lg mb-2">
                                    <x-bi :id="$service['title_id'] ?? ''" :en="$service['title_en'] ?? ''" />
                                </h3>
                                <p class="text-gray-500 leading-relaxed">
                                    <x-bi :id="$service['desc_id'] ?? ''" :en="$service['desc_en'] ?? ''" />
                                </p>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <!-- Mengapa Memilih Nuparis -->
            @if ($about['values'])
                <section>
                    <div class="flex items-center gap-3 border-l-4 border-primary pl-3 mb-6">
                        <h2 class="text-xl lg:text-2xl font-bold text-gray-800">
                            <x-bi id="Mengapa Memilih Nuparis?" en="Why Choose Nuparis?" />
                        </h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @foreach ($about['values'] as $value)
                            <div
                                class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex gap-4 lg:hover:-translate-y-1 lg:hover:shadow-xl transition-all duration-300">
                                <div class="w-10 h-10 shrink-0 bg-red-100 rounded-lg flex items-center justify-center">
                                    <i class="fas {{ $value['icon'] ?? 'fa-circle' }} text-red-600"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-800 mb-1">
                                        <x-bi :id="$value['title_id'] ?? ''" :en="$value['title_en'] ?? ''" />
                                    </h3>
                                    <p class="text-sm text-gray-500 leading-relaxed">
                                        <x-bi :id="$value['desc_id'] ?? ''" :en="$value['desc_en'] ?? ''" />
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            <!-- Visi & Misi -->
            <section>
                <div class="flex items-center gap-3 border-l-4 border-primary pl-3 mb-6">
                    <h2 class="text-xl lg:text-2xl font-bold text-gray-800">
                        <x-bi id="Visi & Misi" en="Vision & Mission" />
                    </h2>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div
                        class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 lg:hover:-translate-y-1 lg:hover:shadow-xl transition-all duration-300">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-eye text-red-600"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800"><x-bi id="Visi" en="Vision" /></h3>
                        </div>
                        <p class="text-gray-600 leading-relaxed">
                            <x-bi :text="$about['vision']" />
                        </p>
                    </div>

                    <div
                        class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 lg:hover:-translate-y-1 lg:hover:shadow-xl transition-all duration-300">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-bullseye text-red-600"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800"><x-bi id="Misi" en="Mission" /></h3>
                        </div>
                        <ol class="space-y-3 text-gray-600">
                            @foreach ($about['missions'] as $i => $misi)
                                <li class="flex items-start gap-3">
                                    <span
                                        class="w-6 h-6 shrink-0 rounded-full bg-red-100 text-red-700 text-xs font-bold flex items-center justify-center mt-0.5">{{ $i + 1 }}</span>
                                    <span><x-bi :text="$misi" /></span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-3 justify-end mt-6">
                    <button onclick="openPrivacyModal()"
                        class="inline-flex items-center justify-center bg-white border-2 border-primary text-primary font-semibold px-5 py-3 rounded-lg lg:hover:bg-primary lg:hover:text-white transition-colors">
                        <i class="fas fa-user-shield mr-2"></i><x-bi id="Kebijakan Privasi" en="Privacy Policy" />
                    </button>
                    <button onclick="openTermsModal()"
                        class="inline-flex items-center justify-center bg-white border-2 border-primary text-primary font-semibold px-5 py-3 rounded-lg lg:hover:bg-primary lg:hover:text-white transition-colors">
                        <i class="fas fa-file-contract mr-2"></i><x-bi id="Syarat dan Ketentuan"
                            en="Terms and Conditions" />
                    </button>
                </div>
            </section>

            <!-- CTA -->
            <section class="text-center">
                <div class="bg-gradient-to-r from-primary to-red-500 rounded-2xl p-8 text-white">
                    <h3 class="text-xl lg:text-3xl font-bold mb-4">
                        <x-bi :text="$about['cta']['title']" />
                    </h3>
                    <p class="mb-6 max-w-2xl mx-auto">
                        <x-bi :text="$about['cta']['text']" />
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <button type="button" onclick="document.getElementById('chatToggle')?.click()"
                            class="bg-white text-primary font-bold px-6 py-3 rounded-lg hover:bg-slate-100 hover:shadow-md transition-all duration-300">
                            <i class="fas fa-ticket-alt mr-2"></i><x-bi id="Buat Tiket Baru"
                                en="Create New Ticket" />
                        </button>
                    </div>
                </div>
            </section>
        </main>

        @include('front-end.layouts.components.footer')
    </div>

    @include('front-end.layouts.components.chat')
    @include('front-end.layouts.components.bottom-bar')

    <script>
        (function() {
            var KEY = 'nuparis_lang';

            function setLang(lang) {
                document.documentElement.lang = lang;
                document.querySelectorAll('[data-lang]').forEach(function(el) {
                    el.classList.toggle('hidden', el.dataset.lang !== lang);
                });
                document.querySelectorAll('[data-lang-btn]').forEach(function(btn) {
                    var active = btn.dataset.langBtn === lang;
                    btn.classList.toggle('bg-white', active);
                    btn.classList.toggle('text-red-700', active);
                    btn.classList.toggle('text-white', !active);
                    btn.setAttribute('aria-pressed', active);
                });
                try {
                    localStorage.setItem(KEY, lang);
                } catch (e) {}
            }

            document.querySelectorAll('[data-lang-btn]').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    setLang(btn.dataset.langBtn);
                });
            });

            var saved = 'id';
            try {
                saved = localStorage.getItem(KEY) || 'id';
            } catch (e) {}
            setLang(saved);
        })();
    </script>
</body>

</html>
