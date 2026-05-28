<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $permissions = $user->permissions ?? $user->privilege ?? [];

        $modules = DB::table('system_menu')
            ->where('level', 1)
            ->where('status', 1)
            ->orderBy('sort')
            ->get();

        $rows = [];
        foreach ($modules as $module) {
            $children = DB::table('system_menu')
                ->where('pid', $module->id)
                ->where('status', 1)
                ->orderBy('sort')
                ->get()
                ->filter(function ($item) use ($permissions) {
                    if (empty($item->path)) {
                        return true;
                    }
                    return in_array($item->path, $permissions, true);
                })
                ->values();

            $rows[] = [
                'id' => $module->id,
                'module' => $module->module,
                'menu' => $children,
            ];
        }

        return ResponseHelper::success($rows, 'Success');
    }
}
