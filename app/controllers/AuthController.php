<?php
require __DIR__ . '/../../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController {
    private static $secretKey = 'Bifidus3012';

    public static function generarToken($userId) {
        $payload = [
            'iat' => time(),
            'exp' => time() + 3600,
            'sub' => $userId
        ];
        return JWT::encode($payload, self::$secretKey, 'HS256');
    }

    public static function validarToken($token) {
        try {
            $decoded = JWT::decode($token, new Key(self::$secretKey, 'HS256'));
            return $decoded->sub;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>