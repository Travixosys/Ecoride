<?php

namespace App\Models;

use PDO;

class User
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Find a user by email
     * Trouver un utilisateur par e-mail
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Create a new user with default 20 credits
     * Créer un nouvel utilisateur avec 20 crédits par défaut
     */
    public function createUser(array $data): bool
{
    $stmt = $this->db->prepare("INSERT INTO users (name, email, password, role, phone_number, credits) 
                                 VALUES (:name, :email, :password, :role, :phone_number, :credits)");
    return $stmt->execute([
        'name' => $data['name'],
        'email' => $data['email'],
        'password' => $data['password'],
        'role' => $data['role'],
        'phone_number' => $data['phone_number'] ?? null,
        'credits' => 20 
    ]);
}

    /**
     * Update user profile (email, name, password...)
     * Mettre à jour le profil utilisateur (email, name, mot de passe...)
     */
    public function updateProfile(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET email = :email, name = :name, password = :password WHERE id = :id");
        return $stmt->execute([
            'email' => $data['email'],
            'name' => $data['name'],
            'password' => $data['password'],
            'id' => $id
        ]);
    }

    /**
     * Get all users (for admin panel)
     * Récupérer tous les utilisateurs (pour le panneau admin)
     */
    public function getAllUsers(): array
    {
        $stmt = $this->db->query("SELECT * FROM users ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Delete a user by ID
     * Supprimer un utilisateur par ID
     */
    public function deleteUser(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
