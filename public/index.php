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

// Configuración de internacionalización
$lang = $_COOKIE['lang'] ?? 'es';
putenv("LANG=$lang");
setlocale(LC_ALL, $lang);
bindtextdomain("messages", "../app/lang");
textdomain("messages");

// Añadir filtro de traducción a Twig
$twig->addFilter(new \Twig\TwigFilter('trans', function ($string) {
    return gettext($string);
}));

// Inicializar controladores
$authController = new AuthController();
$pokemonController = new PokemonController();
$adminController = new AdminController();
$apiController = new ApiController();

// Configurar router
$router = new Router($twig, $authController);

// Definir rutas
error_log("Registrando rutas...");

// Ruta principal
$router->add('GET', '/', [$pokemonController, 'home']);
error_log("Ruta '/' registrada");

// Ruta para Pokémon individual
$router->add('GET', '/pokemon/:id', [$pokemonController, 'showPokemon']);
error_log("Ruta '/pokemon/:id' registrada");

// Otras rutas...
$router->add('GET', '/admin', [$adminController, 'dashboard'], true);
$router->add('GET', '/admin/pokemon/edit/:id', [$adminController, 'editPokemon'], true);
$router->add('GET', '/admin/pokemon/new', [$adminController, 'newPokemon'], true);
$router->add('GET', '/login', [$authController, 'loginPage']);
$router->add('POST', '/login', [$authController, 'login']);
$router->add('GET', '/logout', [$authController, 'logout']);

// Añadir rutas de la API
$router->add('POST', '/api/login', [$apiController, 'login']);
$router->add('POST', '/api/validate-token', [$apiController, 'validateToken']);

// Manejar la solicitud actual
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

error_log("\n=== PROCESANDO SOLICITUD ===");
error_log("Método: " . $method);
error_log("URI: " . $uri);

try {
    echo $router->handle($method, $uri);
} catch (Exception $e) {
    error_log("Error al procesar la solicitud: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo $twig->render('404.twig', [
        'error' => 'Error: ' . $e->getMessage()
    ]);
}