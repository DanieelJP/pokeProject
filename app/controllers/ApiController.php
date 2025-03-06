<?php
namespace App\Controllers;

class ApiController {
    private $pdo;
    private $authController;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->authController = new AuthController();
    }

    public function login() {
        try {
            // Obtener datos del POST en formato JSON
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';

            // Validar credenciales
            if ($username === 'admin' && $password === 'admin123') {
                $token = $this->authController->generateToken($username);
                
                http_response_code(200);
                echo json_encode([
                    'status' => 'success',
                    'token' => $token
                ]);
            } else {
                http_response_code(401);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Credenciales inválidas'
                ]);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error en el servidor'
            ]);
        }
    }

    public function validateToken() {
        try {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? '';
            
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                $token = $matches[1];
                $isValid = $this->authController->validarToken($token);
                
                if ($isValid) {
                    http_response_code(200);
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Token válido'
                    ]);
                    return;
                }
            }

            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'Token inválido o no proporcionado'
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Error en el servidor'
            ]);
        }
    }
} 