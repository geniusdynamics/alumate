<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Get dashboard data
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'Dashboard API endpoint'
            ]
        ]);
    }
}