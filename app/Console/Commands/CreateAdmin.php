<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'drc:admin {username=superadmin} {--email=admin@daygunresearch.org} {--name=Super Administrator} {--write-credentials : Save a generated password to .local/ACCESS.md}';

    protected $description = 'Create a super administrator with a generated or interactively supplied password';

    public function handle(): int
    {
        $username = $this->argument('username');
        if (User::withTrashed()->where('username', $username)->orWhere('email', $this->option('email'))->exists()) {
            $this->error('Username or email already exists.');

            return self::FAILURE;
        }
        $password = $this->option('write-credentials') ? Str::password(24) : $this->secret('Password (minimum 12 characters, letters and numbers)');
        $data = ['username' => $username, 'email' => $this->option('email'), 'full_name' => $this->option('name'), 'password' => $password];
        $validation = Validator::make($data, ['username' => 'required|string|max:50', 'email' => 'required|email|max:100', 'full_name' => 'required|string|max:100', 'password' => ['required', Password::min(12)->letters()->numbers()]]);
        if ($validation->fails()) {
            $this->error($validation->errors()->first());

            return self::FAILURE;
        }
        User::create($data + ['role' => 'super_admin', 'status' => 'active']);
        if ($this->option('write-credentials')) {
            if (! is_dir(base_path('.local'))) {
                mkdir(base_path('.local'), 0700, true);
            }
            file_put_contents(base_path('.local/ACCESS.md'), "# Akses lokal DRC\n\nURL: http://127.0.0.1:8000/admin/login\n\nUsername: $username\n\nEmail: {$data['email']}\n\nKata sandi: `$password`\n\nUbah kata sandi di menu Profil setelah login. File ini hanya untuk akses lokal dan tidak disertakan dalam Git.\n");
            $this->info('Administrator created. Credentials saved in .local/ACCESS.md.');
        } else {
            $this->info('Administrator created.');
        }

        return self::SUCCESS;
    }
}
