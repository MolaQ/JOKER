<?php

/*
 * Konta zakładane przez "php artisan db:seed --class=StartSeeder". Hasła tylko w .env,
 * nigdy w kodzie ani w repozytorium. Puste hasło = seeder wylosuje je i wypisze w konsoli.
 * Po pierwszym logowaniu zmień hasło w "Mój profil".
 */
return [
    'admin' => [
        'name' => env('SEED_ADMIN_NAME', 'Marcin Molak'),
        'email' => env('SEED_ADMIN_EMAIL'),
        'password' => env('SEED_ADMIN_PASSWORD'),
    ],
    'boss' => [
        'name' => env('SEED_SZEF_NAME', 'Jacek Wysocki'),
        'email' => env('SEED_SZEF_EMAIL'),
        'password' => env('SEED_SZEF_PASSWORD'),
    ],
    'player' => [
        'name' => env('SEED_PLAYER_NAME', 'Marcel Molak'),
        'email' => env('SEED_PLAYER_EMAIL'),
        'password' => env('SEED_PLAYER_PASSWORD'),
    ],
];
