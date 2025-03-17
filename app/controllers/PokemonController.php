<?php
namespace App\Controllers;

use App\Models\PokemonModel;

/**
 * Controlador para gestionar las operaciones relacionadas con Pokémon
 * 
 * Maneja la visualización de la página principal y los detalles de cada Pokémon
 */
class PokemonController {
    private $model; // Instancia del modelo de Pokémon
    private $twig;  // Motor de plantillas Twig
    private $pdo;   // Conexión a la base de datos

    /**
     * Constructor que inicializa las dependencias
     * 
     * @param object|null $twig Instancia de Twig o null para crear una nueva
     */
    public function __construct($twig = null) {
        global $pdo;
        $this->pdo = $pdo;
        $this->model = new PokemonModel();
        $this->twig = $twig;
    }

    /**
     * Método para mostrar la página principal con el listado de Pokémon
     * 
     * @return string HTML renderizado de la página principal
     */
    public function home() {
        try {
            error_log("=== Iniciando método home() ===");
            
            // Obtener los Pokémon desde el modelo
            $pokemons = $this->model->getAllPokemons();
            if (!$pokemons) {
                error_log("No se encontraron Pokémon");
                $pokemons = [];
            }
            
            // Renderizar la vista con los datos obtenidos
            return $this->twig->render('home.twig', [
                'pokemons' => $pokemons,
                'locale' => $_COOKIE['lang'] ?? 'es' // Idioma por defecto: español
            ]);
            
        } catch (\Exception $e) {
            // Registrar el error para depuración
            error_log("Error en home(): " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // En caso de error, mostrar la página 404 con un mensaje de error
            return $this->twig->render('404.twig', [
                'error' => 'Error al cargar los Pokémon: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Método para mostrar los detalles de un Pokémon específico
     * 
     * @param array $params Parámetros de la URL, debe contener 'id'
     * @return string HTML renderizado con los detalles del Pokémon
     */
    public function showPokemon($params) {
        try {
            // Extraer el ID del Pokémon de los parámetros
            $id = $params['id'] ?? null;
            
            // Verificar que se proporcionó un ID
            if (!$id) {
                return $this->twig->render('404.twig', ['error' => 'ID de Pokémon no especificado']);
            }

            // Obtener los datos del Pokémon desde el modelo
            $pokemon = $this->model->getPokemonById($id);
            
            // Verificar que el Pokémon existe
            if (!$pokemon) {
                return $this->twig->render('404.twig', ['error' => 'Pokémon no encontrado']);
            }

            // Renderizar la vista con los datos del Pokémon
            return $this->twig->render('pokemon.twig', [
                'pokemon' => $pokemon,
                'is_authenticated' => isset($_COOKIE['jwt']) // Verificar si el usuario está autenticado
            ]);
        } catch (\Exception $e) {
            // Registrar el error y mostrar página 404
            error_log("Error en showPokemon: " . $e->getMessage());
            return $this->twig->render('404.twig', ['error' => 'Error al cargar el Pokémon']);
        }
    }
}
?>
