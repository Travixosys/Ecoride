# EcoRide Code Issues Report

## Executive Summary

This report identifies **17 issues** across the EcoRide codebase, categorized by severity:
- **Critical**: 2 issues
- **High**: 4 issues
- **Medium**: 6 issues
- **Low**: 5 issues

---

## Critical Security Issues

### 1. CSRF Protection Disabled
**File**: `public/index.php:79-82`
**Severity**: Critical

The CSRF middleware is commented out, leaving the application vulnerable to Cross-Site Request Forgery attacks.

```php
// CSRF protection
// /** @var ResponseFactoryInterface $responseFactory */
// $responseFactory = $app->getResponseFactory();
// $csrf = new Guard($responseFactory);
// $app->add($csrf);
```

**Impact**: Attackers can trick authenticated users into performing unwanted actions (joining carpools, making payments, changing profile settings).

**Recommendation**: Enable CSRF protection immediately and add CSRF tokens to all forms.

---

### 2. TLS Certificate Verification Disabled
**File**: `app/db.php:43-45`
**Severity**: Critical

MongoDB connection bypasses TLS certificate verification:

```php
$driverOptions = [
    'tlsInsecure' => true,
];
```

**Impact**: Vulnerable to Man-in-the-Middle (MITM) attacks. Attackers can intercept database traffic and steal/modify user preferences data.

**Recommendation**: Remove `tlsInsecure` option and properly configure TLS certificates for MongoDB Atlas.

---

## High Security Issues

### 3. Debug Mode Enabled in Production
**Files**:
- `public/index.php:5-7`
- `public/index.php:87-91`
- `app/Controllers/UserController.php:103-104`

**Severity**: High

Debug settings expose sensitive information:

