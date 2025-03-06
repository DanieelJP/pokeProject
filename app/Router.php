<?php
namespace App;

class Router {
    private $routes = [];
    private $twig;
    private $authController;

    public function __construct($twig, $authController) {
        $this->twig = $twig;
        $this->authController = $authController;
    }

    public function add($method, $path, $handler, $requiresAuth = false) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'requiresAuth' => $requiresAuth
        ];
    }

    public function handle($method, $uri) {
        error_log("\n\n=== NUEVA PETICIÓN ===");
        error_log("Método: " . $method);
        error_log("URI: " . $uri);
        
        // Si es una ruta de API
        if (strpos($uri, '/api/') === 0) {
            header('Content-Type: application/json');
            
            // Verificar token para rutas protegidas
            if ($uri !== '/api/login') {
                $headers = getallheaders();
                $authHeader = $headers['Authorization'] ?? '';
                
                if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                    $this->sendJsonResponse([
                        'status' => 'error',
                        'message' => 'Token no proporcionado'
                    ], 401);
                }

                $token = $matches[1];
                if (!$this->authController->validarToken($token)) {
                    $this->sendJsonResponse([
                        'status' => 'error',
                        'message' => 'Token inválido'
                    ], 401);
                }
            }
        }

        // Limpiamos la URI
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/');
        if (empty($uri)) {
            $uri = '/';
        }

        error_log("\n=== INICIO ROUTER ===");
        error_log("URI solicitada: " . $uri);
        error_log("Método: " . $method);

        foreach ($this->routes as $route) {
            error_log("\nVerificando ruta: " . $route['path']);
            error_log("URI actual: " . $uri);
            error_log("Método actual: " . $method);
            
            $pattern = $this->convertRouteToRegex($route['path']);
            error_log("Patrón regex: " . $pattern);
            
            if (preg_match($pattern, $uri, $matches)) {
                error_log("¡Coincidencia encontrada!");
                error_log("Matches: " . print_r($matches, true));
                
                $params = $this->extractParams($route['path'], $uri);
                error_log("Parámetros extraídos: " . print_r($params, true));

                if ($route['method'] === $method) {
                    error_log("Ejecutando controlador para ruta: " . $route['path']);
                    if ($route['requiresAuth']) {
                        $token = $_COOKIE['jwt'] ?? null;
                        if (!$token || !$this->authController->validarToken($token)) {
                            header('Location: /login');
                            exit;
                        }
                    }
                    return call_user_func($route['handler'], $params);
                }
            }
        }

        error_log("=== No se encontró ninguna ruta ===\n");
        return $this->twig->render('404.twig', ['error' => 'Página no encontrada']);
    }

    private function convertRouteToRegex($route) {
        $regex = '#^' . preg_replace('/:[a-zA-Z]+/', '([^/]+)', $route) . '$#';
        error_log("Ruta convertida a regex: " . $route . " -> " . $regex);
        return $regex;
    }

    private function extractParams($route, $uri) {
        $routeParts = explode('/', trim($route, '/'));
        $uriParts = explode('/', trim($uri, '/'));
        
        error_log("Partes de la ruta: " . print_r($routeParts, true));
        error_log("Partes de la URI: " . print_r($uriParts, true));
        
        $params = [];
        foreach ($routeParts as $index => $part) {
            if (strpos($part, ':') === 0) {
                $paramName = substr($part, 1);
                $params[$paramName] = $uriParts[$index];
                error_log("Parámetro encontrado: {$paramName} = {$uriParts[$index]}");
            }
        }
        
        return $params;
    }

    private function sendJsonResponse($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
} 