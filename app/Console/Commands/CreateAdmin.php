<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'invoices:create-admin {username}';
    protected $description = 'Create the first invoice administrator';

    public function handle(): int
    {
        if (User::where('role', 'admin')->exists()) {
            $this->error('An administrator already exists. Manage users in the application.');
            return self::FAILURE;
        }

        $data = [
            'username' => $this->argument('username'),
            'password' => $this->secret('Choose a password (at least 12 characters)'),
        ];
        $validator = Validator::make($data, [
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|string|min:12|max:72',
        ]);
        if ($validator->fails()) {
            $this->error($validator->errors()->first());
            return self::FAILURE;
        }
        if ($data['password'] !== $this->secret('Confirm password')) {
            $this->error('Passwords differ.');
            return self::FAILURE;
        }
        User::create([
            'username' => $data['username'],
            'password' => Hash::make($data['password']),
            'role' => 'admin',
        ]);
        $this->info('Administrator created.');
        return self::SUCCESS;
    }
}
