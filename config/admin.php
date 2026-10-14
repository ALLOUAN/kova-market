<?php

/*
|--------------------------------------------------------------------------
| Back-office
|--------------------------------------------------------------------------
|
| The admin panel lives on a non-standard path set per environment, so it
| cannot be found by probing "/admin" (specification F-100).
|
*/

return [

    'path' => env('ADMIN_PATH', 'admin'),

    /*
    | Local machines only (APP_ENV=local): two-factor secret given to every account set up locally,
    | whose current code is displayed on the screens asking for it. Read from the local .env only, never
    | written here; without it, local accounts get random secrets like everywhere else.
    */
    'local_two_factor_secret' => env('ADMIN_LOCAL_TWO_FACTOR_SECRET'),

    /*
    | Password of the demo super-admin created by "db:seed" (never in production). Set in the local .env;
    | the repository is public, so a real password never goes here.
    */
    'demo_password' => env('ADMIN_DEMO_PASSWORD') ?: 'password',

];
