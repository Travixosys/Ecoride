<?php

namespace App\Models;

use PDO;

/**
 * Carpool model
 * FR : Encapsule la logique liée à la table `carpools`
 * EN : Encapsulates logic related to the `carpools` table
 */
class Carpool
{
    /** @var PDO Connexion PDO • PDO connection */
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /*--------------------------------------------------------------------
      decrementOccupiedSeats()
      FR : Libère des sièges puis vérifie si le covoiturage peut passer
           à « completed ».
      EN : Frees seats and then checks whether the carpool can move to
           “completed” status.
    --------------------------------------------------------------------*/
    public function decrementOccupiedSeats(int $carpoolId, int $seatsToFree): void
    {
        $this->db->prepare("
            UPDATE carpools
               SET occupied_seats = GREATEST(0, occupied_seats - :seats),
                   updated_at     = NOW()
             WHERE id = :id
        ")->execute(['seats' => $seatsToFree, 'id' => $carpoolId]);

        // Vérifie ensuite si le trajet doit être marqué « completed »
        $this->checkAndMarkAsCompleted($carpoolId);
    }

    /*--------------------------------------------------------------------
      checkAndMarkAsCompleted()
      FR : Marque le covoiturage terminé s’il n’y a plus de sièges occupés
           et plus de trajets actifs.
      EN : Marks carpool completed if no occupied seats and no active rides
    --------------------------------------------------------------------*/
    private function checkAndMarkAsCompleted(int $carpoolId): void
    {
        // Récupère driver_id & occupied_seats
        $driverStmt = $this->db->prepare("
            SELECT driver_id, occupied_seats
            FROM carpools
            WHERE id = :id
        ");
        $driverStmt->execute(['id' => $carpoolId]);
        $carpoolData = $driverStmt->fetch(PDO::FETCH_ASSOC);
        if (!$carpoolData) return;

        $driverId      = $carpoolData['driver_id'];
        $occupiedSeats = (int)$carpoolData['occupied_seats'];

        // Compte rides encore actifs (≠ completed / cancelled)
        $checkStmt = $this->db->prepare("
            SELECT COUNT(*) AS count
            FROM rides
            WHERE driver_id = :driver_id
              AND status NOT IN ('completed','cancelled')
        ");
        $checkStmt->execute(['driver_id' => $driverId]);
        $remaining = $checkStmt->fetch(PDO::FETCH_ASSOC);

        // Si plus de rides actifs et 0 seat occupé → status completed
        if ((int)$remaining['count'] === 0 && $occupiedSeats === 0) {
            $this->db->prepare("
                UPDATE carpools
                   SET status = 'completed',
                       updated_at = NOW()
                 WHERE id = :id
            ")->execute(['id' => $carpoolId]);
        }
    }

    /*--------------------------------------------------------------------
      markAsCompletedIfEligible()
      FR : API publique – délègue à checkAndMarkAsCompleted
      EN : Public wrapper – delegates to checkAndMarkAsCompleted
    --------------------------------------------------------------------*/
    public function markAsCompletedIfEligible(int $carpoolId, int $driverId): void
    {
        // La logique est centralisée dans checkAndMarkAsCompleted()
        $this->checkAndMarkAsCompleted($carpoolId);
    }

    /*--------------------------------------------------------------------
      getActiveCarpoolId()
      FR : Renvoie l’ID du covoiturage ‘upcoming’ du conducteur
      EN : Return driver’s ‘upcoming’ carpool ID
    --------------------------------------------------------------------*/
    public function getActiveCarpoolId(int $driverId): ?int
    {
        $stmt = $this->db->prepare("
            SELECT id
            FROM carpools
            WHERE driver_id = :driver_id
              AND status = 'upcoming'
            LIMIT 1
        ");
        $stmt->execute(['driver_id' => $driverId]);
        $carpool = $stmt->fetch(PDO::FETCH_ASSOC);
        return $carpool ? (int)$carpool['id'] : null;
    }

    /*--------------------------------------------------------------------
      updateCarpoolStatusByRide()
      FR : Appelé lorsqu’un ride change de statut ; met à jour seats et
           potentiellement le statut du covoiturage.
      EN : Called when a ride’s status changes; updates seats and possibly
           carpool status.
    --------------------------------------------------------------------*/
    public function updateCarpoolStatusByRide(int $rideId): void
    {
        // Passenger_count + driver_id via jointure ride / ride_requests
        $stmt = $this->db->prepare("
            SELECT r.driver_id, rr.passenger_count
            FROM rides r
            JOIN ride_requests rr ON r.passenger_id = rr.passenger_id
            WHERE r.id = :ride_id
        ");
        $stmt->execute(['ride_id' => $rideId]);
        $rideInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$rideInfo) return;

        $carpoolId = $this->getActiveCarpoolId($rideInfo['driver_id']);
        if ($carpoolId !== null) {
            $this->decrementOccupiedSeats($carpoolId, (int)$rideInfo['passenger_count']);
            // checkAndMarkAsCompleted appelé dans decrementOccupiedSeats
        }
    }

    /*--------------------------------------------------------------------
      decrementSeatsByRide()
      FR : Libère des sièges suite à l’annulation d’un ride.
      EN : Free seats when a ride is cancelled.
    --------------------------------------------------------------------*/
    public function decrementSeatsByRide(int $rideId): void
    {
        $stmt = $this->db->prepare("
            SELECT r.driver_id, rr.passenger_count
            FROM rides r
            JOIN ride_requests rr ON r.passenger_id = rr.passenger_id
            WHERE r.id = :ride_id
        ");
        $stmt->execute(['ride_id' => $rideId]);
        $info = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$info) return;

        $carpoolId = $this->getActiveCarpoolId($info['driver_id']);
        if ($carpoolId !== null) {
            $this->decrementOccupiedSeats($carpoolId, (int)$info['passenger_count']);
        }
    }
}
