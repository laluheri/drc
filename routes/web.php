<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicController::class, 'home'])->name('home');
foreach (['tentang-kami', 'visi-misi', 'sejarah', 'legalitas', 'profil'] as $slug) {
    Route::get($slug, [PublicController::class, 'page'])->defaults('slug', $slug);
}
foreach (config('drc.public') as $path => $module) {
    Route::get($path, [PublicController::class, 'listing'])->defaults('section', $path);
    if (in_array($path, ['berita', 'jurnal', 'buku', 'galeri'])) {
        Route::get($path.'/{slug}', [PublicController::class, 'detail'])->defaults('section', $path);
    }
}
Route::get('kontak', [PublicController::class, 'contact']);
Route::post('kontak/kirim', [PublicController::class, 'send'])->middleware('throttle:5,1');
Route::get('sitemap.xml', [PublicController::class, 'sitemap']);
Route::get('file/{module}/{id}', [PublicController::class, 'download'])->whereNumber('id')->name('download');
Route::get('media/{path}', [PublicController::class, 'media'])->where('path', '.*')->name('media');

Route::prefix('admin')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [AuthController::class, 'loginForm'])->name('login');
        Route::post('login/process', [AuthController::class, 'login'])->middleware('throttle:30,1');
        Route::get('forgot-password', [AuthController::class, 'forgotForm']);
        Route::post('forgot-password/process', [AuthController::class, 'forgot'])->middleware('throttle:5,1');
        Route::get('reset-password/{token}', [AuthController::class, 'resetForm'])->name('password.reset');
        Route::post('reset-password/process', [AuthController::class, 'reset'])->middleware('throttle:10,1');
    });
    Route::middleware('admin')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard']);
        Route::get('dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('profile', [AuthController::class, 'profile']);
        Route::post('profile/update', [AuthController::class, 'updateProfile']);
        Route::get('settings', [AdminController::class, 'settings']);
        Route::post('settings/save', [AdminController::class, 'saveSettings']);
        Route::get('messages', [AdminController::class, 'messages']);
        Route::get('messages/view/{id}', [AdminController::class, 'message'])->whereNumber('id');
        Route::post('messages/delete/{id}', [AdminController::class, 'deleteMessage'])->whereNumber('id');
        Route::get('logs', [AdminController::class, 'logs']);
        Route::get('backup', [AdminController::class, 'backup']);
        Route::post('backup/export', [AdminController::class, 'export']);
        $modules = implode('|', array_map(fn ($module) => preg_quote($module, '/'), array_keys(config('drc.modules'))));
        Route::get('{module}', [AdminController::class, 'index'])->where('module', $modules);
        Route::get('{module}/create', [AdminController::class, 'form'])->where('module', $modules);
        Route::post('{module}/store', [AdminController::class, 'save'])->where('module', $modules);
        Route::get('{module}/edit/{id}', [AdminController::class, 'form'])->where('module', $modules)->whereNumber('id');
        Route::post('{module}/update/{id}', [AdminController::class, 'save'])->where('module', $modules)->whereNumber('id');
        Route::post('{module}/delete/{id}',[AdminController::class, 'delete'])->where('module',$modules)->whereNumber('id');
    });
});
