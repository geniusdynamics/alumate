<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomepageNavigationItem;
use Illuminate\Http\JsonResponse;

class HomepageNavigationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $navigationItems = HomepageNavigationItem::whereNull('parent_id')
            ->with('children')
            ->orderBy('order')
            ->get();

        return response()->json($navigationItems);
    }
}
