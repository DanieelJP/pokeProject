<?php
require __DIR__ . '/../vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../app/controllers/AuthController.php';
require __DIR__ . '/../config/database.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../app/views');
$twig = new \Twig\Environment($loader, ['cache' => false]);

$request = $_SERVER['REQUEST_URI'];

switch ($request) {
    case '/':
        // Obtener datos de la tabla pokemons
        $stmt = $conn->query("SELECT * FROM pokemons");
        $pokemons = $stmt->fetchAll(PDO::FETCH_ASSOC);

        var_dump($pokemons);
        // Obtener datos de la tabla moves para cada pokemon
        /*
        foreach ($pokemons as &$pokemon) {
            $stmt = $conn->prepare("SELECT * FROM moves WHERE pokemon_id = :pokemon_id");
            $stmt->execute(['pokemon_id' => $pokemon['pokemon_id']]);
            $pokemon['moves'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener datos de la tabla raids para cada pokemon
            $stmt = $conn->prepare("SELECT * FROM raids WHERE pokemon_id = :pokemon_id");
            $stmt->execute(['pokemon_id' => $pokemon['pokemon_id']]);
            $pokemon['raids'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener datos de la tabla pokemon_forms para cada pokemon
            $stmt = $conn->prepare("SELECT * FROM pokemon_forms WHERE pokemon_id = :pokemon_id");
            $stmt->execute(['pokemon_id' => $pokemon['pokemon_id']]);
            $pokemon['forms'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obtener datos de la tabla pokemon_images para cada pokemon
            $stmt = $conn->prepare("SELECT * FROM pokemon_images WHERE pokemon_id = :pokemon_id");
            $stmt->execute(['pokemon_id' => $pokemon['pokemon_id']]);
            $pokemon['images'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
*/
        // Pasar datos a la plantilla
        echo $twig->render('home.twig', ['pokemons' => $pokemons]);
        break;

    case '/admin':
        $token = $_COOKIE['jwt'] ?? null;
        if ($token && AuthController::validarToken($token)) {
            echo $twig->render('admin.twig');
        } else {
            header("Location: /login");
        }
        break;

    case '/login':
        $userId = 1; // Ejemplo: ID del usuario autenticado
        $token = AuthController::generarToken($userId);
        setcookie('jwt', $token, time() + 3600, '/');
        header("Location: /admin");
        break;

    default:
        header("HTTP/1.0 404 Not Found");
        echo $twig->render('404.twig');
        break;
}
?>