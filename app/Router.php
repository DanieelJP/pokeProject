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
            error_log("Manejando ruta: $method $uri");
            
            // Eliminar query strings
            $uri = strtok($uri, '?');
            
            foreach ($this->routes as $route) {
                $pattern = $this->getPattern($route['path']);
                if ($method === $route['method'] && preg_match($pattern, $uri, $matches)) {
                    error_log("Ruta encontrada: " . $route['path']);
                    
                    // Verificar autenticación si es necesario
                    if ($route['requiresAuth'] && !isset($_COOKIE['jwt'])) {
                        error_log("Redirigiendo a login - autenticación requerida");
                        header('Location: /login');
                        exit;
                    }

                    // Extraer parámetros de la URL
                    $params = $this->extractParams($route['path'], $uri);
                    
                    // Llamar al controlador
                    return call_user_func($route['handler'], $params);
                }
            }
            
            error_log("Ruta no encontrada: $method $uri");
            return $this->twig->render('404.twig', ['error' => 'Página no encontrada']);
            
        } catch (\Exception $e) {
            error_log("Error en Router::handle: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return $this->twig->render('404.twig', [
                'error' => 'Error interno del servidor: ' . $e->getMessage()
            ]);
        }
    }

    private function getPattern($path) {
        return '#^' . preg_replace('#:([a-zA-Z0-9_]+)#', '([^/]+)', $path) . '$#';
    }

    private function extractParams($path, $uri) {
        $params = [];
        $pathParts = explode('/', trim($path, '/'));
        $uriParts = explode('/', trim($uri, '/'));
        
        foreach ($pathParts as $index => $part) {
            if (strpos($part, ':') === 0) {
                $paramName = substr($part, 1);
                $params[$paramName] = $uriParts[$index];
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