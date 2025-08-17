<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RealTimeController extends Controller
{
    /**
     * Get real-time updates
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'Real-time API endpoint',
            ],
        ]);
    }
}
