<?php

namespace App\Models;

use PDO;

class Vehicle
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Créer un véhicule pour un conducteur
     * Create a vehicle for a driver
     */
    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO vehicles (driver_id, make, model, year, plate, seats, energy_type) 
            VALUES (:driver_id, :make, :model, :year, :plate, :seats, :energy_type)
        ");
        return $stmt->execute([
            'driver_id'   => $data['driver_id'],
            'make'        => $data['make'],
            'model'       => $data['model'],
            'year'        => $data['year'],
            'plate'       => $data['plate'],
            'seats'       => $data['seats'],
            'energy_type' => $data['energy_type']
        ]);
    }

    /**
     * Trouver un véhicule par l'identifiant du conducteur
     * Find a vehicle by driver ID
     */
    public function findByDriverId(int $driverId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM vehicles WHERE driver_id = :driver_id");
        $stmt->execute(['driver_id' => $driverId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Supprimer un véhicule par son ID
     * Delete a vehicle by ID
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM vehicles WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Mettre à jour les informations d'un véhicule
     * Update vehicle information
     */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE vehicles 
            SET make = :make, model = :model, year = :year, plate = :plate, seats = :seats, energy_type = :energy_type 
            WHERE id = :id
        ");
        return $stmt->execute([
            'make'        => $data['make'],
            'model'       => $data['model'],
            'year'        => $data['year'],
            'plate'       => $data['plate'],
            'seats'       => $data['seats'],
            'energy_type' => $data['energy_type'],
            'id'          => $id
        ]);
    }
}
