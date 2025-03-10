<?php
namespace App;

class Router {
    private $routes = [];
    private $twig;
    private $authController;
    private $notFoundHandler;

    public function __construct($twig, $authController) {
        $this->twig = $twig;
        $this->authController = $authController;
        $this->setRoutes();
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
        try {
            error_log("=== NUEVA PETICIÓN ===");
            error_log("Método original: " . $method);
            error_log("URI: " . $uri);

            // Obtener el método real si es una petición POST con _method
            if ($method === 'POST' && isset($_POST['_method'])) {
                $method = strtoupper($_POST['_method']);
                error_log("Método modificado a: " . $method);
            }

            // Para peticiones AJAX, verificar el método X-HTTP-Method-Override
            $headers = getallheaders();
            if (isset($headers['X-HTTP-Method-Override'])) {
                $method = strtoupper($headers['X-HTTP-Method-Override']);
            }

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
        } catch (\Exception $e) {
            error_log("Error en Router->handle: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return $this->twig->render('404.twig', ['error' => 'Error interno del servidor']);
        }
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

    public function setRoutes() {
        // Rutas existentes...
        
        // Rutas del panel de administración
        $adminController = new \App\Controllers\AdminController();
        
        // Ruta principal del admin
        $this->add('GET', '/admin', [$adminController, 'dashboard'], true);
        
        // Rutas CRUD para Pokémon
        $this->add('GET', '/admin/pokemon/new', [$adminController, 'newPokemon'], true);
        $this->add('GET', '/admin/pokemon/edit/:id', [$adminController, 'editPokemon'], true);
        $this->add('POST', '/admin/pokemon/save', [$adminController, 'savePokemon'], true);
        $this->add('POST', '/admin/pokemon/delete/:id', [$adminController, 'deletePokemon'], true);
        
        // Rutas CRUD para Movimientos
        $this->add('GET', '/admin/move/new', [$adminController, 'newMove'], true);
        $this->add('GET', '/admin/move/edit/:id', [$adminController, 'editMove'], true);
        $this->add('POST', '/admin/move/save', [$adminController, 'saveMove'], true);
        $this->add('POST', '/admin/move/delete/:id', [$adminController, 'deleteMove'], true);
        
        // Rutas CRUD para Raids
        $this->add('GET', '/admin/raid/new', [$adminController, 'newRaid'], true);
        $this->add('GET', '/admin/raid/edit/:id', [$adminController, 'editRaid'], true);
        $this->add('POST', '/admin/raid/save', [$adminController, 'saveRaid'], true);
        $this->add('POST', '/admin/raid/delete/:id', [$adminController, 'deleteRaid'], true);
        
        // Rutas CRUD para Formas
        $this->add('GET', '/admin/form/new', [$adminController, 'newForm'], true);
        $this->add('GET', '/admin/form/edit/:id', [$adminController, 'editForm'], true);
        $this->add('POST', '/admin/form/save', [$adminController, 'saveForm'], true);
        $this->add('POST', '/admin/form/delete/:id', [$adminController, 'deleteForm'], true);
    }
} 