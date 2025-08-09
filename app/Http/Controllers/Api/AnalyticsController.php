<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    /**
     * Get analytics data
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'Analytics API endpoint'
            ]
        ]);
    }
}