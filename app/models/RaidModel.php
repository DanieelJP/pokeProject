<?php
namespace App\Models;

class RaidModel
{
    private $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function getCount()
    {
        $sql = "SELECT COUNT(*) as count FROM raids";
        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetch();
        return $result['count'];
    }

    public function getAllRaids()
    {
        $sql = "SELECT r.*, p.name as pokemon_name 
                FROM raids r 
                LEFT JOIN pokemons p ON r.pokemon_id = p.pokemon_id
                ORDER BY r.raid_tier DESC, p.name";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
} 