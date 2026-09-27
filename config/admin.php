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

];
