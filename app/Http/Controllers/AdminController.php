<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Support\Audit;
use App\Support\Html;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
    private function definition(string $module): array
    {
        $def = config("drc.modules.$module");
        abort_unless($def, 404);

        return $def;
    }

    private function query(string $table)
    {
        $q = Content::forTable($table)->newQuery();
        if (isset(config("drc_schema.$table.fields")['deleted_at'])) {
            $q->whereNull('deleted_at');
        }

return $q;
    }

    private function fields(string $table): array
    {
        return array_diff_key(config("drc_schema.$table.fields"), array_flip(['id', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'remember_token', 'reset_token', 'reset_expires', 'login_attempts', 'locked_until', 'last_login', 'views', 'download_count', 'file_size', 'file_type']));
    }

    public function dashboard()
    {
        $counts = [];
        foreach (config('drc.modules') as $key => $def) {
            $counts[$key] = $this->query($def['table'])->count();
        }

        return view('admin.dashboard', ['title' => 'Dashboard', 'counts' => $counts, 'unread' => DB::table('contact_messages')->where('is_read', 0)->count(), 'today' => DB::table('visitor_statistics')->where('visit_date', today()->toDateString())->count(), 'visitors' => DB::table('visitor_statistics')->count()]);
    }

    public function index(Request $request, string $module)
    {
        $def = $this->definition($module);
        $q = $this->query($def['table']);
        $fields = $this->fields($def['table']);
        $request->validate(['q' => 'nullable|string|max:200']);
        if ($request->filled('q')) {
            $q->where(function ($q) use ($request, $fields) {
                foreach (array_intersect(['title', 'name', 'question', 'username', 'platform'], array_keys($fields)) as $column) {
                    $q->orWhere($column, 'like', '%'.$request->query('q').'%');
                }
            });
        }

        return view('admin.index', ['title' => $def['label'], 'module' => $module, 'items' => $q->latest('id')->paginate(15)->withQueryString()]);
    }

    public function form(string $module, ?int $id = null)
    {
        $def = $this->definition($module);
        $item = $id ? $this->query($def['table'])->findOrFail($id) : Content::forTable($def['table']);
        $choices = [];
        foreach (config("drc_schema.{$def['table']}.foreign") as $name => $foreign) {
            if ($name !== 'created_by') {
                $choices[$name] = $this->query($foreign['table'])->when($foreign['table'] === $def['table'] && $id, fn ($q) => $q->where('id', '!=', $id))->get();
            }
        }

        return view('admin.form', ['title' => ($id ? 'Edit ' : 'Tambah ').$def['label'], 'module' => $module, 'item' => $item, 'fields' => $this->fields($def['table']), 'choices' => $choices]);
    }

    public function save(Request $request, string $module, ?int $id = null)
    {
        $def = $this->definition($module);
        $table = $def['table'];
        $fields = $this->fields($table);
        $item = $id ? $this->query($table)->findOrFail($id) : Content::forTable($table);
        if ($table === 'users' && $id === $request->user()->id) {
            abort_unless($request->input('role') === 'super_admin' && $request->input('status') === 'active', 422, 'Anda tidak dapat menonaktifkan atau menurunkan peran akun sendiri.');
        }
        if (isset($fields['slug']) && ! $request->filled('slug')) {
            $request->merge(['slug' => Str::slug($request->input('title', $request->input('name', '')))]);
        }
        $rules = [];
        foreach ($fields as $name => $field) {
            $isFile = in_array($name, array_merge(config('drc.images'), config('drc.files')));
            $required = $field['required'] && ! array_key_exists('default', $field) && ! ($isFile && $id && $item->$name) && ! ($name === 'password' && $id);
            $rule = [$required ? 'required' : 'nullable'];
            if ($isFile) {
                $rule[] = 'file';
                $rule[] = 'max:5120';
                $rule[] = in_array($name, config('drc.images')) ? 'mimes:jpg,jpeg,png,gif,webp' : 'mimes:pdf,doc,docx';
            } elseif ($name === 'password') {
                $rule[] = Password::min(12)->letters()->numbers();
            } elseif (isset($field['options'])) {
                $rule[] = Rule::in($field['options']);
            } elseif (in_array($field['type'], ['INT', 'TINYINT', 'YEAR'])) {
                $rule[] = 'integer';
                $rule[] = 'min:0';
                if ($name === 'rating') {
                    $rule[] = 'max:5';
                }
            } elseif ($field['type'] === 'DATE' || $field['type'] === 'DATETIME') {
                $rule[] = 'date';
            } elseif ($field['type'] === 'TIME') {
                $rule[] = 'date_format:H:i';
            } else {
                $rule[] = 'string';
                $rule[] = 'max:'.($field['length'] ?? 100000);
            }
            if ($name === 'email') {
                $rule[] = 'email';
            }
            if ($name === 'slug') {
                $rule[] = 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/';
            }
            if ($field['unique']) {
                $rule[] = Rule::unique($table, $name)->ignore($id);
            }
            if (isset(config("drc_schema.$table.foreign")[$name])) {
                $foreign = config("drc_schema.$table.foreign.$name.table");
                $exists = Rule::exists($foreign, 'id');
                if (isset(config("drc_schema.$foreign.fields")['deleted_at'])) {
                    $exists->whereNull('deleted_at');
                }
                $rule[] = $exists;
                if ($foreign === $table && $id) {
                    $rule[] = Rule::notIn([$id]);
                }
            }
            if (in_array($name, ['url', 'website_url', 'registration_url', 'button_url', 'facebook', 'twitter', 'instagram', 'linkedin', 'youtube_url'])) {
                $rule[] = function ($attr, $value, $fail) {
                    if (! Html::safeUrl($value)) {
                        $fail('URL harus berupa http(s) atau path lokal.');
                    }
                };
            }
            $rules[$name] = $rule;
        }
        $data = $request->validate($rules);
        foreach ($data as $name => $value) {
            if (in_array($name, array_merge(config('drc.images'), config('drc.files')))) {
                if (! $value instanceof UploadedFile) {
                    unset($data[$name]);

                    continue;
                }
                $data[$name] = $value->store('media', 'public');
                if (isset(config("drc_schema.$table.fields")['file_size'])) {
                    $data['file_size'] = $value->getSize();
                }
                if (isset(config("drc_schema.$table.fields")['file_type'])) {
                    $data['file_type'] = $value->extension();
                }
            }
            if ($name === 'password') {
                if (! $value) {
                    unset($data[$name]);
                } else {
                    $data[$name] = Hash::make($value);
                }
            }
            if (in_array($name, ['content', 'description', 'biography', 'synopsis', 'abstract', 'answer', 'testimonial'])) {
                $data[$name] = Html::clean($value);
            }
        }
        if (isset(config("drc_schema.$table.fields")['created_by']) && ! $id) {
            $data['created_by'] = $request->user()->id;
        }
        DB::transaction(function () use ($item, $data, $module, $id) {
            $item->fill($data)->save();
            Audit::record($id ? 'update' : 'create', $module, $item->id);
        });

        return redirect("/admin/$module")->with('success', 'Data berhasil disimpan.');
    }

    public function delete(Request $request, string $module, int $id)
    {
        $def = $this->definition($module);
        $item = $this->query($def['table'])->findOrFail($id);
        if ($module === 'users' && $id === $request->user()->id) {
            abort(422, 'Anda tidak dapat menghapus akun sendiri.');
        }
        DB::transaction(function () use ($def, $item, $module, $id) {
            if (isset(config("drc_schema.{$def['table']}.fields")['deleted_at'])) {
                $item->deleted_at = now();
                $item->save();
            } else {
                $item->delete();
            } Audit::record('delete', $module, $id);
        });

        return redirect("/admin/$module")->with('success', 'Data berhasil dihapus.');
    }

    public function settings()
    {
        return view('admin.settings', ['title' => 'Pengaturan Website', 'settings' => DB::table('settings')->orderBy('setting_group')->get()]);
    }

    public function saveSettings(Request $request)
    {
        $allowed = DB::table('settings')->pluck('setting_key')->all();
        $rules = [];
        foreach ($allowed as $key) {
            $rules[$key] = 'nullable|string|max:10000';
        } $data = $request->validate($rules);
        DB::transaction(function () use ($data) {
            foreach ($data as $key => $value) {
                DB::table('settings')->where('setting_key', $key)->update(['setting_value' => $value, 'updated_at' => now()]);
            } Audit::record('update', 'settings');
        });

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }

    public function messages()
    {
        return view('admin.messages', ['title' => 'Pesan Masuk', 'items' => DB::table('contact_messages')->latest('id')->paginate(20)]);
    }

    public function message(int $id)
    {
        $item = DB::table('contact_messages')->find($id);
        abort_unless($item, 404);
        DB::table('contact_messages')->where('id', $id)->update(['is_read' => 1]);

        return view('admin.message', ['title' => $item->subject, 'item' => $item]);
    }

    public function deleteMessage(int $id)
    {
        abort_unless(DB::table('contact_messages')->where('id', $id)->delete(), 404);
        Audit::record('delete', 'messages', $id);

        return redirect('/admin/messages')->with('success', 'Pesan dihapus.');
    }

    public function logs()
    {
        return view('admin.logs', ['title' => 'Log Aktivitas', 'items' => DB::table('logs')->leftJoin('users', 'users.id', '=', 'logs.user_id')->select('logs.*', 'users.full_name')->latest('logs.id')->paginate(50)]);
    }

    public function backup()
    {
        return view('admin.backup', ['title' => 'Backup Database']);
    }

    public function export()
    {
        Audit::record('export', 'backup');

        return response()->streamDownload(function () {
            $tables = [];
            DB::transaction(function () use (&$tables) {
                foreach (array_keys(config('drc_schema')) as $table) {
                    $tables[$table] = DB::table($table)->get()->all();
                }
            });
            echo json_encode(['format' => 'drc-laravel-v1', 'exported_at' => now()->toIso8601String(), 'tables' => $tables],JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }, 'drc-backup-'.now()->format('Y-m-d-His').'.json', ['Content-Type' => 'application/json', 'Cache-Control' => 'no-store']);
    }
}
