@extends('layouts.site')
@section('content')
<div class="hero-section">
    <div class="container">
        <h1 class="hero-title">{{ $settings['hero_title'] ?? 'Inovasi & Riset Berkualitas untuk Masa Depan' }}</h1>
        <p class="hero-subtitle">{{ $settings['hero_subtitle'] ?? 'Daygun Research Center berfokus pada studi multidisiplin, publikasi ilmiah, dan solusi teknologi berkelanjutan.' }}</p>
        <div class="hero-btns">
            <a href="{{ url('/') }}/bidang-penelitian" class="btn btn-primary"><i class="fas fa-search-location"></i> Jelajahi Riset</a>
            <a href="{{ url('kontak') }}" class="btn btn-outline"><i class="fas fa-paper-plane" aria-hidden="true"></i> Hubungi Kami</a>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- BIDANG KEGIATAN                              -->
<!-- ============================================ -->
<section class="section">
    <div class="section-header">
        <div class="section-divider"></div>
        <h2 class="section-title">Bidang Kegiatan</h2>
    </div>
    <div style="display: flex; justify-content: center; gap: 1.5rem; flex-wrap: wrap; max-width: 900px; margin: 0 auto;">
        <div style="flex: 1; min-width: 160px;">
            <div style="height: 48px; border-radius: 24px; background: linear-gradient(135deg, #1e3a8a, #0284c7); display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <i class="fas fa-flask" style="font-size: 1.1rem; color: white;"></i>
                <span style="font-weight: 600; font-size: 0.9rem; color: white;">Penelitian</span>
            </div>
        </div>
        <div style="flex: 1; min-width: 160px;">
            <div style="height: 48px; border-radius: 24px; background: linear-gradient(135deg, #0369a1, #0ea5e9); display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <i class="fas fa-chalkboard-teacher" style="font-size: 1.1rem; color: white;"></i>
                <span style="font-weight: 600; font-size: 0.9rem; color: white;">Pendidikan</span>
            </div>
        </div>
        <div style="flex: 1; min-width: 160px;">
            <div style="height: 48px; border-radius: 24px; background: linear-gradient(135deg, #0f766e, #14b8a6); display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <i class="fas fa-hands-helping" style="font-size: 1.1rem; color: white;"></i>
                <span style="font-weight: 600; font-size: 0.9rem; color: white;">Pemberdayaan</span>
            </div>
        </div>
        <div style="flex: 1; min-width: 160px;">
            <div style="height: 48px; border-radius: 24px; background: linear-gradient(135deg, #7c3aed, #a78bfa); display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <i class="fas fa-book" style="font-size: 1.1rem; color: white;"></i>
                <span style="font-weight: 600; font-size: 0.9rem; color: white;">Penerbitan</span>
            </div>
        </div>
    </div>
</section>

<!-- ============================================ -->
<!-- PROFIL LEMBAGA                               -->
<!-- ============================================ -->
<section class="section" id="profil-lembaga">
    <div class="section-header">
        <div class="section-divider"></div>
        <h2 class="section-title">Profil Lembaga</h2>
        <p class="section-subtitle">Mengenal lebih dekat Daygun Research Center (DRC)</p>
    </div>

    <!-- Identitas & Deskripsi -->
    <div class="profile-intro">
        <div class="profile-icon-box">
            <i class="fas fa-university"></i>
        </div>
        <div class="profile-intro-text">
            <h3>LEMBAGA DAYGUN RESEARCH CENTER</h3>
            <p class="profile-tagline">{{ $settings['site_tagline'] ?? 'Lembaga Penelitian Independen untuk Pembangunan Berkelanjutan' }}</p>
            <p>{{ $settings['org_purpose'] ?? 'Menyelenggarakan penelitian ilmiah yang berkualitas untuk mendukung pembangunan nasional dan kesejahteraan masyarakat.' }}</p>
        </div>
    </div>

    <!-- Detail Grid -->
    <div class="profile-details-grid">
        <div class="profile-detail-card">
            <div class="detail-icon"><i class="fas fa-file-signature"></i></div>
            <h4>Akta Pendirian</h4>
            <p>{{ $settings['org_akta'] ?? '-' }}</p>
            <small>Notaris: {{ $settings['org_notaris'] ?? '-' }}</small>
        </div>
        <div class="profile-detail-card">
            <div class="detail-icon"><i class="fas fa-calendar-alt"></i></div>
            <h4>Tahun Berdiri</h4>
            <p>27 Juli 2023</p>
            <small>Periode Kepengurusan: {{ $settings['org_management_period'] ?? '-' }}</small>
        </div>
        <div class="profile-detail-card">
            <div class="detail-icon"><i class="fas fa-balance-scale"></i></div>
            <h4>Legalitas</h4>
            <p>{{ $settings['org_legalitas'] ?? '-' }}</p>
            <small>NPWP: {{ $settings['org_npwp'] ?? '-' }}</small>
        </div>
        <div class="profile-detail-card">
            <div class="detail-icon"><i class="fas fa-id-badge"></i></div>
            <h4>Kode BRIN</h4>
            <p>{{ $settings['org_brin_code'] ?? '-' }}</p>
            <small>Terintegrasi BRIN</small>
        </div>
        <div class="profile-detail-card">
            <div class="detail-icon"><i class="fas fa-file-alt"></i></div>
            <h4>NIB</h4>
            <p>0802240016055</p>
        </div>
        <div class="profile-detail-card">
            <div class="detail-icon"><i class="fas fa-certificate"></i></div>
            <h4>Sertifikat Standar</h4>
            <p>08022400160550001</p>
        </div>
    </div>

