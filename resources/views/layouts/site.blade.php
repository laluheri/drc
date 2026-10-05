<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Daygun Research Center' }}</title>
    <meta name="description" content="{{ $item->meta_description ?? $metaDesc ?? ($settings['meta_description'] ?? 'Daygun Research Center - Lembaga Penelitian dan Riset') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @unless(request()->routeIs('home'))<link rel="stylesheet" href="{{ asset('css/drc.css') }}">@endunless
    <link rel="stylesheet" href="{{ asset('css/reference.css') }}">
    <link rel="stylesheet" href="{{ asset('css/reference-responsive.css') }}">
</head>
<body>
    <div class="top-bar">
        <div class="container top-bar-content">
            <div>
                <i class="fas fa-envelope me-1"></i> {{ $settings['email'] ?? 'daygunrisetcenter@gmail.com' }}
                <span style="margin: 0 0.5rem;">|</span>
                <i class="fas fa-phone me-1"></i> 082339880399 (Tlp/WA)
            </div>
            <div>
                <a href="{{ url('admin') }}/login" style="color: #cbd5e1;"><i class="fas fa-user-lock"></i> Portal Admin</a>
            </div>
        </div>
    </div>

    <header>
        <div class="container">
            <nav class="navbar">
                <a href="{{ url('/') }}" class="logo-container">
                    <img src="{{ asset('logo.png') }}" alt="{{ $settings['site_name'] ?? 'DRC' }}" style="height: 55px; width: auto;">
                </a>
                <button type="button" class="mobile-menu-toggle" aria-controls="siteNavigation" aria-expanded="false" aria-label="Buka navigasi"><i class="fas fa-bars" aria-hidden="true"></i></button><ul class="nav-links" id="siteNavigation">
                    <li><a href="{{ url('/') }}" class="{{ request()->is('/') ? 'active' : '' }}">Beranda</a></li>
                    <li><a href="{{ url('/') }}/program-kerja" class="{{ request()->is('program-kerja*') ? 'active' : '' }}">Kegiatan</a></li>
                    <li><a href="{{ url('/') }}/bidang-penelitian" class="{{ request()->is('bidang-penelitian*') ? 'active' : '' }}">Riset</a></li>
                    <li><a href="{{ url('/') }}/buku" class="{{ request()->is('buku*') ? 'active' : '' }}">Buku</a></li>
                    <li><a href="{{ url('/') }}/jurnal" class="{{ request()->is('jurnal*') ? 'active' : '' }}">Journal</a></li>
                    <li><a href="{{ url('/') }}/tentang-kami" class="{{ request()->is('tentang-kami*') ? 'active' : '' }}">Tentang</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container flash-messages">@include('partials.flash')</div>

        @if(request()->routeIs('home')) @yield('content') @else <div class="container public-page-content">@yield('content')</div> @endif
    </main>

    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3>{{ $settings['site_name'] ?? 'Daygun Research Center' }}</h3>
                    <p>{{ $settings['about_short'] ?? 'Lembaga penelitian independen yang berdedikasi pada inovasi, pengembangan sains, dan teknologi masa depan.' }}</p>
                </div>
                <div class="footer-col">
                    <h3>Navigasi</h3>
                    <ul style="list-style:none;">
                        <li><a href="{{ url('/') }}/profil" style="color:#94a3b8;">Profil Lembaga</a></li>
                        <li><a href="{{ url('/') }}/visi-misi" style="color:#94a3b8;">Visi & Misi</a></li>
                        <li><a href="{{ url('/') }}/program-kerja" style="color:#94a3b8;">Program Kerja</a></li>
                        <li><a href="{{ url('/') }}/tim" style="color:#94a3b8;">Tim Pengurus</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Kontak Us</h3>
                    <p><i class="fas fa-map-marker-alt"></i> {{ $settings['contact_address'] ?? $settings['address'] ?? 'Blok B No.3-4 BTN Tanjung Indah Residence, Dusun Tanak Song Timur, Desa Jenggala, Kec. Tanjung, Kabupaten Lombok Utara' }}</p>
                    <p><i class="fas fa-envelope"></i> {{ $settings['email'] ?? 'daygunrisetcenter@gmail.com' }}</p>
                    <p><i class="fas fa-phone"></i> 082339880399 (Tlp/WA)</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} {{ $settings['site_name'] ?? 'Daygun Research Center' }}. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <a href="#" class="back-to-top" id="backToTop" title="Kembali ke atas">
        <i class="fas fa-chevron-up"></i>
    </a>

    <script>
        (function() {
            var toggle = document.querySelector('.mobile-menu-toggle');
            var navigation = document.getElementById('siteNavigation');
            if (toggle && navigation) toggle.addEventListener('click', function() { var open = toggle.getAttribute('aria-expanded') !== 'true'; toggle.setAttribute('aria-expanded', String(open)); toggle.setAttribute('aria-label', open ? 'Tutup navigasi' : 'Buka navigasi'); navigation.classList.toggle('is-open', open); });
            var btn = document.getElementById('backToTop');
            if (!btn) return;
            window.addEventListener('scroll', function() {
                if (window.scrollY > 400) {
                    btn.classList.add('visible');
                } else {
                    btn.classList.remove('visible');
                }
            });
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        })();
    </script>
</body>
</html>
