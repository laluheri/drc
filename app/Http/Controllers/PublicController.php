<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PublicController extends Controller
{
    private function settings(): array
    {
        return DB::table('settings')->pluck('setting_value', 'setting_key')->all();
    }

    public function home(Request $request)
    {
        DB::table('visitor_statistics')->insert(['visit_date' => today()->toDateString(), 'page_url' => '/', 'ip_address' => $request->ip(), 'user_agent' => mb_substr($request->userAgent() ?? '', 0, 255), 'referrer' => mb_substr($request->headers->get('referer', ''), 0, 255), 'created_at' => now()]);
        $settings = $this->settings();

        return view('public.home', [
            'title' => $settings['meta_title'] ?? 'Daygun Research Center',
            'settings' => $settings,
            'workPrograms' => Content::visible('work_programs')->orderBy('sort_order')->get()->toArray(),
        ]);
    }

    public function page(string $slug)
    {
        if ($slug === 'profil') {
            return view('public.profile', ['title' => 'Profil Lembaga', 'settings' => $this->settings()]);
        }
        $item = Content::visible('pages')->where('slug', $slug)->firstOrFail();

        return view('public.detail', ['title' => $item->meta_title ?: $item->title, 'settings' => $this->settings(), 'item' => $item, 'table' => 'pages', 'path' => $slug, 'images' => collect(), 'related' => collect()]);
    }

    public function listing(Request $request)
    {
        $path = $request->route()->defaults['section'];
        $module = config("drc.public.$path");
        $def = config("drc.modules.$module");
        $fields = config("drc_schema.{$def['table']}.fields");
        $query = Content::visible($def['table']);
        $request->validate(['q' => 'nullable|string|max:200', 'category' => 'nullable|integer']);
        if ($request->filled('q')) {
            $columns = array_intersect(['title', 'name', 'question', 'description', 'content', 'abstract', 'authors'], $keys = array_keys($fields));
            $query->where(function ($q) use ($columns, $request) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', '%'.$request->query('q').'%');
                }
            });
        }
        if ($request->filled('category') && isset($fields['category_id'])) {
            $query->where('category_id', $request->integer('category'));
        }
        if (isset($fields['sort_order'])) {
            $query->orderBy('sort_order');
        } elseif (isset($fields['event_date'])) {
            $query->orderBy('event_date', 'desc');
        } else {
            $query->latest('id');
        }

        return view('public.list', ['title' => $def['label'], 'settings' => $this->settings(), 'path' => $path, 'table' => $def['table'], 'items' => $query->paginate(9)->withQueryString(), 'categories' => $def['table'] === 'news' ? Content::visible('categories')->get() : collect()]);
    }

    public function detail(Request $request, string $slug)
    {
        $path = $request->route()->defaults['section'];
        $table = config('drc.modules.'.config("drc.public.$path").'.table');
        $item = Content::visible($table)->where('slug', $slug)->firstOrFail();
        if ($table === 'news') {
            $item->increment('views');
        }
        $images = $table === 'gallery' ? Content::forTable('gallery_images')->where('gallery_id', $item->id)->orderBy('sort_order')->get() : collect();
        $related = $table === 'news' && $item->category_id ? Content::visible('news')->where('category_id', $item->category_id)->where('id', '!=', $item->id)->limit(3)->get() : collect();

        return view('public.detail', ['title' => $item->meta_title ?: $item->label(), 'settings' => $this->settings(), 'path' => $path, 'table' => $table, 'item' => $item, 'images' => $images, 'related' => $related]);
    }

    public function contact(Request $request)
    {
        $a = random_int(1, 10);
        $b = random_int(1, 10);
        $request->session()->put('captcha_answer', $a + $b);

        return view('public.contact', ['title' => 'Kontak', 'settings' => $this->settings(), 'captcha' => "$a + $b"]);
    }

    public function send(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:100', 'email' => 'required|email|max:100', 'phone' => 'nullable|string|max:20', 'subject' => 'required|string|max:200', 'message' => 'required|string|max:10000', 'captcha' => 'required|integer']);
        $answer = $request->session()->get('captcha_answer');
        if ($answer === null || (int) $data['captcha'] !== $answer) {
            throw ValidationException::withMessages(['captcha' => 'Jawaban captcha salah atau sudah kedaluwarsa.']);
        }
        $request->session()->forget('captcha_answer');
        unset($data['captcha']);
        DB::table('contact_messages')->insert($data + ['ip_address' => $request->ip(), 'created_at' => now()]);

        return redirect('/kontak')->with('success', 'Pesan Anda berhasil dikirim. Terima kasih!');
    }

    public function download(string $module, int $id)
    {
        abort_unless(in_array($module, ['journals', 'books', 'documents', 'downloads']), 404);
        $item = Content::visible($module)->findOrFail($id);
        $path = Media::path($item->pdf_file ?: $item->file_path);
        $item->increment('download_count');

        return Storage::disk('public')->download($path);
    }

    public function media(string $path)
    {
        $path = Media::path($path);
        $disk = Storage::disk('public');
        abort_unless(in_array($disk->mimeType($path), ['image/jpeg', 'image/png', 'image/gif', 'image/webp']), 404);

        return response()->file($disk->path($path), ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'public, max-age=86400']);
    }

    public function sitemap()
    {
        $urls = array_merge(['', 'tentang-kami', 'visi-misi', 'sejarah', 'legalitas', 'profil', 'kontak'], array_keys(config('drc.public')));
        foreach (['berita' => 'news', 'jurnal' => 'journals', 'buku' => 'books', 'galeri' => 'gallery'] as $path => $table) {
            foreach (Content::visible($table)->pluck('slug') as $slug) {
                $urls[] = "$path/$slug";
            }
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $path) {
            $xml .= '<url><loc>'.htmlspecialchars(url($path),ENT_XML1 | ENT_QUOTES,'UTF-8').'</loc></url>';
        }

        return response($xml.'</urlset>',200,['Content-Type' => 'application/xml']);
    }
}