</section>

<!-- ============================================ -->
<!-- STRUKTUR ORGANISASI                          -->
<!-- ============================================ -->
<section class="section">
    <div class="section-header">
        <div class="section-divider"></div>
        <h2 class="section-title">Struktur Organisasi</h2>
        <p class="section-subtitle">Susunan pengurus DAYGUN RESEARCH CENTER</p>
    </div>

    <div class="org-table">
        <div class="org-row org-row-header">
            <div class="org-cell org-cell-role">Jabatan</div>
            <div class="org-cell org-cell-name">Nama</div>
            <div class="org-cell org-cell-edu">Pendidikan</div>
            <div class="org-cell org-cell-prof">Profesi</div>
        </div>
        <div class="org-row">
            <div class="org-cell org-cell-role"><span class="org-badge" style="background:#1e3a8a;">Pembina</span></div>
            <div class="org-cell org-cell-name">Prof. Drs. Mahyuni, M.A., Ph.D.</div>
            <div class="org-cell org-cell-edu">Ph.D.</div>
            <div class="org-cell org-cell-prof">Akademisi</div>
        </div>
        <div class="org-row">
            <div class="org-cell org-cell-role"><span class="org-badge" style="background:#0369a1;">Penasehat</span></div>
            <div class="org-cell org-cell-name">Dr. Burhanuddin, M.Hum.</div>
            <div class="org-cell org-cell-edu">Doktoral (S3)</div>
            <div class="org-cell org-cell-prof">Akademisi</div>
        </div>
        <div class="org-row">
            <div class="org-cell org-cell-role"><span class="org-badge" style="background:#0f766e;">Direktur</span></div>
            <div class="org-cell org-cell-name">Dr. Muh. Azkar, M.Pd.I.</div>
            <div class="org-cell org-cell-edu">Doktoral (S3)</div>
            <div class="org-cell org-cell-prof">Akademisi UIN Mataram</div>
        </div>
        <div class="org-row">
            <div class="org-cell org-cell-role"><span class="org-badge" style="background:#7c3aed;">Wakil Direktur</span></div>
            <div class="org-cell org-cell-name">Zulhadi, M.Ip.</div>
            <div class="org-cell org-cell-edu">Magister (S2), S3 (Ongoing)</div>
            <div class="org-cell org-cell-prof">Akademisi Univ. 45 Mataram</div>
        </div>
        <div class="org-row">
            <div class="org-cell org-cell-role"><span class="org-badge" style="background:#b45309;">Sekretaris</span></div>
            <div class="org-cell org-cell-name">Satria Efendi, S.Pd.</div>
            <div class="org-cell org-cell-edu">Sarjana (S1), S2 (Ongoing)</div>
            <div class="org-cell org-cell-prof">Jurnalis</div>
        </div>
        <div class="org-row">
            <div class="org-cell org-cell-role"><span class="org-badge" style="background:#be123c;">Bendahara</span></div>
            <div class="org-cell org-cell-name">Eky Roza Paradistia, S.Stat.</div>
            <div class="org-cell org-cell-edu">Sarjana (S1)</div>
            <div class="org-cell org-cell-prof">Jurnalis</div>
        </div>
    </div>
</section>


<!-- ============================================ -->
<!-- PROGRAM KERJA                                -->
<!-- ============================================ -->
@if (!empty($workPrograms))
<section class="section work-program-section" id="program-kerja">
    <div class="container">
    <div class="section-header">
        <div class="section-divider"></div>
        <h2 class="section-title">Program Kerja</h2>
        <p class="section-subtitle">Program kerja bidang penelitian, pendidikan, pemberdayaan, dan humas</p>
    </div>
    <div class="wp-grid">
        @php
        $wpIcons = ['fa-flask', 'fa-chalkboard-teacher', 'fa-hands-helping', 'fa-handshake'];
        $wpColors = ['#1e3a8a', '#0369a1', '#0f766e', '#7c3aed'];
        @endphp
        @foreach ($workPrograms as $wp)
        @php
            $description = html_entity_decode(strip_tags($wp['description'] ?? ''), ENT_QUOTES, 'UTF-8');
            $subItems = array_values(array_filter(array_map('trim', explode("\n", $description)), fn ($line) => preg_match('/^(PL|PP|PM|HM)\d/i', $line)));
            $icon = $wpIcons[$loop->index % count($wpIcons)];
            $color = $wpColors[$loop->index % count($wpColors)];
        @endphp
            <article class="wp-card" style="--program-color: {{ $color }};">
                <div class="wp-header">
                    <div class="wp-icon">
                        <i class="fas {{ $icon }}" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h3 class="wp-title">{{ $wp['title'] }}</h3>
                    </div>
                </div>
                @if (!empty($subItems))
                    <ul class="wp-list">
                        @foreach ($subItems as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                @elseif ($description !== '')
                    <p class="program-description">{{ $description }}</p>
                @endif
            </article>
        @endforeach
    </div>
    <div class="work-program-actions">
        <a href="{{ url('/') }}/program-kerja" class="btn-team">
            <i class="fas fa-clipboard-list"></i> Lihat Seluruh Program Kerja <i class="fas fa-arrow-right"></i>
        </a>
    </div>
    </div>
</section>
@endif


@endsection
