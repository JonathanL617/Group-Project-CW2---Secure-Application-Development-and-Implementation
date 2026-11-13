<?php
// Secure API Error Handling
ini_set('display_errors', '0');
ini_set('log_errors', '1');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/../config/session_config.php';

header('Content-Type: application/json; charset=utf-8');

// Identify this as an API request
$isApiRequest = true;

// Check session timeout
require_once __DIR__ . '/../config/session_timeout.php';

// Only Admin can access
require_once __DIR__ . '/../config/authorization.php';
requireRole(['admin']);

require_once __DIR__ . '/../config/conn.php';

class AdminController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Get Staff
    public function getStaff() {
        $sql = "SELECT * FROM staff ORDER BY staff_id";
        $result = $this->conn->query($sql);

        echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    }

    // Get Patients
    public function getPatients() {
        $sql = "SELECT * FROM patient ORDER BY patient_id";
        $result = $this->conn->query($sql);

        echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    }

    // Get Staff By ID
    public function getStaffById($id) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM staff WHERE staff_id = ?"
        );

        $stmt->bind_param('s', $id);
        $stmt->execute();

        $result = $stmt->get_result()->fetch_assoc();

        echo json_encode($result);
        $stmt->close();
    }

    // Get Patient By ID
    public function getPatientById($id) {
        $stmt = $this->conn->prepare(
            "SELECT * FROM patient WHERE patient_id = ?"
        );

        $stmt->bind_param('s', $id);
        $stmt->execute();

        $result = $stmt->get_result()->fetch_assoc();

        echo json_encode($result);
        $stmt->close();
    }

    // Update Staff
    public function updateStaff() {
        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        $stmt = $this->conn->prepare("
            UPDATE staff SET
                staff_name = ?,
                staff_dob = ?,
                staff_specialization = ?,
                staff_role = ?,
                staff_phone = ?,
                staff_email = ?,
                staff_status = ?
            WHERE staff_id = ?
        ");

        $stmt->bind_param(
            'ssssssss',
            $data['staff_name'],
            $data['staff_dob'],
            $data['staff_specialization'],
            $data['staff_role'],
            $data['staff_phone'],
            $data['staff_email'],
            $data['staff_status'],
            $data['staff_id']
        );

        $success = $stmt->execute();

        echo json_encode([
            'success' => $success
        ]);

        $stmt->close();
    }

    // Generate Patient ID
    public function generatePatientId() {
        $result = $this->conn->query(
            "SELECT patient_id FROM patient ORDER BY patient_id DESC LIMIT 1"
        );

        if ($result && $result->num_rows > 0) {
            $lastID = $result->fetch_assoc()['patient_id'];
            $num = (int)substr($lastID, 1) + 1;

            $newID = 'P' . str_pad(
                $num,
                3,
                '0',
                STR_PAD_LEFT
            );
        } else {
            $newID = 'P001';
        }

        echo json_encode([
            'id' => $newID
        ]);
    }

    // Register Patient - Server-Side Input Validation
    public function registerPatient() {
        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        // Validate request data
        if (!is_array($data)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid request data'
            ]);
            return;
        }

        // Validate Patient ID
        if (!is_string($data['patient_id'] ?? null) ||
            trim($data['patient_id']) === '') {

            echo json_encode([
                'success' => false,
                'message' => 'Patient ID is required'
            ]);
            return;
        }

        // Validate Patient Name
        if (!is_string($data['patient_name'] ?? null) ||
            trim($data['patient_name']) === '') {

            echo json_encode([
                'success' => false,
                'message' => 'Patient name is required'
            ]);
            return;
        }

        // Server-Side Email Validation
        $email = $data['patient_email'] ?? null;

        if (!is_string($email) ||
            !filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {

            echo json_encode([
                'success' => false,
                'message' => 'Invalid email address'
            ]);
            return;
        }

        $data['patient_email'] = trim($email);
        $data['registered_by'] = $_SESSION['admin_id'] ?? '';

        // Insert Patient Information
        $stmt = $this->conn->prepare("
            INSERT INTO patient (
                patient_id,
                patient_name,
                patient_date_of_birth,
                patient_phone,
                patient_gender,
                patient_email,
                patient_address,
                registered_by
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            'ssssssss',
            $data['patient_id'],
            $data['patient_name'],
            $data['patient_date_of_birth'],
            $data['patient_phone'],
            $data['patient_gender'],
            $data['patient_email'],
            $data['patient_address'],
            $data['registered_by']
        );

        // Secure Error Handling
        try {
            $stmt->execute();

            echo json_encode([
                'success' => true
            ]);

        } catch (Throwable $e) {

            // Log detailed database errors on the server
            error_log(
                'Register Patient Error: ' . $e->getMessage()
            );

            // Return a safe message to the user
            echo json_encode([
                'success' => false,
                'error' => 'Unable to register patient. Please try again.'
            ]);

        } finally {
            $stmt->close();
        }
    }

    // Update Patient - Server-Side Input Validation
    public function updatePatient() {
        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        // Validate request data
        if (!is_array($data)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid request data'
            ]);
            return;
        }

        // Validate Patient ID
        if (!is_string($data['patient_id'] ?? null) ||
            trim($data['patient_id']) === '') {

            echo json_encode([
                'success' => false,
                'message' => 'Patient ID is required'
            ]);
            return;
        }

        // Validate Patient Name
        if (!is_string($data['patient_name'] ?? null) ||
            trim($data['patient_name']) === '') {

            echo json_encode([
                'success' => false,
                'message' => 'Patient name is required'
            ]);
            return;
        }

        // Server-Side Email Validation
        $email = $data['patient_email'] ?? null;

        if (!is_string($email) ||
            !filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {

            echo json_encode([
                'success' => false,
                'message' => 'Invalid email address'
            ]);
            return;
        }

        $data['patient_email'] = trim($email);

        // Update Patient Information
        $stmt = $this->conn->prepare("
            UPDATE patient SET
                patient_name = ?,
                patient_date_of_birth = ?,
                patient_phone = ?,
                patient_gender = ?,
                patient_email = ?,
                patient_address = ?
            WHERE patient_id = ?
        ");

        $stmt->bind_param(
            'sssssss',
            $data['patient_name'],
            $data['patient_date_of_birth'],
            $data['patient_phone'],
            $data['patient_gender'],
            $data['patient_email'],
            $data['patient_address'],
            $data['patient_id']
        );

        $success = $stmt->execute();

        echo json_encode([
            'success' => $success
        ]);

        $stmt->close();
    }

    // Generate Staff ID
    public function generateStaffId() {
        $role = $_GET['role'] ?? '';

        $prefix = ($role === 'doctor')
            ? 'D'
            : (($role === 'pharmacist') ? 'P' : 'S');

        $sql = "SELECT staff_id FROM staff
                WHERE staff_id LIKE ?
                ORDER BY staff_id DESC LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $likeParam = $prefix . '%';

        $stmt->bind_param('s', $likeParam);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $lastID = $result->fetch_assoc()['staff_id'];
            $num = (int)substr($lastID, 1) + 1;

            $newID = $prefix . str_pad(
                $num,
                3,
                '0',
                STR_PAD_LEFT
            );
        } else {
            $newID = $prefix . '001';
        }

        echo json_encode([
            'id' => $newID
        ]);

        $stmt->close();
    }

    // Create Staff Request
    public function createStaffRequest() {
        $data = json_decode(
            file_get_contents('php://input'),
            true
        );

        // Basic Validation
        if (empty($data['staff_id']) ||
            empty($data['staff_email'])) {

            echo json_encode([
                'success' => false,
                'message' => 'Missing required fields'
            ]);
            return;
        }

        // Check Duplicate Email in Staff Table
        $check = $this->conn->prepare(
            "SELECT staff_id FROM staff WHERE staff_email = ?"
        );

        $check->bind_param('s', $data['staff_email']);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Email already exists in active staff'
            ]);

            $check->close();
            return;
        }

        $check->close();

        // Check Duplicate Email in Pending Staff Table
        $checkPending = $this->conn->prepare(
            "SELECT pending_id FROM pending_staff WHERE staff_email = ?"
        );

        $checkPending->bind_param('s', $data['staff_email']);
        $checkPending->execute();

        if ($checkPending->get_result()->num_rows > 0) {
            echo json_encode([
                'success' => false,
                'message' => 'A request with this email is already pending approval'
            ]);

            $checkPending->close();
            return;
        }

        $checkPending->close();

        // Insert into Pending Staff Table
        $stmt = $this->conn->prepare("
            INSERT INTO pending_staff (
                staff_id,
                staff_name,
                staff_dob,
                staff_specialization,
                staff_role,
                staff_phone,
                staff_email,
                registered_by
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            'ssssssss',
            $data['staff_id'],
            $data['staff_name'],
            $data['staff_dob'],
            $data['staff_specialization'],
            $data['staff_role'],
            $data['staff_phone'],
            $data['staff_email'],
            $data['registered_by']
        );

        // Secure Error Handling
        try {
            $stmt->execute();

            // Insert into User Approval Table
            $approvalStmt = $this->conn->prepare("
                INSERT INTO user_approval (
                    approved_user_id,
                    status
                )
                VALUES (?, 'pending')
            ");

            $approvalStmt->bind_param(
                's',
                $data['staff_id']
            );

            $approvalStmt->execute();
            $approvalStmt->close();

            echo json_encode([
                'success' => true,
                'message' => 'Staff request submitted for approval'
            ]);

        } catch (Throwable $e) {

            // Log detailed errors on the server
            error_log(
                'Create Staff Request Error: ' . $e->getMessage()
            );

            // Return a safe message to the user
            echo json_encode([
                'success' => false,
                'error' => 'Unable to submit staff request. Please try again.'
            ]);

        } finally {
            $stmt->close();
        }
    }
}


// Router - Secure Error Handling for All Actions
try {
    require_once __DIR__ . "/../config/conn.php";

    $action = $_GET['action'] ?? '';
    $id = $_GET['id'] ?? '';

    $controller = new AdminController($conn);

    match ($action) {
        'getStaff' => $controller->getStaff(),
        'getPatients' => $controller->getPatients(),
        'getStaffById' => $controller->getStaffById($id),
        'getPatientById' => $controller->getPatientById($id),
        'updateStaff' => $controller->updateStaff(),
        'updatePatient' => $controller->updatePatient(),
        'generatePatientId' => $controller->generatePatientId(),
        'registerPatient' => $controller->registerPatient(),
        'generateStaffId' => $controller->generateStaffId(),
        'createStaffRequest' => $controller->createStaffRequest(),

        default => (function() {
            echo json_encode([
                'error' => 'Invalid action'
            ]);
        })()
    };

} catch (Throwable $e) {

    // Log detailed errors on the server
    error_log(
        'AdminController Error: ' . $e->getMessage()
    );

    // Return a generic error message
    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => 'An internal server error occurred. Please try again.',
        'message' => 'An internal server error occurred. Please try again.'
    ]);
}
?>