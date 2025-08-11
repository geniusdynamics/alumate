<?php

require_once '../../vendor/autoload.php';

// Bootstrap Laravel
$app = require_once '../../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "Checking existing users in the database:\n\n";

try {
    $users = User::all(['id', 'name', 'email']);

    if ($users->count() === 0) {
        echo "No users found in the database.\n";
    } else {
        echo 'Found '.$users->count()." users:\n";
        echo str_pad('ID', 5).str_pad('Name', 25).str_pad('Email', 35)."Roles\n";
        echo str_repeat('-', 75)."\n";

        foreach ($users as $user) {
            $roles = $user->getRoleNames()->implode(', ') ?: 'No roles';
            echo str_pad($user->id, 5).
                 str_pad($user->name, 25).
                 str_pad($user->email, 35).
                 $roles."\n";
        }
    }

    echo "\nTo create test users, run: php scripts/data/create_sample_data.php\n";

} catch (Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
}
