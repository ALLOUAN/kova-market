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
    | whose current code is displayed on the screens asking for it. Never used in other environments.
    */
    'local_two_factor_secret' => env('ADMIN_LOCAL_TWO_FACTOR_SECRET', 'JBSWY3DPEHPK3PXP'),

];
