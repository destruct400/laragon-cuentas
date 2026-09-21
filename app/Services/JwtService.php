<?php

namespace App\Services;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class JwtService
{
    /**
     * @return array{access_token: string, refresh_token: string, token_type: string, expires_in: int}
     */
    public function issueTokenPair(User $user): array
    {
        $now = Carbon::now();
        $accessToken = $this->encode([
            'iss' => config('app.url'),
            'sub' => $user->getKey(),
            'iat' => $now->timestamp,
            'exp' => $now->copy()->addSeconds($this->accessTtl())->timestamp,
            'jti' => (string) Str::uuid(),
        ]);
        $refreshToken = Str::random(96);

        RefreshToken::query()->create([
            'user_id' => $user->getKey(),
            'token_hash' => hash('sha256', $refreshToken),
            'expires_at' => $now->copy()->addSeconds($this->refreshTtl()),
        ]);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->accessTtl(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function decode(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid JWT.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $parts;
        $header = $this->decodePart($encodedHeader);
        $payload = $this->decodePart($encodedPayload);

        if (($header['alg'] ?? null) !== config('jwt.algorithm')) {
            throw new RuntimeException('Invalid JWT algorithm.');
        }

        $expectedSignature = $this->sign($encodedHeader.'.'.$encodedPayload);

        if (! hash_equals($expectedSignature, $encodedSignature)) {
            throw new RuntimeException('Invalid JWT signature.');
        }

        if (! isset($payload['sub'], $payload['exp']) || ! is_numeric($payload['sub']) || ! is_numeric($payload['exp'])) {
            throw new RuntimeException('Invalid JWT claims.');
        }

        if ((int) $payload['exp'] <= now()->timestamp) {
            throw new RuntimeException('Expired JWT.');
        }

        return $payload;
    }

    public function refresh(string $plainToken): ?array
    {
        return DB::transaction(function () use ($plainToken): ?array {
            $refreshToken = RefreshToken::query()
                ->where('token_hash', hash('sha256', $plainToken))
                ->whereNull('revoked_at')
                ->lockForUpdate()
                ->first();

            if (! $refreshToken || $refreshToken->isExpired()) {
                return null;
            }

            RefreshToken::query()
                ->where('token_hash', hash('sha256', $plainToken))
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            return $this->issueTokenPair($refreshToken->user);
        });
    }

    public function revokeAll(User $user): void
    {
        RefreshToken::query()
            ->where('user_id', $user->getKey())
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    private function encode(array $payload): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => config('jwt.algorithm'), 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $body = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $data = $header.'.'.$body;

        return $data.'.'.$this->sign($data);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePart(string $part): array
    {
        $decoded = json_decode($this->base64UrlDecode($part), true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Invalid JWT encoding.');
        }

        return $decoded;
    }

    private function sign(string $data): string
    {
        $secret = config('jwt.secret');

        if (! is_string($secret) || $secret === '') {
            throw new RuntimeException('JWT secret is not configured.');
        }

        return $this->base64UrlEncode(hash_hmac('sha256', $data, $secret, true));
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        return base64_decode(strtr($value, '-_', '+/').str_repeat('=', (4 - strlen($value) % 4) % 4), true) ?: '';
    }

    private function accessTtl(): int
    {
        return max(1, (int) config('jwt.ttl'));
    }

    private function refreshTtl(): int
    {
        return max(1, (int) config('jwt.refresh_ttl'));
    }
}
