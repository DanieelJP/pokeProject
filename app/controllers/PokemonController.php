<?php
namespace App\Controllers;

use App\Models\PokemonModel;

class PokemonController {
    private $model;
    private $twig;
    private $pdo;

    public function __construct($twig = null) {
        global $pdo;
        $this->pdo = $pdo;
        $this->model = new PokemonModel();
        $this->twig = $twig;
    }

    public function home() {
        try {
            error_log("=== Iniciando método home() ===");
            
            // Obtener los Pokémon
            $pokemons = $this->model->getAllPokemons();
            if (!$pokemons) {
                error_log("No se encontraron Pokémon");
                $pokemons = [];
            }
            
            // Renderizar la vista
            return $this->twig->render('home.twig', [
                'pokemons' => $pokemons
            ]);
            
        } catch (\Exception $e) {
            error_log("Error en home(): " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            
            // En caso de error, mostrar la página con un mensaje de error
            return $this->twig->render('404.twig', [
                'error' => 'Error al cargar los Pokémon: ' . $e->getMessage()
            ]);
        }
    }

    public function showPokemon($params) {
        try {
            $id = $params['id'] ?? null;
            
            if (!$id) {
                return $this->twig->render('404.twig', ['error' => 'ID de Pokémon no especificado']);
            }

            $pokemon = $this->model->getPokemonById($id);
            
            if (!$pokemon) {
                return $this->twig->render('404.twig', ['error' => 'Pokémon no encontrado']);
            }

            return $this->twig->render('pokemon.twig', [
                'pokemon' => $pokemon,
                'is_authenticated' => isset($_COOKIE['jwt'])
            ]);
        } catch (\Exception $e) {
            error_log("Error en showPokemon: " . $e->getMessage());
            return $this->twig->render('404.twig', ['error' => 'Error al cargar el Pokémon']);
        }
    }
}
?>
