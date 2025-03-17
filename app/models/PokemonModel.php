<?php
namespace App\Models;

/**
 * Clase PokemonModel
 * 
 * Maneja todas las operaciones relacionadas con los Pokémon en la base de datos,
 * incluyendo consultas, inserciones, actualizaciones y eliminaciones.
 */
class PokemonModel {
    private $pdo; // Conexión a la base de datos

    /**
     * Constructor que inicializa la conexión a la base de datos
     */
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Obtiene todos los Pokémon de la base de datos
     * 
     * @return array Lista de todos los Pokémon con su imagen principal
     */
    public function getAllPokemons() {
        try {
            // Consulta que obtiene los Pokémon junto con su primera imagen
            $stmt = $this->pdo->query("
                SELECT p.*, 
                       (SELECT pi.image_path 
                        FROM pokemon_images pi 
                        WHERE pi.pokemon_id = p.pokemon_id 
                        LIMIT 1) as image_path
                FROM pokemons p 
                ORDER BY CAST(p.pokemon_id AS UNSIGNED)
            ");
            
            $pokemons = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Procesamos los resultados para darles formato
            $result = [];
            foreach ($pokemons as $pokemon) {
                $result[] = [
                    'id' => $pokemon['id'],
                    'name' => $pokemon['name'],
                    'pokemon_id' => $pokemon['pokemon_id'],
                    'region' => $pokemon['region'],
                    'images' => $pokemon['image_path'] ? [
                        ['image_path' => $pokemon['image_path']]
                    ] : []
                ];
            }
            
            return $result;
        } catch (\PDOException $e) {
            // Registramos el error y lo propagamos
            error_log("Error en getAllPokemons: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene un Pokémon específico por su ID
     * 
     * @param string $id ID del Pokémon a buscar
     * @return array|null Datos completos del Pokémon o null si no existe
     */
    public function getPokemonById($id) {
        try {
            // Obtener información básica del Pokémon
            $stmt = $this->pdo->prepare("
                SELECT p.*, 
                       (SELECT pi.image_path 
                        FROM pokemon_images pi 
                        WHERE pi.pokemon_id = p.pokemon_id 
                        LIMIT 1) as image_path
                FROM pokemons p 
                WHERE p.pokemon_id = :id
            ");
            
            $stmt->execute(['id' => $id]);
            $pokemon = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$pokemon) {
                return null;
            }
            
            // Obtener imágenes
            $stmt = $this->pdo->prepare("
                SELECT * FROM pokemon_images 
                WHERE pokemon_id = :id
            ");
            $stmt->execute(['id' => $id]);
            $images = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Procesar el resultado
            $result = [
                'id' => $pokemon['id'],
                'name' => $pokemon['name'],
                'pokemon_id' => $pokemon['pokemon_id'],
                'region' => $pokemon['region'],
                'images' => [],
                'moves' => [
                    'fast' => [],
                    'main' => []
                ],
                'raid' => null,
                'forms' => []
            ];

            // Procesar imágenes
            if ($images) {
                foreach ($images as $image) {
                    $result['images'][] = ['image_path' => $image['image_path']];
                }
            }

            // Procesar movimientos
            $stmt = $this->pdo->prepare("
                SELECT m.*, p.name as pokemon_name 
                FROM moves m 
                LEFT JOIN pokemons p ON m.pokemon_id = p.pokemon_id
                WHERE m.pokemon_id = :id
            ");
            $stmt->execute(['id' => $id]);
            $moves = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            if ($moves) {
                foreach ($moves as $move) {
                    $result['moves'][$move['move_type']][] = [
                        'name' => $move['move_name'],
                        'damage' => $move['damage'],
                        'eps' => $move['eps'],
                        'dps' => $move['dps']
                    ];
                }
            }

            // Procesar información de raid
            $stmt = $this->pdo->prepare("
                SELECT r.*, p.name as pokemon_name 
                FROM raids r 
                LEFT JOIN pokemons p ON r.pokemon_id = p.pokemon_id
                WHERE r.pokemon_id = :id
            ");
            $stmt->execute(['id' => $id]);
            $raid = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($raid) {
                $result['raid'] = [
                    'tier' => $raid['raid_tier'],
                    'boss_cp' => $raid['boss_cp'],
                    'boss_hp' => $raid['boss_hp'],
                    'suggested_players' => $raid['suggested_players'],
                    'caught_cp_range' => $raid['caught_cp_range'],
                    'caught_cp_boosted' => $raid['caught_cp_boosted'],
                    'minimum_ivs' => $raid['minimum_ivs']
                ];
            }

            // Procesar formas
            $stmt = $this->pdo->prepare("
                SELECT pf.*, p.name as base_pokemon_name 
                FROM pokemon_forms pf 
                LEFT JOIN pokemons p ON pf.pokemon_id = p.pokemon_id
                WHERE pf.pokemon_id = :id
            ");
            $stmt->execute(['id' => $id]);
            $forms = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            if ($forms) {
                foreach ($forms as $form) {
                    $result['forms'][] = [
                        'pokemon_name' => $form['pokemon_name'],
                        'form_name' => $form['form_name']
                    ];
                }
            }

            return $result;
        } catch (\PDOException $e) {
            error_log("Error en getPokemonById: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene todos los movimientos de la base de datos
     * 
     * @return array Lista de todos los movimientos con traducción
     */
    public function getAllMoves() {
        try {
            $stmt = $this->pdo->query("
                SELECT m.*, p.name as pokemon_name 
                FROM moves m 
                LEFT JOIN pokemons p ON m.pokemon_id = p.pokemon_id
                ORDER BY m.pokemon_id, m.move_type
            ");
            $moves = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Traducir los nombres de los movimientos
            foreach ($moves as &$move) {
                // Traducir el nombre del movimiento
                $move['move_name'] = dgettext('messages', trim($move['move_name']));
                
                // Traducir el tipo de movimiento
                $move['move_type'] = dgettext('messages', trim($move['move_type']));
            }
            
            return $moves;
        } catch (\PDOException $e) {
            error_log("Error en getAllMoves: " . $e->getMessage());
            throw $e;
        }
    }

    public function getAllRaids() {
        try {
            $stmt = $this->pdo->query("
                SELECT r.*, p.name as pokemon_name 
                FROM raids r 
                LEFT JOIN pokemons p ON r.pokemon_id = p.pokemon_id
                ORDER BY r.raid_tier DESC, p.name
            ");
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error en getAllRaids: " . $e->getMessage());
            throw $e;
        }
    }

    public function getAllForms() {
        try {
            $stmt = $this->pdo->query("
                SELECT pf.*, p.name as base_pokemon_name 
                FROM pokemon_forms pf 
                LEFT JOIN pokemons p ON pf.pokemon_id = p.pokemon_id
                ORDER BY p.name, pf.form_name
            ");
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error en getAllForms: " . $e->getMessage());
            throw $e;
        }
    }

    public function getAllImages() {
        try {
            $stmt = $this->pdo->query("
                SELECT pi.*, p.name as pokemon_name 
                FROM pokemon_images pi 
                LEFT JOIN pokemons p ON pi.pokemon_id = p.pokemon_id
                ORDER BY p.name
            ");
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error en getAllImages: " . $e->getMessage());
            throw $e;
        }
    }

    public function getMoveById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT m.*, p.name as pokemon_name 
                FROM moves m 
                LEFT JOIN pokemons p ON m.pokemon_id = p.pokemon_id
                WHERE m.id = :id
            ");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error en getMoveById: " . $e->getMessage());
            throw $e;
        }
    }

    public function addMove($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO moves (pokemon_id, move_type, move_name, damage, eps, dps)
                VALUES (:pokemon_id, :move_type, :move_name, :damage, :eps, :dps)
            ");
            
            $result = $stmt->execute([
                'pokemon_id' => $data['pokemon_id'],
                'move_type' => $data['move_type'],
                'move_name' => $data['move_name'],
                'damage' => $data['damage'],
                'eps' => $data['eps'],
                'dps' => $data['dps']
            ]);

            if (!$result) {
                throw new \Exception('Error al crear el movimiento');
            }

            return true;
        } catch (\PDOException $e) {
            error_log("Error en addMove: " . $e->getMessage());
            throw new \Exception('Error al crear el movimiento: ' . $e->getMessage());
        }
    }

    public function updateMove($id, $data) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE moves 
                SET pokemon_id = :pokemon_id,
                    move_type = :move_type,
                    move_name = :move_name,
                    damage = :damage,
                    eps = :eps,
                    dps = :dps
                WHERE id = :id
            ");
            
            $result = $stmt->execute([
                'id' => $id,
                'pokemon_id' => $data['pokemon_id'],
                'move_type' => $data['move_type'],
                'move_name' => $data['move_name'],
                'damage' => $data['damage'],
                'eps' => $data['eps'],
                'dps' => $data['dps']
            ]);

            if (!$result) {
                throw new \Exception('Error al actualizar el movimiento');
            }

            return true;
        } catch (\PDOException $e) {
            error_log("Error en updateMove: " . $e->getMessage());
            throw new \Exception('Error al actualizar el movimiento: ' . $e->getMessage());
        }
    }

    public function deleteMove($id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM moves WHERE id = :id");
            $result = $stmt->execute(['id' => $id]);

            if (!$result) {
                throw new \Exception('Error al eliminar el movimiento');
            }

            return true;
        } catch (\PDOException $e) {
            error_log("Error en deleteMove: " . $e->getMessage());
            throw new \Exception('Error al eliminar el movimiento: ' . $e->getMessage());
        }
    }

    public function addPokemon($data) {
        try {
            error_log("Añadiendo nuevo Pokémon");
            error_log("Datos recibidos: " . print_r($data, true));

            // Verificar si ya existe un Pokémon con ese ID
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM pokemons WHERE pokemon_id = :pokemon_id");
            $stmt->execute(['pokemon_id' => $data['pokemon_id']]);
            if ($stmt->fetchColumn() > 0) {
                throw new \Exception('Ya existe un Pokémon con ese ID');
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO pokemons (pokemon_id, name, region)
                VALUES (:pokemon_id, :name, :region)
            ");
            
            $params = [
                'pokemon_id' => $data['pokemon_id'],
                'name' => $data['name'],
                'region' => $data['region']
            ];
            
            error_log("Ejecutando query con parámetros: " . print_r($params, true));
            
            $result = $stmt->execute($params);

            if (!$result) {
                error_log("Error en la ejecución del query: " . print_r($stmt->errorInfo(), true));
                throw new \Exception('Error al crear el Pokémon');
            }

            error_log("Pokémon creado correctamente");
            return true;
        } catch (\PDOException $e) {
            error_log("Error en addPokemon: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw new \Exception('Error al crear el Pokémon: ' . $e->getMessage());
        }
    }

    public function updatePokemon($id, $data) {
        try {
            error_log("Actualizando Pokémon con ID: " . $id);
            error_log("Datos recibidos: " . print_r($data, true));

            $stmt = $this->pdo->prepare("
                UPDATE pokemons 
                SET name = :name,
                    region = :region
                WHERE pokemon_id = :id
            ");
            
            $params = [
                'id' => $id,
                'name' => $data['name'],
                'region' => $data['region']
            ];
            
            error_log("Ejecutando query con parámetros: " . print_r($params, true));
            
            $result = $stmt->execute($params);

            if (!$result) {
                error_log("Error en la ejecución del query: " . print_r($stmt->errorInfo(), true));
                throw new \Exception('Error al actualizar el Pokémon');
            }

            error_log("Pokémon actualizado correctamente");
            return true;
        } catch (\PDOException $e) {
            error_log("Error en updatePokemon: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw new \Exception('Error al actualizar el Pokémon: ' . $e->getMessage());
        }
    }

    public function deletePokemon($id) {
        try {
            // Primero eliminamos registros relacionados
            $this->pdo->beginTransaction();
            
            // Eliminar movimientos asociados
            $stmt = $this->pdo->prepare("DELETE FROM moves WHERE pokemon_id = :id");
            $stmt->execute(['id' => $id]);
            
            // Eliminar raids asociadas
            $stmt = $this->pdo->prepare("DELETE FROM raids WHERE pokemon_id = :id");
            $stmt->execute(['id' => $id]);
            
            // Eliminar formas asociadas
            $stmt = $this->pdo->prepare("DELETE FROM pokemon_forms WHERE pokemon_id = :id");
            $stmt->execute(['id' => $id]);
            
            // Eliminar imágenes asociadas
            $stmt = $this->pdo->prepare("DELETE FROM pokemon_images WHERE pokemon_id = :id");
            $stmt->execute(['id' => $id]);
            
            // Finalmente eliminar el Pokémon
            $stmt = $this->pdo->prepare("DELETE FROM pokemons WHERE pokemon_id = :id");
            $result = $stmt->execute(['id' => $id]);
            
            $this->pdo->commit();
            return true;
            
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            error_log("Error en deletePokemon: " . $e->getMessage());
            throw new \Exception('Error al eliminar el Pokémon');
        }
    }

    public function getAllPokemonsAdmin() {
        try {
            $stmt = $this->pdo->query("
                SELECT p.* 
                FROM pokemons p 
                ORDER BY CAST(p.pokemon_id AS UNSIGNED)
            ");
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error en getAllPokemonsAdmin: " . $e->getMessage());
            throw $e;
        }
    }

    public function getRaidById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT r.*, p.name as pokemon_name 
                FROM raids r 
                LEFT JOIN pokemons p ON r.pokemon_id = p.pokemon_id
                WHERE r.id = :id
            ");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error en getRaidById: " . $e->getMessage());
            throw $e;
        }
    }

    public function addRaid($data) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO raids (
                    pokemon_id, raid_tier, boss_cp, boss_hp, 
                    suggested_players, caught_cp_range, caught_cp_boosted, minimum_ivs
                ) VALUES (
                    :pokemon_id, :raid_tier, :boss_cp, :boss_hp,
                    :suggested_players, :caught_cp_range, :caught_cp_boosted, :minimum_ivs
                )
            ");
            
            $result = $stmt->execute([
                'pokemon_id' => $data['pokemon_id'],
                'raid_tier' => $data['raid_tier'],
                'boss_cp' => $data['boss_cp'],
                'boss_hp' => $data['boss_hp'],
                'suggested_players' => $data['suggested_players'],
                'caught_cp_range' => $data['caught_cp_range'],
                'caught_cp_boosted' => $data['caught_cp_boosted'],
                'minimum_ivs' => $data['minimum_ivs']
            ]);

            if (!$result) {
                throw new \Exception('Error al crear la raid');
            }

            return true;
        } catch (\PDOException $e) {
            error_log("Error en addRaid: " . $e->getMessage());
            throw new \Exception('Error al crear la raid: ' . $e->getMessage());
        }
    }

    public function updateRaid($id, $data) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE raids 
                SET pokemon_id = :pokemon_id,
                    raid_tier = :raid_tier,
                    boss_cp = :boss_cp,
                    boss_hp = :boss_hp,
                    suggested_players = :suggested_players,
                    caught_cp_range = :caught_cp_range,
                    caught_cp_boosted = :caught_cp_boosted,
                    minimum_ivs = :minimum_ivs
                WHERE id = :id
            ");
            
            $result = $stmt->execute([
                'id' => $id,
                'pokemon_id' => $data['pokemon_id'],
                'raid_tier' => $data['raid_tier'],
                'boss_cp' => $data['boss_cp'],
                'boss_hp' => $data['boss_hp'],
                'suggested_players' => $data['suggested_players'],
                'caught_cp_range' => $data['caught_cp_range'],
                'caught_cp_boosted' => $data['caught_cp_boosted'],
                'minimum_ivs' => $data['minimum_ivs']
            ]);

            if (!$result) {
                throw new \Exception('Error al actualizar la raid');
            }

            return true;
        } catch (\PDOException $e) {
            error_log("Error en updateRaid: " . $e->getMessage());
            throw new \Exception('Error al actualizar la raid: ' . $e->getMessage());
        }
    }

    public function deleteRaid($id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM raids WHERE id = :id");
            $result = $stmt->execute(['id' => $id]);

            if (!$result) {
                throw new \Exception('Error al eliminar la raid');
            }

            return true;
        } catch (\PDOException $e) {
            error_log("Error en deleteRaid: " . $e->getMessage());
            throw new \Exception('Error al eliminar la raid: ' . $e->getMessage());
        }
    }

    public function getFormById($id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT f.*, p.name as base_pokemon_name 
                FROM pokemon_forms f 
                LEFT JOIN pokemons p ON f.pokemon_id = p.pokemon_id
                WHERE f.id = :id
            ");
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("Error en getFormById: " . $e->getMessage());
            throw $e;
        }
    }

    public function addForm($data) {
        try {
            // Primero obtenemos el nombre del Pokémon
            $stmtPokemon = $this->pdo->prepare("
                SELECT name FROM pokemons WHERE pokemon_id = :pokemon_id
            ");
            $stmtPokemon->execute(['pokemon_id' => $data['pokemon_id']]);
            $pokemon = $stmtPokemon->fetch(\PDO::FETCH_ASSOC);

            if (!$pokemon) {
                throw new \Exception('Pokémon no encontrado');
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO pokemon_forms (
                    pokemon_id, pokemon_name, form_name
                ) VALUES (
                    :pokemon_id, :pokemon_name, :form_name
                )
            ");
            
            $result = $stmt->execute([
                'pokemon_id' => $data['pokemon_id'],
                'pokemon_name' => $pokemon['name'],
                'form_name' => $data['form_name']
            ]);

            if (!$result) {
                throw new \Exception('Error al crear la forma');
            }

            return true;
        } catch (\PDOException $e) {
            error_log("Error en addForm: " . $e->getMessage());
            throw new \Exception('Error al crear la forma: ' . $e->getMessage());
        }
    }

    public function updateForm($id, $data) {
        try {
            // Primero obtenemos el nombre del Pokémon
            $stmtPokemon = $this->pdo->prepare("
                SELECT name FROM pokemons WHERE pokemon_id = :pokemon_id
            ");
            $stmtPokemon->execute(['pokemon_id' => $data['pokemon_id']]);
            $pokemon = $stmtPokemon->fetch(\PDO::FETCH_ASSOC);

            if (!$pokemon) {
                throw new \Exception('Pokémon no encontrado');
            }

            $stmt = $this->pdo->prepare("
                UPDATE pokemon_forms 
                SET pokemon_id = :pokemon_id,
                    pokemon_name = :pokemon_name,
                    form_name = :form_name
                WHERE id = :id
            ");
            
            $result = $stmt->execute([
                'id' => $id,
                'pokemon_id' => $data['pokemon_id'],
                'pokemon_name' => $pokemon['name'],
                'form_name' => $data['form_name']
            ]);

            if (!$result) {
                throw new \Exception('Error al actualizar la forma');
            }

            return true;
        } catch (\PDOException $e) {
            error_log("Error en updateForm: " . $e->getMessage());
            throw new \Exception('Error al actualizar la forma: ' . $e->getMessage());
        }
    }

    public function deleteForm($id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM pokemon_forms WHERE id = :id");
            $result = $stmt->execute(['id' => $id]);

            if (!$result) {
                throw new \Exception('Error al eliminar la forma');
            }

            return true;
        } catch (\PDOException $e) {
            error_log("Error en deleteForm: " . $e->getMessage());
            throw new \Exception('Error al eliminar la forma: ' . $e->getMessage());
        }
    }

    public function getCount()
    {
        $sql = "SELECT COUNT(*) as count FROM pokemons";
        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetch();
        return $result['count'];
    }
}
?>
