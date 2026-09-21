<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController
{
    public function __construct(private JwtService $jwtService) {}

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Las credenciales no son válidas.'], 401);
        }

        return response()->json($this->jwtService->issueTokenPair($user));
    }

    public function refresh(Request $request): JsonResponse
    {
        $refreshToken = $request->validate([
            'refresh_token' => ['required', 'string'],
        ])['refresh_token'];
        $tokens = $this->jwtService->refresh($refreshToken);

        return $tokens
            ? response()->json($tokens)
            : response()->json(['message' => 'El refresh token no es válido.'], 401);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->jwtService->revokeAll($request->user());

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }
}
