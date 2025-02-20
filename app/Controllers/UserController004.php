

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use PDO;
use Firebase\JWT\JWT;

class UserController {
    private $db;
    private $secretKey = "your_secret_key"; // Change this!

    public function __construct(ContainerInterface $container) {
        $this->db = $container->get('db');
    }

    // ✅ Register New User (Passenger OR Driver)
    public function register(Request $request, Response $response): Response {
        $data = json_decode($request->getBody()->getContents(), true);

        if (!isset($data['email'], $data['password'], $data['name'], $data['role'])) {
            return $this->jsonResponse($response, ['error' => 'Missing required fields'], 400);
        }

        // ✅ Keep role handling **as it was working before**
        $role = ($data['role'] === 'passenger') ? 'user' : $data['role'];

        // ✅ Hash Password
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);

        try {
            // ✅ Insert into users table
            $stmt = $this->db->prepare("INSERT INTO users (name, email, password, role, phone_number) 
                                        VALUES (:name, :email, :password, :role, :phone_number)");
            $stmt->execute([
                'name' => htmlspecialchars($data['name']),
                'email' => filter_var($data['email'], FILTER_SANITIZE_EMAIL),
                'password' => $hashedPassword,
                'role' => $role,
                'phone_number' => $data['phone_number'] ?? null
            ]);

            $userId = $this->db->lastInsertId();

            // ✅ If driver, add vehicle details
            if ($role === 'driver') {
                if (!isset($data['make'], $data['model'], $data['year'], $data['plate'], $data['seats'])) {
                    return $this->jsonResponse($response, ['error' => 'Missing vehicle details for driver'], 400);
                }

                $stmt = $this->db->prepare("INSERT INTO vehicles (driver_id, make, model, year, plate, seats) 
                                            VALUES (:driver_id, :make, :model, :year, :plate, :seats)");
                $stmt->execute([
                    'driver_id' => $userId,
                    'make' => htmlspecialchars($data['make']),
                    'model' => htmlspecialchars($data['model']),
                    'year' => (int)$data['year'],
                    'plate' => htmlspecialchars($data['plate']),
                    'seats' => (int)$data['seats']
                ]);
            }

            return $this->jsonResponse($response, ['message' => 'User registered successfully'], 201);
        } catch (\PDOException $e) {
            return $this->jsonResponse($response, ['error' => 'Database error: ' . $e->getMessage()], 500);
        }
    }

    // ✅ Login User
    public function login(Request $request, Response $response): Response {
        $data = json_decode($request->getBody()->getContents(), true) ?? [];

        if (!isset($data['email'], $data['password'])) {
            return $this->jsonResponse($response, ['error' => 'Missing email or password'], 400);
        }

        // Fetch user
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $data['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($data['password'], $user['password'])) {
            return $this->jsonResponse($response, ['error' => 'Invalid credentials'], 401);
        }

        // ✅ Generate JWT Token
        $tokenPayload = [
            "user_id" => $user['id'],
            "email" => $user['email'],
            "role" => $user['role'],
            "exp" => time() + (60 * 60) // Expires in 1 hour
        ];
        $token = JWT::encode($tokenPayload, $this->secretKey, 'HS256');

        return $this->jsonResponse($response, [
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'role' => $user['role']
            ]
        ], 200);
    }

    // ✅ Utility function for JSON response
    private function jsonResponse(Response $response, array $data, int $statusCode = 200): Response {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
    }
}
