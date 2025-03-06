<?php
namespace App\Controllers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController {
    private $twig;
    private $pdo;
    private $secretKey;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->secretKey = $_ENV['JWT_SECRET'] ?? 'tu_clave_secreta';
        $loader = new \Twig\Loader\FilesystemLoader('../app/views');
        $this->twig = new \Twig\Environment($loader, [
            'cache' => false,
            'debug' => true
        ]);
    }

    public function generateToken($username) {
        $payload = [
            'username' => $username,
            'iat' => time(),
            'exp' => time() + (7 * 24 * 60 * 60) // Token válido por 1 semana
        ];

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    public function loginPage() {
        return $this->twig->render('auth/login.twig', [
            'error' => null
        ]);
    }

    public function login() {
        try {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            // Por ahora, usaremos credenciales hardcodeadas
            // En producción, esto debería validar contra la base de datos
            if ($username === 'admin' && $password === 'admin123') {
                $token = $this->generateToken($username);
                
                // Guardar token en cookie
                setcookie('jwt', $token, [
                    'expires' => time() + (7 * 24 * 60 * 60), // 1 semana
                    'path' => '/',
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]);

                header('Location: /admin');
                exit;
            }

            return $this->twig->render('auth/login.twig', [
                'error' => 'Credenciales inválidas'
            ]);
        } catch (\Exception $e) {
            error_log("Error en login: " . $e->getMessage());
            return $this->twig->render('auth/login.twig', [
                'error' => 'Error al iniciar sesión'
            ]);
        }
    }

    public function logout() {
        setcookie('jwt', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'httponly' => true
        ]);
        header('Location: /login');
        exit;
    }

    public function validarToken($token) {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            return true;
        } catch (\Exception $e) {
            error_log("Error validando token: " . $e->getMessage());
            return false;
        }
    }
}
?>