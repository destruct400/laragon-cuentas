<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthenticateJwt
{
    public function __construct(private JwtService $jwtService) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $token = $request->bearerToken();
            $payload = $token ? $this->jwtService->decode($token) : null;
            $user = $payload ? User::query()->find($payload['sub']) : null;
        } catch (Throwable) {
            $user = null;
        }

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
