<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateEditor extends Command
{
    protected $signature = 'editorial:create-admin {email} {--name=Editor}';

    protected $description = 'Create a Pisica Ta administrator with an interactive password prompt';

    public function handle(): int
    {
        $email = mb_strtolower(trim($this->argument('email')));
        $password = $this->secret('Parolă');
        if (! is_string($password) || $password !== $this->secret('Confirmă parola')) {
            $this->error('Parolele trebuie să coincidă.');

            return self::FAILURE;
        }

        $data = Validator::make([
            'name' => $this->option('name'),
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:254', 'unique:users'],
            'password' => ['required', 'string', 'max:72', Password::min(12)],
        ])->validate();

        User::create($data + ['is_admin' => true]);
        $this->info('Administrator creat.');

        return self::SUCCESS;
    }
}
