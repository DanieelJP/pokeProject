<?php
namespace App\Models;

class FormModel
{
    private $pdo;

    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function getCount()
    {
        $sql = "SELECT COUNT(*) as count FROM pokemon_forms";
        $stmt = $this->pdo->query($sql);
        $result = $stmt->fetch();
        return $result['count'];
    }

    public function getAllForms()
    {
        $sql = "SELECT f.*, p.name as base_pokemon_name 
                FROM pokemon_forms f 
                LEFT JOIN pokemons p ON f.pokemon_id = p.pokemon_id
                ORDER BY p.name, f.form_name";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
} 