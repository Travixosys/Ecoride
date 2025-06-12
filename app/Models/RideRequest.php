<?php

namespace App\Models;

use PDO;

class RideRequest
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Store a new ride request (passenger)
     * Enregistrer une demande de trajet (passager)
     */
    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("INSERT INTO ride_requests (passenger_id, pickup_location, dropoff_location, passenger_count, status) 
                                     VALUES (:passenger_id, :pickup, :dropoff, :passenger_count, 'pending')");

        return $stmt->execute([
            'passenger_id' => $data['passenger_id'],
            'pickup' => $data['pickup_location'],
            'dropoff' => $data['dropoff_location'],
            'passenger_count' => $data['passenger_count']
        ]);
    }

    /**
     * Get all pending ride requests
     * Obtenir toutes les demandes en attente
     */
    public function getPending(): array
    {
        $stmt = $this->db->query("SELECT * FROM ride_requests WHERE status = 'pending' ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update the status of a ride request
     * Mettre à jour le statut d'une demande
     */
    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE ride_requests SET status = :status WHERE id = :id");
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }
} 
