<?php

namespace Fatemeh\TaskManagerApi\Services;

use Fatemeh\TaskManagerApi\Exceptions\UnauthorizedException;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\SignatureInvalidException;
use UnexpectedValueException;

class JwtService
{
    private const int ACCESS_TOKEN_TTL = 900;
    private const int REFRESH_TOKEN_TTL = 2592000;
    private const string ALG = 'HS256';

    public function __construct(private string $accessSecret, private string $refreshSecret) {}

    public function generateAccessToken(int $userId): string
    {
        return $this->generateToken($userId, $this->accessSecret, time() + self::ACCESS_TOKEN_TTL);
    }

    public function generateRefreshToken(int $userId): string
    {
        return $this->generateToken($userId, $this->refreshSecret, time() + self::REFRESH_TOKEN_TTL);
    }

    private function generateToken(int $userId, string $key, int $exp): string
    {
        $payload = [
            'sub' => $userId,
            'iat' => time(),
            'exp' => $exp
        ];

        return JWT::encode($payload, $key, self::ALG);
    }

    public function validateAccessToken(string $token): array
    {
        return $this->validateToken($token, $this->accessSecret);
    }

    public function validateRefreshToken(string $token): array
    {
        return $this->validateToken($token, $this->refreshSecret);
    }

    private function validateToken(string $token, string $secretKey) : array {
        try {
            $decodedToken = JWT::decode($token, new Key($secretKey, self::ALG));
            return (array) $decodedToken;
        } catch (ExpiredException | SignatureInvalidException | UnexpectedValueException $e) {
            throw new UnauthorizedException();
        }
    }
}
