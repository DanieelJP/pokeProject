<?php
namespace App\Models;

class PokemonModel {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function getAllPokemons() {
        try {
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
            
            // Procesamos los resultados
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
            error_log("Error en getAllPokemons: " . $e->getMessage());
            throw $e;
        }
    }

    public function getPokemonById($id) {
        try {
            // Consulta principal para obtener datos básicos e imágenes
            $stmt = $this->pdo->prepare("
                SELECT p.*, 
                       GROUP_CONCAT(DISTINCT pi.image_path) as image_paths,
                       GROUP_CONCAT(DISTINCT CONCAT(m.move_type, ':', m.move_name, ':', COALESCE(m.damage, '0'), ':', COALESCE(m.eps, '0'), ':', COALESCE(m.dps, '0'))) as moves,
                       r.raid_tier, r.boss_cp, r.boss_hp, r.suggested_players, r.caught_cp_range, r.caught_cp_boosted, r.minimum_ivs,
                       GROUP_CONCAT(DISTINCT CONCAT(pf.pokemon_name, ':', pf.form_name)) as forms
                FROM pokemons p
                LEFT JOIN pokemon_images pi ON p.pokemon_id = pi.pokemon_id
                LEFT JOIN moves m ON p.pokemon_id = m.pokemon_id
                LEFT JOIN raids r ON p.pokemon_id = r.pokemon_id
                LEFT JOIN pokemon_forms pf ON p.pokemon_id = pf.pokemon_id
                WHERE p.pokemon_id = :id
                GROUP BY p.id, r.id
            ");
            
            $stmt->bindValue(':id', (string)$id, \PDO::PARAM_STR);
            $stmt->execute();
            
            $pokemon = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$pokemon) {
                return null;
            }

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
            if ($pokemon['image_paths']) {
                foreach (explode(',', $pokemon['image_paths']) as $path) {
                    $result['images'][] = ['image_path' => trim($path)];
                }
            }

            // Procesar movimientos
            if ($pokemon['moves']) {
                foreach (explode(',', $pokemon['moves']) as $move) {
                    list($type, $name, $damage, $eps, $dps) = explode(':', $move);
                    $result['moves'][$type][] = [
                        'name' => $name,
                        'damage' => $damage,
                        'eps' => $eps,
                        'dps' => $dps
                    ];
                }
            }

            // Procesar información de raid
            if ($pokemon['raid_tier']) {
                $result['raid'] = [
                    'tier' => $pokemon['raid_tier'],
                    'boss_cp' => $pokemon['boss_cp'],
                    'boss_hp' => $pokemon['boss_hp'],
                    'suggested_players' => $pokemon['suggested_players'],
                    'caught_cp_range' => $pokemon['caught_cp_range'],
                    'caught_cp_boosted' => $pokemon['caught_cp_boosted'],
                    'minimum_ivs' => $pokemon['minimum_ivs']
                ];
            }

            // Procesar formas
            if ($pokemon['forms']) {
                foreach (explode(',', $pokemon['forms']) as $form) {
                    list($pokemonName, $formName) = explode(':', $form);
                    $result['forms'][] = [
                        'pokemon_name' => $pokemonName,
                        'form_name' => $formName
                    ];
                }
            }

            return $result;
        } catch (\PDOException $e) {
            error_log("Error en getPokemonById: " . $e->getMessage());
            throw $e;
        }
    }

    public function getAllMoves() {
        try {
            $stmt = $this->pdo->query("
                SELECT m.*, p.name as pokemon_name 
                FROM moves m 
                LEFT JOIN pokemons p ON m.pokemon_id = p.pokemon_id
                ORDER BY m.pokemon_id, m.move_type
            ");
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
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
