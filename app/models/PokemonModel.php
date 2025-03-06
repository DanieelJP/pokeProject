<?php
namespace App\Models;

class PokemonModel {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function getAllPokemons() {
        // Modificamos la consulta para obtener solo una imagen por Pokémon
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
        
        // Debug: ver qué rutas de imágenes estamos obteniendo
        error_log("Rutas de imágenes de la BD: " . print_r($pokemons, true));
        
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
}
?>
