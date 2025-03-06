<?php
namespace App\Controllers;

use App\Models\PokemonModel;

class AdminController {
    private $model;
    private $twig;
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->model = new PokemonModel();
        $loader = new \Twig\Loader\FilesystemLoader('../app/views');
        $this->twig = new \Twig\Environment($loader, [
            'cache' => false,
            'debug' => true
        ]);
        $this->twig->addExtension(new \Twig\Extension\DebugExtension());
    }

    public function dashboard() {
        try {
            $data = [
                'pokemons' => $this->model->getAllPokemons(),
                'moves' => $this->model->getAllMoves(),
                'raids' => $this->model->getAllRaids(),
                'forms' => $this->model->getAllForms(),
                'images' => $this->model->getAllImages()
            ];

            return $this->twig->render('admin/dashboard.twig', $data);
        } catch (\Exception $e) {
            error_log("Error en dashboard: " . $e->getMessage());
            return $this->twig->render('404.twig', ['error' => 'Error al cargar el panel']);
        }
    }

    public function editPokemon($params) {
        try {
            $id = $params['id'] ?? null;
            if (!$id) {
                return $this->twig->render('404.twig', ['error' => 'ID no especificado']);
            }

            $pokemon = $this->model->getPokemonById($id);
            if (!$pokemon) {
                return $this->twig->render('404.twig', ['error' => 'Pokémon no encontrado']);
            }

            return $this->twig->render('admin/edit.twig', [
                'pokemon' => $pokemon
            ]);
        } catch (\Exception $e) {
            error_log("Error en editPokemon: " . $e->getMessage());
            return $this->twig->render('404.twig', ['error' => 'Error al cargar el Pokémon']);
        }
    }

    public function newPokemon() {
        return $this->twig->render('admin/edit.twig', [
            'pokemon' => null
        ]);
    }
} 