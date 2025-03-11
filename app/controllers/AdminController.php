<?php
namespace App\Controllers;

use App\Models\PokemonModel;
use App\Models\MoveModel;
use App\Models\RaidModel;
use App\Models\FormModel;

class AdminController {
    private $model;
    private $twig;
    private $pdo;
    private $authController;

    public function __construct($twig = null) {
        global $pdo;
        $this->pdo = $pdo;
        $this->model = new PokemonModel();
        
        // Si no se proporciona una instancia de Twig, crear una nueva
        if ($twig === null) {
            $viewsPath = realpath(__DIR__ . '/../views');
            $loader = new \Twig\Loader\FilesystemLoader($viewsPath);
            $this->twig = new \Twig\Environment($loader, [
                'cache' => false,
                'debug' => true
            ]);
            // Añadir el filtro de traducción directamente
            $this->twig->addFilter(new \Twig\TwigFilter('trans', function ($string) {
                return gettext($string);
            }));
        } else {
            $this->twig = $twig;
        }

        $this->authController = new AuthController();
    }

    public function dashboard() {
        if (!$this->authController->isLoggedIn()) {
            header('Location: /login');
            exit;
        }

        $pokemonModel = new PokemonModel();
        $moveModel = new MoveModel();
        $raidModel = new RaidModel();
        $formModel = new FormModel();

        // Obtener los contadores
        $pokemon_count = $pokemonModel->getCount();
        $moves_count = $moveModel->getCount();
        $raids_count = $raidModel->getCount();
        $forms_count = $formModel->getCount();

        // Obtener los datos
        $pokemons = $pokemonModel->getAllPokemons();
        $moves = $moveModel->getAllMoves();
        $raids = $raidModel->getAllRaids();
        $forms = $formModel->getAllForms();

        return $this->twig->render('admin/dashboard.twig', [
            'pokemon_count' => $pokemon_count,
            'moves_count' => $moves_count,
            'raids_count' => $raids_count,
            'forms_count' => $forms_count,
            'pokemons' => $pokemons,
            'moves' => $moves,
            'raids' => $raids,
            'forms' => $forms
        ]);
    }

    public function editPokemon($params) {
        try {
            $id = $params['id'] ?? null;
            if (!$id) {
                throw new \Exception('ID no especificado');
            }

            $pokemon = $this->model->getPokemonById($id);
            if (!$pokemon) {
                throw new \Exception('Pokémon no encontrado');
            }

            return $this->twig->render('admin/pokemon/edit.twig', [
                'pokemon' => $pokemon,
                'action' => 'edit',
                'success' => $_GET['success'] ?? null,
                'error' => $_GET['error'] ?? null
            ]);
        } catch (\Exception $e) {
            error_log("Error en editPokemon: " . $e->getMessage());
            return $this->twig->render('admin/pokemon/edit.twig', [
                'error' => $e->getMessage(),
                'action' => 'edit'
            ]);
        }
    }

    public function newPokemon() {
        return $this->twig->render('admin/pokemon/edit.twig', [
            'pokemon' => null,
            'action' => 'new'
        ]);
    }