```php
// index.php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$errorMiddleware = $app->addErrorMiddleware(
    true,  // displayErrorDetails - EXPOSES STACK TRACES
    true,  // logErrors
    true   // logErrorDetails
);

// UserController.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

**Impact**: Stack traces, file paths, and internal application structure exposed to attackers.

**Recommendation**: Set `displayErrorDetails` to `false` and disable `display_errors` in production.

---

### 4. Missing Authorization Checks on Employee Routes
**File**: `app/Controllers/EmployeeController.php`
**Severity**: High

All employee controller methods lack role verification:

```php
// No session/role check in any method
public function index(Request $request, Response $response): Response
{
    // Missing: if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'employee')
    try {
        // ... fetches all disputed carpools and reviews
```

**Affected Methods**:
- `index()` - line 25
- `viewDispute()` - line 108
- `approveReview()` - line 148
- `rejectReview()` - line 162
- `resolve()` - line 176

**Impact**: Any user (or unauthenticated user) can access employee moderation panel, approve/reject reviews, and resolve disputes.

**Recommendation**: Add role-based authorization check at the beginning of each method.

---

### 5. Missing Authorization in Review/Ride Operations
**Files**:
- `app/Controllers/ReviewController.php:116-128` (approve method)
- `app/Controllers/ReviewController.php:165-177` (delete method)
- `app/Controllers/RideController.php:285-327` (completeRide method)

**Severity**: High

These methods perform sensitive operations without verifying user identity or role:

```php
// ReviewController - approve() has no auth check
public function approve(Request $request, Response $response, array $args): Response
{
    $reviewId = $args['id'] ?? null;
    // Anyone can approve any review!
    $stmt = $this->db->prepare("UPDATE ride_reviews SET status = 'approved' WHERE id = ?");
```

**Impact**: Unauthorized users can approve reviews, delete reviews, and mark rides as complete.

---

### 6. Sensitive Data Exposure in Error Responses
**File**: `app/Controllers/UserController.php`
**Severity**: High

Raw error messages and user data returned to clients:

```php
// Line 83 - Database error exposed
return $this->jsonResponse($response, ['error' => $e->getMessage()], 400);

// Line 131-132 - User input echoed back
return $this->jsonResponse($response, [
    'error' => 'Missing email or password',
    'received_data' => $data  // EXPOSES ALL REQUEST DATA
], 400);

// Line 145 - Email exposed
return $this->jsonResponse($response, [
    'error' => 'User not found',
    'email' => $data['email']  // CONFIRMS EMAIL EXISTS/DOESN'T EXIST
], 404);
```

**Impact**: Information disclosure aids attackers in reconnaissance (user enumeration, database structure).

**Recommendation**: Return generic error messages to clients; log details server-side only.

---

## Medium Issues

### 7. Session Fixation Vulnerability
**File**: `app/Controllers/UserController.php:171-182`
**Severity**: Medium

Session ID not regenerated after successful login:

```php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Missing: session_regenerate_id(true);
$_SESSION['user'] = [
    "id" => $user['id'],
    // ...
];
```

**Impact**: If attacker knows pre-login session ID, they can hijack the authenticated session.

**Recommendation**: Add `session_regenerate_id(true);` after successful authentication.

---

### 8. Race Condition in Seat Booking
**File**: `app/Controllers/CarpoolController.php:145-274`
**Severity**: Medium

The `joinCarpool()` method doesn't use database transactions:

```php
// Seat availability check (line 196-203)
$availableSeats = $carpool['total_seats'] - $carpool['occupied_seats'];
if ($requestedSeats > $availableSeats) { ... }

// ... other checks ...

// Seat booking (line 252-256) - NOT IN TRANSACTION
$this->db->prepare("UPDATE carpools SET occupied_seats = occupied_seats + ? WHERE id = ?")->execute([...]);
```

**Impact**: Two users could simultaneously book the last seat, resulting in overbooking.

**Recommendation**: Wrap the entire booking operation in a database transaction with row locking.

---

### 9. Missing Route Handlers
**File**: `app/routes.php:110-111`
**Severity**: Medium

Routes defined without corresponding controller methods:

```php
$group->delete('/delete-ride/{id}', [AdminController::class, 'deleteRide']);
$group->post('/user/{id}/suspend', AdminController::class . ':suspendUser');
```

**Impact**: These routes will cause 500 errors when accessed. The admin panel may have broken functionality.

**Recommendation**: Implement `deleteRide()` and `suspendUser()` methods in AdminController or remove unused routes.

---

### 10. Inconsistent Status Values
**Files**:
- `app/Controllers/RideController.php:353` uses `'cancelled'`
- Other files use `'canceled'`

**Severity**: Medium

```php
// RideController.php line 353
$this->db->prepare("UPDATE ride_requests SET status='cancelled' WHERE id=?")->execute([$rideId]);

// vs AdminController.php line 117
"AND status NOT IN ('completed', 'canceled')"
```

**Impact**: Cancelled rides may not be properly recognized by other parts of the application, causing data inconsistencies.

**Recommendation**: Standardize on one spelling throughout the codebase.

---

### 11. Incomplete Driver Self-Join Check
**File**: `app/Controllers/CarpoolController.php:165-171`
**Severity**: Medium

```php
if ($role === 'driver') {
    return $this->reloadCarpoolWithMessage(
        $response,
        $carpoolId,
        "Drivers cannot join carpools."
    );
}
```

**Problem**: This blocks ALL drivers from joining ANY carpool. Should only prevent a driver from joining their OWN carpool.

**Impact**: Drivers cannot book seats as passengers on other drivers' carpools.

---

### 12. Missing Input Validation
**File**: `app/Controllers/CarpoolController.php:434-455`
**Severity**: Medium

`storeCarpool()` doesn't validate required fields:

```php
public function storeCarpool(Request $request, Response $response): Response
{
    $data   = $request->getParsedBody();
    $userId = $_SESSION['user']['id'] ?? null;

    // No validation of $data fields!
    $stmt->execute([
        $userId,
        $data['vehicle_id'],      // Could be null/invalid
        $data['pickup_location'], // Could be empty
        $data['dropoff_location'],
        $data['departure_time'],
        $data['total_seats']
    ]);
```

**Impact**: Invalid/empty carpools can be created, causing database integrity issues.

---

## Low Issues

### 13. Unused/Dead Code
**Files**:
- `app/routes.php:20` - Twig created then immediately overwritten
- `app/Controllers/UserController.php:236-240` - `updateProfile` stub never used

```php
// routes.php
$twig = Twig::create(__DIR__ . '/../app/templates'); // Created
$twig = $container->get('view'); // Immediately overwritten

// UserController.php
public function updateProfile($request, $response)
{
    $data = $request->getParsedBody();
    return $response->withJson(['message' => 'Profile updated (stub)']);
}
```

**Impact**: Code clutter and confusion for maintainers.

---

### 14. Hardcoded Business Values
**File**: `app/Controllers/CarpoolController.php:179-180`
**Severity**: Low

```php
$costPerSeat    = 5;
$commissionPerSeat = 2;
```

**Impact**: Pricing changes require code modifications and redeployment.

**Recommendation**: Move to configuration file or database.

---

### 15. Inconsistent Response Handling
**Files**: Multiple controllers
**Severity**: Low

Mixed response types for similar scenarios:

```php
// CarpoolController - returns plain text
$response->getBody()->write("Carpool not found.");
return $response->withStatus(404);

// AdminController - returns JSON
return $this->jsonResponse($response, ['error' => 'Non autorisé / Unauthorized'], 403);
```

**Impact**: Inconsistent API behavior; frontend must handle multiple response formats.

---

### 16. Missing Error Handling for Database Operations
**File**: `app/Controllers/CarpoolController.php:232-246`
**Severity**: Low

Database operations without try/catch:

```php
$this->db->prepare("INSERT INTO ride_requests ...")->execute([...]);
$this->db->prepare("UPDATE carpools ...")->execute([...]);
$this->db->prepare("UPDATE users ...")->execute([...]);
// No try/catch - if any fails, partial data is saved
```

**Impact**: Partial transactions may leave data in inconsistent state.

---

### 17. Incomplete Review Duplicate Check
**File**: `app/Controllers/ReviewController.php:42-46`
**Severity**: Low

```php
$check = $this->db->prepare("SELECT id FROM ride_reviews WHERE ride_request_id = ?");
$check->execute([$rideRequestId]);
if ($check->fetch()) {
    return $response->withHeader('Location', '/rides')->withStatus(302);
}
```

**Problem**: Only checks `ride_request_id`, not `reviewer_id`. This means:
- Same user can't submit two reviews (correct)
- But NO other user can review the same ride (incorrect)

**Impact**: Only one review per ride ever, regardless of reviewer.

---

## Summary Table

| # | Issue | File(s) | Severity | Type |
|---|-------|---------|----------|------|
| 1 | CSRF Protection Disabled | index.php | Critical | Security |
| 2 | TLS Verification Disabled | db.php | Critical | Security |
| 3 | Debug Mode in Production | index.php, UserController.php | High | Security |
| 4 | Missing Auth on Employee Routes | EmployeeController.php | High | Security |
| 5 | Missing Auth on Review/Ride Ops | ReviewController.php, RideController.php | High | Security |
| 6 | Sensitive Data in Errors | UserController.php | High | Security |
| 7 | Session Fixation | UserController.php | Medium | Security |
| 8 | Race Condition in Booking | CarpoolController.php | Medium | Logic |
| 9 | Missing Route Handlers | routes.php | Medium | Logic |
| 10 | Inconsistent Status Values | RideController.php | Medium | Logic |
| 11 | Incomplete Driver Check | CarpoolController.php | Medium | Logic |
| 12 | Missing Input Validation | CarpoolController.php | Medium | Validation |
| 13 | Unused/Dead Code | routes.php, UserController.php | Low | Quality |
| 14 | Hardcoded Values | CarpoolController.php | Low | Config |
| 15 | Inconsistent Responses | Multiple | Low | Quality |
| 16 | Missing Error Handling | CarpoolController.php | Low | Quality |
| 17 | Incomplete Duplicate Check | ReviewController.php | Low | Logic |

---

## Recommended Priority

**Immediate Action Required (P0)**:
1. Enable CSRF protection
2. Fix TLS certificate verification
3. Add authorization to Employee routes
4. Disable debug mode in production

**Short-term (P1)**:
5. Add authorization to Review/Ride operations
6. Remove sensitive data from error responses
7. Fix session fixation vulnerability
8. Add transactions to booking logic

**Medium-term (P2)**:
9. Implement missing route handlers or remove routes
10. Standardize status values
11. Fix driver self-join logic
12. Add input validation

**Technical Debt (P3)**:
13-17. Clean up code quality issues
