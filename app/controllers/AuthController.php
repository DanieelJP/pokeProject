<?php
namespace App\Controllers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController {
    private $twig;
    private $pdo;
    private $secretKey;
    private $cookieLifetime = 2592000; // 30 días en segundos

    public function __construct($twig = null) {
        global $pdo;
        $this->pdo = $pdo;
        $this->secretKey = $_ENV['JWT_SECRET'] ?? 'tu_clave_secreta';
        
        if ($twig === null) {
            $loader = new \Twig\Loader\FilesystemLoader(dirname(__DIR__) . '/views');
            $this->twig = new \Twig\Environment($loader, [
                'cache' => false,
                'debug' => true
            ]);
            // Añadir las extensiones necesarias
            $this->twig->addExtension(new \Twig\Extension\DebugExtension());
            $this->twig->addExtension(new \App\Extensions\TranslationExtension());
        } else {
            $this->twig = $twig;
        }
    }

    public function generateToken($username) {
        $payload = [
            'username' => $username,
            'iat' => time(),
            'exp' => time() + $this->cookieLifetime // Token válido por 30 días
        ];

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    public function isLoggedIn() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (isset($_SESSION['user_id'])) {
            return true;
        }
        
        if (isset($_COOKIE['remember_token'])) {
            try {
                $token = $_COOKIE['remember_token'];
                $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
                
                $_SESSION['user_id'] = 1;
                $_SESSION['username'] = $decoded->username;
                
                return true;
            } catch (\Exception $e) {
                setcookie('remember_token', '', time() - 3600, '/');
                error_log("Error validando token de cookie: " . $e->getMessage());
            }
        }
        
        return false;
    }

    public function loginPage() {
        if ($this->isLoggedIn()) {
            header('Location: /admin');
            exit;
        }
        return $this->twig->render('auth/login.twig');
    }

    public function login() {
        try {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']) && $_POST['remember'] === 'on';

            if ($username === 'admin' && $password === 'admin123') {
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['user_id'] = 1;
                $_SESSION['username'] = $username;
                
                if ($remember) {
                    $token = $this->generateToken($username);
                    setcookie(
                        'remember_token',
                        $token,
                        time() + $this->cookieLifetime,
                        '/',
                        '',
                        false,  // secure (false para desarrollo, true para producción)
                        true    // httponly
                    );
                }
                
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
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        
        setcookie('remember_token', '', time() - 3600, '/');
        
        header('Location: /login');
        exit;
    }

    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
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