    public function newMove() {
        try {
            return $this->twig->render('admin/move/edit.twig', [
                'move' => null,
                'action' => 'new',
                'pokemons' => $this->model->getAllPokemons()
            ]);
        } catch (\Exception $e) {
            error_log("Error en newMove: " . $e->getMessage());
            return $this->twig->render('404.twig', ['error' => $e->getMessage()]);
        }
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
                'action' => 'edit',
                'pokemons' => $this->model->getAllPokemons(),
                'success' => $_GET['success'] ?? null,
                'error' => $_GET['error'] ?? null
            ]);
        } catch (\Exception $e) {
            error_log("Error en editMove: " . $e->getMessage());
            return $this->twig->render('404.twig', ['error' => $e->getMessage()]);
        }
    }

    public function saveMove() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            $data = $_POST;
            $id = $data['id'] ?? null;

            // Validaciones básicas
            if (empty($data['pokemon_id'])) {
                throw new \Exception('El Pokémon es requerido');
            }
            if (empty($data['move_type'])) {
                throw new \Exception('El tipo de movimiento es requerido');
            }
            if (empty($data['move_name'])) {
                throw new \Exception('El nombre del movimiento es requerido');
            }

            if ($id) {
                $this->model->updateMove($id, $data);
                $message = 'Movimiento actualizado correctamente';
            } else {
                $this->model->addMove($data);
                $message = 'Movimiento añadido correctamente';
            }

            header('Location: /admin?success=' . urlencode($message));
            exit;
        } catch (\Exception $e) {
            error_log("Error en saveMove: " . $e->getMessage());
            return $this->twig->render('admin/move/edit.twig', [
                'move' => $data ?? null,
                'action' => $id ? 'edit' : 'new',
                'pokemons' => $this->model->getAllPokemons(),
                'error' => $e->getMessage()
            ]);
        }
    }

    public function deleteMove($params) {
        try {
            $id = $params['id'] ?? null;
            if (!$id) {
                throw new \Exception('ID no especificado');
            }

            $this->model->deleteMove($id);
            header('Location: /admin?success=' . urlencode('Movimiento eliminado correctamente'));
            exit;
        } catch (\Exception $e) {
            error_log("Error en deleteMove: " . $e->getMessage());
            header('Location: /admin?error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    public function savePokemon() {
        try {
            error_log("=== Iniciando savePokemon ===");
            error_log("Método HTTP: " . $_SERVER['REQUEST_METHOD']);
            error_log("Datos POST recibidos: " . print_r($_POST, true));

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            $data = $_POST;
            $id = $data['id'] ?? null;
            
            error_log("ID del Pokémon: " . ($id ? $id : 'nuevo'));
            
            // Validaciones básicas
            if (empty($data['pokemon_id'])) {
                throw new \Exception('El ID del Pokémon es requerido');
            }
            if (empty($data['name'])) {
                throw new \Exception('El nombre es requerido');
            }
            if (empty($data['region'])) {
                throw new \Exception('La región es requerida');
            }

            // Sanitizar datos
            $pokemonData = [
                'pokemon_id' => trim($data['pokemon_id']),
                'name' => trim($data['name']),
                'region' => trim($data['region'])
            ];

            error_log("Datos sanitizados: " . print_r($pokemonData, true));

            if ($id) {
                error_log("Actualizando Pokémon existente");
                $success = $this->model->updatePokemon($id, $pokemonData);
                $message = 'Pokémon actualizado correctamente';
            } else {
                error_log("Creando nuevo Pokémon");
                $success = $this->model->addPokemon($pokemonData);
                $message = 'Pokémon añadido correctamente';
            }
            
            error_log("Operación completada. Redirigiendo...");
            header('Location: /admin?success=' . urlencode($message));
            exit;
        } catch (\Exception $e) {
            error_log("Error en savePokemon: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return $this->twig->render('admin/pokemon/edit.twig', [
                'pokemon' => $data ?? null,
                'action' => $id ? 'edit' : 'new',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function newRaid() {
        try {
            return $this->twig->render('admin/raid/edit.twig', [
                'raid' => null,
                'action' => 'new',
                'pokemons' => $this->model->getAllPokemons()
            ]);
        } catch (\Exception $e) {
            error_log("Error en newRaid: " . $e->getMessage());
            return $this->twig->render('404.twig', ['error' => $e->getMessage()]);
        }
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
                'action' => 'edit',
                'pokemons' => $this->model->getAllPokemons(),
                'success' => $_GET['success'] ?? null,
                'error' => $_GET['error'] ?? null
            ]);
        } catch (\Exception $e) {
            error_log("Error en editRaid: " . $e->getMessage());
            return $this->twig->render('404.twig', ['error' => $e->getMessage()]);
        }
    }

    public function saveRaid() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            $data = $_POST;
            $id = $data['id'] ?? null;

            // Validaciones básicas
            if (empty($data['pokemon_id'])) {
                throw new \Exception('El Pokémon es requerido');
            }

            if ($id) {
                $this->model->updateRaid($id, $data);
                $message = 'Raid actualizada correctamente';
            } else {
                $this->model->addRaid($data);
                $message = 'Raid añadida correctamente';
            }

            header('Location: /admin?success=' . urlencode($message));
            exit;
        } catch (\Exception $e) {
            error_log("Error en saveRaid: " . $e->getMessage());
            return $this->twig->render('admin/raid/edit.twig', [
                'raid' => $data ?? null,
                'action' => $id ? 'edit' : 'new',
                'pokemons' => $this->model->getAllPokemons(),
                'error' => $e->getMessage()
            ]);
        }
    }

    public function deleteRaid($params) {
        try {
            $id = $params['id'] ?? null;
            if (!$id) {
                throw new \Exception('ID no especificado');
            }

            $this->model->deleteRaid($id);
            header('Location: /admin?success=' . urlencode('Raid eliminada correctamente'));
            exit;
        } catch (\Exception $e) {
            error_log("Error en deleteRaid: " . $e->getMessage());
            header('Location: /admin?error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    public function newForm() {
        try {
            return $this->twig->render('admin/form/edit.twig', [
                'form' => null,
                'action' => 'new',
                'pokemons' => $this->model->getAllPokemons()
            ]);
        } catch (\Exception $e) {
            error_log("Error en newForm: " . $e->getMessage());
            return $this->twig->render('404.twig', ['error' => $e->getMessage()]);
        }
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
                'action' => 'edit',
                'pokemons' => $this->model->getAllPokemons(),
                'success' => $_GET['success'] ?? null,
                'error' => $_GET['error'] ?? null
            ]);
        } catch (\Exception $e) {
            error_log("Error en editForm: " . $e->getMessage());
            return $this->twig->render('404.twig', ['error' => $e->getMessage()]);
        }
    }

    public function saveForm() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception('Método no permitido');
            }

            $data = $_POST;
            $id = $data['id'] ?? null;

            // Validaciones básicas
            if (empty($data['pokemon_id'])) {
                throw new \Exception('El Pokémon base es requerido');
            }
            if (empty($data['form_name'])) {
                throw new \Exception('El nombre de la forma es requerido');
            }

            if ($id) {
                $this->model->updateForm($id, $data);
                $message = 'Forma actualizada correctamente';
            } else {
                $this->model->addForm($data);
                $message = 'Forma añadida correctamente';
            }

            header('Location: /admin?success=' . urlencode($message));
            exit;
        } catch (\Exception $e) {
            error_log("Error en saveForm: " . $e->getMessage());
            return $this->twig->render('admin/form/edit.twig', [
                'form' => $data ?? null,
                'action' => $id ? 'edit' : 'new',
                'pokemons' => $this->model->getAllPokemons(),
                'error' => $e->getMessage()
            ]);
        }
    }

    public function deleteForm($params) {
        try {
            $id = $params['id'] ?? null;
            if (!$id) {
                throw new \Exception('ID no especificado');
            }

            $this->model->deleteForm($id);
            header('Location: /admin?success=' . urlencode('Forma eliminada correctamente'));
            exit;
        } catch (\Exception $e) {
            error_log("Error en deleteForm: " . $e->getMessage());
            header('Location: /admin?error=' . urlencode($e->getMessage()));
            exit;
        }
    }

    public function deletePokemon($params) {
        try {
            error_log("=== Iniciando deletePokemon ===");
            error_log("Parámetros recibidos: " . print_r($params, true));

            $id = $params['id'] ?? null;
            if (!$id) {
                throw new \Exception('ID no especificado');
            }

            $result = $this->model->deletePokemon($id);
            
            // Redirigir después de la eliminación
            header('Location: /admin?success=' . urlencode('Pokémon eliminado correctamente'));
            exit;
        } catch (\Exception $e) {
            error_log("Error en deletePokemon: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            header('Location: /admin?error=' . urlencode($e->getMessage()));
            exit;
        }
    }
} 