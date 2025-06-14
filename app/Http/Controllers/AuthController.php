<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function login(Request $request): JsonResponse {
        $validated = $request->validate([
            'id'=>'required|integer',
            'username'=>'required|string',
        ]);
        $user = User::query()->where('id', $validated['id'])->where('username', $validated['username'])->first();
        if (!$user) {
            return response()->json(['error' => 'id or user name incorrect'], 401);
        }
        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json(['access_token' => $token, 'token_type' => 'Bearer'], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function logout(Request $request) {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out'], 200);
    }
}
