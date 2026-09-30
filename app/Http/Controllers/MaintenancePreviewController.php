<?php

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Services\Storefront\Maintenance;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The maintenance page as visitors see it, whether the mode is on or not, for those who manage it.
 */
class MaintenancePreviewController extends Controller
{
    public function __invoke(Request $request, Maintenance $maintenance): View
    {
        abort_unless($request->user()->can(Permission::ManageSettings->value), 403);

        return view('maintenance', ['maintenance' => $maintenance]);
    }
}
