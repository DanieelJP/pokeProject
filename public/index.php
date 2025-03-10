<?php
// public/index.php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/Router.php';
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/PokemonController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';
require_once __DIR__ . '/../app/controllers/ApiController.php';
require_once __DIR__ . '/../app/models/PokemonModel.php';

use App\Router;
use App\Controllers\PokemonController;
use App\Controllers\AuthController;
use App\Controllers\AdminController;
use App\Controllers\ApiController;

// Configuración de Twig
$loader = new \Twig\Loader\FilesystemLoader('../app/views');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'debug' => true
]);

$twig->addExtension(new \Twig\Extension\DebugExtension());

// Inicializar controladores
$authController = new AuthController();
$pokemonController = new PokemonController();
$adminController = new AdminController();
$apiController = new ApiController();

// Configurar router
$router = new Router($twig, $authController);

// Definir rutas
$router->add('GET', '/', [$pokemonController, 'home']);
$router->add('GET', '/pokemon/:id', [$pokemonController, 'showPokemon']);
$router->add('GET', '/admin', [$adminController, 'dashboard'], true);
$router->add('GET', '/login', [$authController, 'loginPage']);
$router->add('POST', '/login', [$authController, 'login']);
$router->add('GET', '/logout', [$authController, 'logout']);

// Manejar la solicitud actual
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

try {
    echo $router->handle($method, $uri);
} catch (Exception $e) {
    error_log("Error al procesar la solicitud: " . $e->getMessage());
    echo $twig->render('404.twig', [
        'error' => 'Error: ' . $e->getMessage()
    ]);
}