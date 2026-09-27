<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Creates (or promotes) the back-office super-admin. Also installs the roles, so a fresh
 * production database only needs "migrate" then this command.
 */
#[Signature('app:create-super-admin {--name= : Full name} {--email= : Login e-mail}')]
#[Description('Create or promote the back-office super-admin account')]
class CreateSuperAdmin extends Command
{
    public function handle(): int
    {
        $this->callSilently('db:seed', ['--class' => RolesAndPermissionsSeeder::class, '--force' => true]);

        $email = $this->option('email') ?: text('E-mail de connexion', required: true);
        $user = User::where('email', $email)->first();

        if ($user === null) {
            $data = [
                'name' => $this->option('name') ?: text('Nom complet', required: true),
                'email' => $email,
                'password' => password('Mot de passe (12 caractères minimum)', required: true),
            ];

            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:12'],
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->all() as $error) {
                    $this->error($error);
                }

                return self::FAILURE;
            }

            $user = User::create($data);
        }

        $user->assignRole(Role::SuperAdmin->value);

        $this->info("{$user->email} est super-admin. À la première connexion, il devra configurer la double authentification.");

        return self::SUCCESS;
    }
}
