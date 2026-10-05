<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DrcTest extends TestCase
{
    use RefreshDatabase;

    private function admin(string $role = 'super_admin'): User
    {
        return User::create(['username' => 'test-'.$role, 'email' => $role.'@example.org', 'full_name' => 'Test Admin', 'password' => 'StrongPassword123', 'role' => $role, 'status' => 'active']);
    }

    public function test_public_routes(): void
    {
        $this->seed();
        foreach (array_merge(['/', '/tentang-kami', '/visi-misi', '/sejarah', '/legalitas', '/profil', '/kontak', '/sitemap.xml'], array_map(fn ($p) => '/'.$p, array_keys(config('drc.public')))) as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_guest_and_editor_permissions(): void
    {
        $this->get('/admin/news')->assertRedirect('/admin/login');
        $this->actingAs($this->admin('editor'))->get('/admin/users')->assertForbidden();
        $this->get('/admin/settings')->assertForbidden();
        $this->post('/admin/backup/export')->assertForbidden();
        $this->get('/admin/news')->assertOk();
    }

    public function test_all_admin_module_lists_and_forms_render(): void
    {
        $this->seed();
        $this->actingAs($this->admin());
        foreach (config('drc.modules') as $module => $def) {
            $this->get('/admin/'.$module)->assertOk();
            $this->get('/admin/'.$module.'/create')->assertOk();
        }
        foreach (['dashboard', 'settings', 'messages', 'logs', 'backup', 'profile'] as $path) {
            $this->get('/admin/'.$path)->assertOk();
        }
    }

    public function test_login_logout_and_inactive_account(): void
    {
        $user = $this->admin();
        $this->post('/admin/login/process', ['username' => $user->email, 'password' => 'Wrong'])->assertSessionHasErrors('username');
        $this->post('/admin/login/process', ['username' => $user->username, 'password' => 'StrongPassword123'])->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
        $user->update(['status' => 'inactive']);
        $this->post('/admin/login/process', ['username' => $user->username, 'password' => 'StrongPassword123'])->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_news_crud_visibility_search_and_unique_slug(): void
    {
        $this->actingAs($this->admin());
        $data = ['title' => 'Penelitian Baru', 'slug' => 'penelitian-baru', 'content' => '<p>Konten</p><script>alert(1)</script>', 'status' => 'draft'];
        $this->post('/admin/news/store', $data)->assertRedirect('/admin/news');
        $item = DB::table('news')->first();
        $this->assertNotNull($item);
        $this->assertStringNotContainsString('<script', $item->content);
        $this->get('/berita/penelitian-baru')->assertNotFound();
        $this->post('/admin/news/store', $data)->assertSessionHasErrors('slug');
        $this->post('/admin/news/update/'.$item->id, $data + [])->assertRedirect('/admin/news');
        $data['status'] = 'published';
        $this->post('/admin/news/update/'.$item->id, $data)->assertRedirect('/admin/news');
        $this->get('/berita/penelitian-baru')->assertOk()->assertSee('Konten');
        $this->get('/berita?q=Penelitian')->assertSee('Penelitian Baru');
        $this->get('/admin/news/delete/'.$item->id)->assertStatus(405);
        $this->post('/admin/news/delete/'.$item->id)->assertRedirect('/admin/news');
        $this->get('/berita/penelitian-baru')->assertNotFound();
        $this->assertDatabaseHas('logs', ['action' => 'delete', 'module' => 'news']);
    }

    public function test_contact_captcha_and_message_read(): void
    {
        $payload = ['name' => 'Visitor', 'email' => 'visitor@example.org', 'subject' => 'Kolaborasi', 'message' => 'Halo'];
        $this->post('/kontak/kirim', $payload + ['captcha' => 0])->assertSessionHasErrors('captcha');
        $this->withSession(['captcha_answer' => 7])->post('/kontak/kirim', $payload + ['captcha' => 7])->assertRedirect('/kontak');
        $this->assertDatabaseHas('contact_messages', ['email' => 'visitor@example.org', 'is_read' => 0]);
        $id = DB::table('contact_messages')->value('id');
        $this->actingAs($this->admin())->get('/admin/messages/view/'.$id)->assertOk();
        $this->assertDatabaseHas('contact_messages', ['id' => $id, 'is_read' => 1]);
    }

    public function test_upload_download_and_reject_executable_file(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());
        $data = ['title' => 'Dokumen', 'slug' => 'dokumen-test', 'status' => 'active', 'file_path' => UploadedFile::fake()->create('laporan.pdf', 20, 'application/pdf')];
        $this->post('/admin/documents/store', $data)->assertRedirect('/admin/documents');
        $item = DB::table('documents')->first();
        Storage::disk('public')->assertExists($item->file_path);
        $this->get('/file/documents/'.$item->id)->assertOk();
        $this->assertDatabaseHas('documents', ['id' => $item->id, 'download_count' => 1]);
        $this->get('/media/'.$item->file_path)->assertNotFound();
        $this->post('/admin/sliders/store', ['title' => 'Bad', 'image' => UploadedFile::fake()->create('shell.php', 1, 'application/x-php'), 'status' => 'active'])->assertSessionHasErrors('image');
        $this->post('/admin/documents/update/'.$item->id, ['title' => 'Dokumen Baru', 'slug' => 'dokumen-test', 'status' => 'inactive'])->assertRedirect('/admin/documents');
        $this->get('/file/documents/'.$item->id)->assertNotFound();
    }

    public function test_large_document_upload_limit(): void
    {
        Storage::fake('public');
        $this->actingAs($this->admin());
        $data = ['title' => 'Dokumen Besar', 'slug' => 'dokumen-besar', 'status' => 'active'];
        $this->post('/admin/documents/store', $data + ['file_path' => UploadedFile::fake()->create('besar.pdf', 58 * 1024, 'application/pdf')])->assertRedirect('/admin/documents');
        $item = DB::table('documents')->first();
        Storage::disk('public')->assertExists($item->file_path);
        $this->post('/admin/documents/store', ['title' => 'Terlalu Besar', 'slug' => 'terlalu-besar', 'status' => 'active', 'file_path' => UploadedFile::fake()->create('besar.pdf', 65 * 1024, 'application/pdf')])->assertSessionHasErrors('file_path');
        $this->post('/admin/sliders/store', ['title' => 'Gambar Besar', 'status' => 'active', 'image' => UploadedFile::fake()->create('besar.png', 6 * 1024, 'image/png')])->assertSessionHasErrors('image');
        $this->assertDatabaseCount('documents', 1);
    }

    public function test_super_admin_cannot_disable_or_delete_self(): void
    {
        $user = $this->admin();
        $this->actingAs($user);
        $this->post('/admin/users/delete/'.$user->id)->assertStatus(422);
        $this->post('/admin/users/update/'.$user->id, ['role' => 'editor', 'status' => 'active'])->assertStatus(422);
        $this->get('/admin/not-a-module')->assertNotFound();
    }

    public function test_reset_password_uses_laravel_tokens(): void
    {
        $user = $this->admin();
        $token = Password::createToken($user);
        $this->post('/admin/reset-password/process', ['token' => $token, 'email' => $user->email, 'password' => 'NewPassword12345', 'password_confirmation' => 'NewPassword12345'])->assertRedirect('/admin/login');
        $this->assertTrue(Hash::check('NewPassword12345', $user->fresh()->password));
    }

    public function test_backup_contains_real_tables(): void
    {
        $this->seed();
        $this->actingAs($this->admin());
        $response = $this->post('/admin/backup/export')->assertOk()->assertDownload();
        $data = json_decode($response->streamedContent(), true);
        $this->assertSame('drc-laravel-v1', $data['format']);
        $this->assertCount(28, $data['tables']);
        $this->assertNotEmpty($data['tables']['settings']);
    }

    public function test_every_module_can_persist_edit_and_delete_data(): void
    {
        Storage::fake('public');
        $this->seed();
        $this->actingAs($this->admin());
        $created = [];
        foreach (config('drc.modules') as $module => $def) {
            $data = [];
            foreach (config("drc_schema.{$def['table']}.fields") as $name => $field) {
                if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at', 'created_by']) || ! $field['required'] || array_key_exists('default', $field)) {
                    continue;
                }
                if (isset(config("drc_schema.{$def['table']}.foreign")[$name])) {
                    $data[$name] = DB::table(config("drc_schema.{$def['table']}.foreign.$name.table"))->value('id');
                } elseif (in_array($name, config('drc.images'))) {
                    $data[$name] = UploadedFile::fake()->image('sample.png');
                } elseif (in_array($name, config('drc.files'))) {
                    $data[$name] = UploadedFile::fake()->create('sample.pdf', 10, 'application/pdf');
                } elseif ($name === 'email') {
                    $data[$name] = 'module-test@example.org';
                } elseif ($name === 'password') {
                    $data[$name] = 'NewStrongPassword123';
                } elseif (isset($field['options'])) {
                    $data[$name] = $field['options'][0];
                } elseif ($field['type'] === 'DATE') {
                    $data[$name] = '2026-10-05';
                } elseif ($name === 'url') {
                    $data[$name] = '/kontak';
                } else {
                    $data[$name] = 'test-'.$module;
                }
            }
            $this->post('/admin/'.$module.'/store', $data)->assertRedirect('/admin/'.$module);
            $id = DB::table($def['table'])->max('id');
            $created[$module] = ['id' => $id, 'table' => $def['table']];
            $this->get('/admin/'.$module.'/edit/'.$id)->assertOk();
            $this->post('/admin/'.$module.'/update/'.$id, $data)->assertRedirect('/admin/'.$module);
        }
        foreach (array_reverse($created, true) as $module => $record) {
            $id = $record['id'];
            $def = ['table' => $record['table']];
            $this->post('/admin/'.$module.'/delete/'.$id)->assertRedirect('/admin/'.$module);
            if (isset(config("drc_schema.{$def['table']}.fields")['deleted_at'])) {
                $this->assertNotNull(DB::table($def['table'])->where('id',$id)->value('deleted_at'));
            } else {
                $this->assertDatabaseMissing($def['table'],['id' => $id]);
            }
        }
    }
}
