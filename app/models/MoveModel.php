<?php
namespace App\Models;

class MoveModel
{
    private $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function getCount()
    {
        $sql = "SELECT COUNT(*) as count FROM moves";
        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetch();
        return $result['count'];
    }

    public function getAllMoves()
    {
        $sql = "SELECT m.*, p.name as pokemon_name 
                FROM moves m 
                LEFT JOIN pokemons p ON m.pokemon_id = p.pokemon_id
                ORDER BY m.pokemon_id, m.move_type";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
} 