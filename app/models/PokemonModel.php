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
            error_log("\n=== CONSULTA getPokemonById ===");
            error_log("Buscando Pokémon con ID: " . $id);

            // Primero, solo obtenemos los datos básicos del Pokémon
            $stmt = $this->pdo->prepare("
                SELECT p.*, 
                       (SELECT pi.image_path 
                        FROM pokemon_images pi 
                        WHERE pi.pokemon_id = p.pokemon_id 
                        LIMIT 1) as image_path
                FROM pokemons p
                WHERE p.pokemon_id = :id
            ");
            
            $stmt->bindValue(':id', (string)$id, \PDO::PARAM_STR);
            $stmt->execute();
            
            $pokemon = $stmt->fetch(\PDO::FETCH_ASSOC);
            error_log("Resultado de la consulta básica: " . print_r($pokemon, true));
            
            if (!$pokemon) {
                error_log("No se encontró el Pokémon con ID: " . $id);
                return null;
            }

            // Procesamos el resultado similar a getAllPokemons()
            $result = [
                'id' => $pokemon['id'],
                'name' => $pokemon['name'],
                'pokemon_id' => $pokemon['pokemon_id'],
                'region' => $pokemon['region'],
                'images' => $pokemon['image_path'] ? [
                    ['image_path' => $pokemon['image_path']]
                ] : []
            ];

            error_log("Datos procesados: " . print_r($result, true));
            return $result;
        } catch (\PDOException $e) {
            error_log("Error en getPokemonById: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            throw $e;
        }
    }
}
?>
