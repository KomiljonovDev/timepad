<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Services\Transaction\UserWorkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserWorkController extends Controller
{
    public function __construct(protected UserWorkService $service) {}

    public function index(Request $request): JsonResponse {
        $perPage = $request->get('per_page', 10);
        $data = $this->service->getUserWorkSummary($perPage);
        return response()->json($data);
    }

    public function actuallyWork(Request $request): JsonResponse {
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $data = $this->service->getUserWorkMatrix($start_date,$end_date);
        return response()->json($data);
    }
}
