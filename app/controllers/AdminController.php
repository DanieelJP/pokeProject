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
        
        // Verificar la ruta absoluta
        $viewsPath = realpath(__DIR__ . '/../views');
        error_log("Ruta de vistas: " . $viewsPath);
        
        if (!is_dir($viewsPath)) {
            error_log("ERROR: La carpeta de vistas no existe: " . $viewsPath);
            throw new \Exception("La carpeta de vistas no existe");
        }
        
        $loader = new \Twig\Loader\FilesystemLoader($viewsPath);
        $this->twig = new \Twig\Environment($loader, [
            'cache' => false,
            'debug' => true
        ]);
        $this->twig->addExtension(new \Twig\Extension\DebugExtension());
    }

    public function dashboard() {
        try {
            error_log("=== Iniciando carga del dashboard ===");
            
            $data = [
                'pokemons' => $this->model->getAllPokemons(),
                'moves' => $this->model->getAllMoves(),
                'raids' => $this->model->getAllRaids(),
                'forms' => $this->model->getAllForms(),
                'images' => $this->model->getAllImages()
            ];

            error_log("Datos cargados:");
            error_log("Pokemons: " . count($data['pokemons']));
            error_log("Moves: " . count($data['moves']));
            error_log("Raids: " . count($data['raids']));
            error_log("Forms: " . count($data['forms']));
            error_log("Images: " . count($data['images']));

            return $this->twig->render('admin.twig', $data);
        } catch (\Exception $e) {
            error_log("Error en dashboard: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return $this->twig->render('404.twig', ['error' => 'Error al cargar el panel: ' . $e->getMessage()]);
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

    public function newMove() {
        return $this->twig->render('admin/move/edit.twig', [
            'move' => null,
            'pokemons' => $this->model->getAllPokemons()
        ]);
    }

    public function editMove($params) {
        try {
            $id = $params['id'] ?? null;
            if (!$id) {
                throw new \Exception('ID no especificado');
            }

            $move = $this->model->getMoveById($id);
            if (!$move) {
                throw new \Exception('Movimiento no encontrado');
            }

            return $this->twig->render('admin/move/edit.twig', [
                'move' => $move,
                'pokemons' => $this->model->getAllPokemons()
            ]);
        } catch (\Exception $e) {
            error_log("Error en editMove: " . $e->getMessage());
            return $this->twig->render('404.twig', ['error' => $e->getMessage()]);
        }
    }

    public function savePokemon() {
        try {
            $data = $_POST;
            $id = $data['id'] ?? null;
            
            if ($id) {
                $this->model->updatePokemon($id, $data);
            } else {
                $this->model->addPokemon($data);
            }
            
            header('Location: /admin');
            exit;
        } catch (\Exception $e) {
            error_log("Error en savePokemon: " . $e->getMessage());
            return $this->twig->render('404.twig', ['error' => 'Error al guardar el Pokémon']);
        }
    }

    public function newRaid() {
        return $this->twig->render('admin/raid/edit.twig', [
            'raid' => null,
            'pokemons' => $this->model->getAllPokemons()
        ]);
    }

    public function editRaid($params) {
        try {
            $id = $params['id'] ?? null;
            if (!$id) {
                throw new \Exception('ID no especificado');
            }

            $raid = $this->model->getRaidById($id);
            if (!$raid) {
                throw new \Exception('Raid no encontrada');
            }

            return $this->twig->render('admin/raid/edit.twig', [
                'raid' => $raid,
                'pokemons' => $this->model->getAllPokemons()
            ]);
        } catch (\Exception $e) {
            error_log("Error en editRaid: " . $e->getMessage());
            return $this->twig->render('404.twig', ['error' => $e->getMessage()]);
        }
    }

    public function saveRaid() {
        try {
            $data = $_POST;
            $id = $data['id'] ?? null;
            
            if ($id) {
                $this->model->updateRaid($id, $data);
            } else {
                $this->model->addRaid($data);
            }
            
            header('Location: /admin');
            exit;
        } catch (\Exception $e) {
            error_log("Error en saveRaid: " . $e->getMessage());
            return $this->twig->render('404.twig', ['error' => 'Error al guardar la raid']);
        }
    }

    public function newForm() {
        return $this->twig->render('admin/form/edit.twig', [
            'form' => null,
            'pokemons' => $this->model->getAllPokemons()
        ]);
    }

    public function editForm($params) {
        try {
            $id = $params['id'] ?? null;
            if (!$id) {
                throw new \Exception('ID no especificado');
            }

            $form = $this->model->getFormById($id);
            if (!$form) {
                throw new \Exception('Forma no encontrada');
            }

            return $this->twig->render('admin/form/edit.twig', [
                'form' => $form,
                'pokemons' => $this->model->getAllPokemons()
            ]);
        } catch (\Exception $e) {
            error_log("Error en editForm: " . $e->getMessage());
            return $this->twig->render('404.twig', ['error' => $e->getMessage()]);
        }
    }

    public function saveForm() {
        try {
            $data = $_POST;
            $id = $data['id'] ?? null;
            
            if ($id) {
                $this->model->updateForm($id, $data);
            } else {
                $this->model->addForm($data);
            }
            
            header('Location: /admin');
            exit;
        } catch (\Exception $e) {
            error_log("Error en saveForm: " . $e->getMessage());
            return $this->twig->render('404.twig', ['error' => 'Error al guardar la forma']);
        }
    }
} 