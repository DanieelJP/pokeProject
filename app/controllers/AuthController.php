<?php
namespace App\Controllers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Controlador de Autenticación
 * 
 * Maneja todas las operaciones relacionadas con la autenticación de usuarios,
 * incluyendo login, logout, verificación de sesiones y generación de tokens JWT.
 */
class AuthController {
    private $twig;                      // Motor de plantillas Twig
    private $pdo;                       // Conexión a la base de datos
    private $secretKey;                 // Clave secreta para firmar los tokens JWT
    private $cookieLifetime = 2592000;  // 30 días en segundos (duración de la cookie)

    /**
     * Constructor que inicializa las dependencias
     * 
     * @param object|null $twig Instancia de Twig o null para crear una nueva
     */
    public function __construct($twig = null) {
        global $pdo;
        $this->pdo = $pdo;
        $this->secretKey = $_ENV['JWT_SECRET'] ?? 'tu_clave_secreta';
        
        if ($twig === null) {
            // Si no se proporciona una instancia de Twig, creamos una nueva
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

    /**
     * Genera un token JWT para un usuario
     * 
     * @param string $username Nombre de usuario
     * @return string Token JWT generado
     */
    public function generateToken($username) {
        // Crear el payload del token con información del usuario y fechas de emisión/expiración
        $payload = [
            'username' => $username,
            'iat' => time(),                         // Issued At: momento de emisión
            'exp' => time() + $this->cookieLifetime  // Expiration Time: momento de expiración
        ];

        // Codificar el token usando el algoritmo HS256
        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    /**
     * Verifica si el usuario está autenticado
     * 
     * Comprueba tanto la sesión activa como las cookies de "recordarme"
     * 
     * @return bool True si el usuario está autenticado, false en caso contrario
     */
    public function isLoggedIn() {
        // Iniciar sesión si no está activa
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Si hay una sesión activa, el usuario está autenticado
        if (isset($_SESSION['user_id'])) {
            return true;
        }
        
        // Si no hay sesión pero hay una cookie de "remember me", verificamos el token
        if (isset($_COOKIE['remember_token'])) {
            try {
                $token = $_COOKIE['remember_token'];
                // Decodificar y verificar el token JWT
                $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
                
                // Si el token es válido, recreamos la sesión
                $_SESSION['user_id'] = 1;
                $_SESSION['username'] = $decoded->username;
                
                return true;
            } catch (\Exception $e) {
                // Si el token no es válido, eliminamos la cookie
                setcookie('remember_token', '', time() - 3600, '/');
                error_log("Error validando token de cookie: " . $e->getMessage());
            }
        }
        
        // Si no hay sesión ni cookie válida, el usuario no está autenticado
        return false;
    }

    /**
     * Muestra la página de login
     * 
     * @return string HTML renderizado de la página de login
     */
    public function loginPage() {
        // Si el usuario ya está autenticado, redirigir al panel de admin
        if ($this->isLoggedIn()) {
            header('Location: /admin');
            exit;
        }
        
        // Mostrar la página de login
        return $this->twig->render('auth/login.twig', [
            'error' => $_GET['error'] ?? null
        ]);
    }

    /**
     * Procesa el formulario de login
     * 
     * @return string|void HTML renderizado con error o redirección al panel de admin
     */
    public function login() {
        try {
            // Obtener datos del formulario
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']) && $_POST['remember'] === 'on';

            // Verificación de credenciales (en producción debería usar hash)
            if ($username === 'admin' && $password === 'admin123') {
                // Iniciar sesión si no está activa
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                // Guardar datos del usuario en la sesión
                $_SESSION['user_id'] = 1;
                $_SESSION['username'] = $username;
                
                // Si el usuario marcó "Recordarme", establecer una cookie con el token
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
                
                // Redirigir al panel de administración
                header('Location: /admin');
                exit;
            }

            // Si las credenciales son incorrectas, mostrar error
            return $this->twig->render('auth/login.twig', [
                'error' => 'Credenciales inválidas'
            ]);
        } catch (\Exception $e) {
            // Registrar el error y mostrar mensaje
            error_log("Error en login: " . $e->getMessage());
            return $this->twig->render('auth/login.twig', [
                'error' => 'Error al iniciar sesión'
            ]);
        }
    }

    /**
     * Cierra la sesión del usuario
     * 
     * Destruye la sesión y elimina las cookies de "recordarme"
     */
    public function logout() {
        // Iniciar sesión si no está activa
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Destruir la sesión
        session_destroy();
        
        // Eliminar la cookie de "remember me"
        setcookie('remember_token', '', time() - 3600, '/');
        
        // Redirigir a la página de login
        header('Location: /login');
        exit;
    }

    /**
     * Verifica si el usuario está autenticado y redirige si no lo está
     */
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header('Location: /login');
            exit;
        }
    }

    /**
     * Valida un token JWT
     * 
     * @param string $token Token JWT a validar
     * @return bool True si el token es válido, false en caso contrario
     */
    public function validarToken($token) {
        try {
            // Decodificar y verificar el token
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            return true;
        } catch (\Exception $e) {
            // Registrar el error si el token no es válido
            error_log("Error validando token: " . $e->getMessage());
            return false;
        }
    }
}
?>