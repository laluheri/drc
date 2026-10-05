<?php

return [
    'modules' => [
        'pages' => ['table' => 'pages', 'label' => 'Halaman'], 'news' => ['table' => 'news', 'label' => 'Berita'],
        'categories' => ['table' => 'categories', 'label' => 'Kategori'], 'journals' => ['table' => 'journals', 'label' => 'Jurnal'],
        'books' => ['table' => 'books', 'label' => 'Buku'], 'services' => ['table' => 'services', 'label' => 'Layanan'],
        'activities' => ['table' => 'activities', 'label' => 'Kegiatan'], 'research-fields' => ['table' => 'research_fields', 'label' => 'Bidang Penelitian'],
        'work-programs' => ['table' => 'work_programs', 'label' => 'Program Kerja'], 'team' => ['table' => 'team', 'label' => 'Tim'],
        'organization' => ['table' => 'organization_structure', 'label' => 'Struktur Organisasi'], 'gallery' => ['table' => 'gallery', 'label' => 'Galeri'],
        'gallery-images' => ['table' => 'gallery_images', 'label' => 'Media Galeri'], 'agenda' => ['table' => 'agenda', 'label' => 'Agenda'],
        'documents' => ['table' => 'documents', 'label' => 'Dokumen'], 'downloads' => ['table' => 'downloads', 'label' => 'Download'],
        'partners' => ['table' => 'partners', 'label' => 'Partner'], 'faq' => ['table' => 'faq', 'label' => 'FAQ'],
        'testimonials' => ['table' => 'testimonials', 'label' => 'Testimoni'], 'sliders' => ['table' => 'sliders', 'label' => 'Slider'],
        'menus' => ['table' => 'menus', 'label' => 'Menu'], 'social-media' => ['table' => 'social_media', 'label' => 'Media Sosial'],
        'users' => ['table' => 'users', 'label' => 'Pengguna'], 'comments' => ['table' => 'news_comments', 'label' => 'Komentar'],
    ],
    'public' => ['bidang-penelitian' => 'research-fields', 'program-kerja' => 'work-programs', 'layanan' => 'services', 'tim' => 'team',
        'struktur-organisasi' => 'organization', 'jurnal' => 'journals', 'buku' => 'books', 'berita' => 'news', 'agenda' => 'agenda',
        'galeri' => 'gallery', 'dokumen' => 'documents', 'download' => 'downloads', 'partner' => 'partners', 'faq' => 'faq'],
    'images' => ['featured_image', 'cover_image', 'image', 'photo', 'logo', 'avatar', 'image_path'],
    'files' => ['pdf_file', 'file_path'],
];
