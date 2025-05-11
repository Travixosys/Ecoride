<?php

namespace App\Models;

use PDO;

class Ride
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Save a new ride request
     * Enregistrer une nouvelle demande de trajet
     */
    public function create(array $data): bool
    {
        $stmt = $this->db->prepare("INSERT INTO rides (passenger_id, pickup_location, dropoff_location) 
                                     VALUES (:passenger_id, :pickup_location, :dropoff_location)");
        return $stmt->execute([
            'passenger_id' => $data['passenger_id'],
            'pickup_location' => $data['pickup_location'],
            'dropoff_location' => $data['dropoff_location']
        ]);
    }

    /**
     * Assign a driver and vehicle to a ride
     * Assigner un conducteur et un véhicule à un trajet
     */
    public function accept(int $rideId, int $driverId, int $vehicleId): bool
    {
        $stmt = $this->db->prepare("UPDATE rides SET driver_id = :driver_id, vehicle_id = :vehicle_id, status = 'accepted' 
                                     WHERE id = :id");
        return $stmt->execute([
            'driver_id' => $driverId,
            'vehicle_id' => $vehicleId,
            'id' => $rideId
        ]);
    }

    /**
     * Update ride status (completed, cancelled, etc.)
     * Mettre à jour le statut d'un trajet
     */
    public function updateStatus(int $rideId, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE rides SET status = :status WHERE id = :id");
        return $stmt->execute([
            'status' => $status,
            'id' => $rideId
        ]);
    }

    /**
     * Get rides by passenger ID
     * Récupérer les trajets par identifiant passager
     */
    public function findByPassengerId(int $passengerId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM rides WHERE passenger_id = :passenger_id ORDER BY created_at DESC");
        $stmt->execute(['passenger_id' => $passengerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get available rides (pending status)
     * Récupérer les trajets disponibles (en attente)
     */
    public function getAvailable(): array
    {
        $stmt = $this->db->query("SELECT * FROM rides WHERE status = 'pending' ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
