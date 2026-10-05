<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->guest(route('login'));
        }
        if ($user->status !== 'active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login');
        }
        abort_unless(in_array($user->role, ['super_admin', 'admin', 'editor']), 403);
        $module = $request->route('module');
        if ($module === 'users' || $request->is('admin/backup*')) {
            abort_unless($user->role === 'super_admin', 403);
        }
        if ($user->role === 'editor' && ($request->is('admin/settings*', 'admin/logs*', 'admin/messages*') || in_array($module, ['menus', 'social-media']))) {
            abort(403);
        }

        return $next($request);
    }
}
