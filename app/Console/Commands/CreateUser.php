<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

final class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create
        {email : The email address of the user}
        {name : The name of the user}
        {password : The password for the user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var string $email */
        $email = $this->argument('email');
        /** @var string $name */
        $name = $this->argument('name');
        /** @var string $password */
        $password = $this->argument('password');

        if (User::where('email', $email)->exists()) {
            $this->error("User with email '{$email}' already exists.");

            return Command::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $this->info("User created: {$user->email} (ID: {$user->id})");

        return Command::SUCCESS;
    }
}
