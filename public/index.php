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
require_once __DIR__ . '/../app/models/MoveModel.php';
require_once __DIR__ . '/../app/models/RaidModel.php';
require_once __DIR__ . '/../app/models/FormModel.php';
require_once __DIR__ . '/../app/extensions/TranslationExtension.php';

use App\Router;
use App\Controllers\AuthController;
use App\Controllers\PokemonController;
use App\Controllers\AdminController;
use App\Controllers\ApiController;
use App\Extensions\TranslationExtension;
use Twig\TwigFilter;

// Configuración de idioma
$availableLanguages = ['es', 'en'];
$defaultLanguage = 'es';

// Obtener el idioma de la cookie o usar el predeterminado
$lang = $_COOKIE['lang'] ?? $defaultLanguage;
$lang = in_array($lang, $availableLanguages) ? $lang : $defaultLanguage;

// Mapear idiomas a locales disponibles
$localeMap = [
    'en' => 'en_US.utf8',
    'es' => 'es_ES.utf8'
];

$locale = $localeMap[$lang] ?? 'es_ES.utf8';

// Configurar locale y gettext
putenv("LC_ALL=$locale");
putenv("LANGUAGE=$locale");
putenv("LANG=$locale");
setlocale(LC_ALL, $locale);

// Debug
error_log("=== Configuración de idioma ===");
error_log("Cookie lang: " . ($lang ?? 'no cookie'));
error_log("Locale configurado: " . setlocale(LC_ALL, 0));
error_log("LANG env: " . getenv("LANG"));
error_log("LANGUAGE env: " . getenv("LANGUAGE"));
error_log("LC_ALL env: " . getenv("LC_ALL"));

// Especificar la ubicación de las traducciones
$domain = "messages";
$localePath = dirname(__DIR__) . "/app/lang";
bindtextdomain($domain, $localePath);
bind_textdomain_codeset($domain, 'UTF-8');
textdomain($domain);

// Probar una traducción
error_log("Prueba de traducción 'Panel de Administración': " . gettext("Panel de Administración"));

// Configurar Twig
$loader = new \Twig\Loader\FilesystemLoader(dirname(__DIR__) . '/app/views');
$twig = new \Twig\Environment($loader, [
    'cache' => false,
    'debug' => true
]);

// Añadir extensiones de Twig
$twig->addExtension(new \Twig\Extension\DebugExtension());

// Añadir filtro de traducción directamente
$twig->addFilter(new TwigFilter('trans', function ($string, $params = []) {
    if (!empty($params)) {
        return vsprintf(gettext($string), $params);
    }
    return gettext($string);
}));

// Añadir variable global para el idioma actual
$twig->addGlobal('locale', $lang);

// Inicializar controladores con la misma instancia de Twig
$authController = new AuthController($twig);
$pokemonController = new PokemonController($twig);
$adminController = new AdminController($twig);
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