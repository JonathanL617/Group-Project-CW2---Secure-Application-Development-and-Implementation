<?php
require_once __DIR__ . '/../config/session_config.php';

header('Content-Type: application/json; charset=utf-8');

// Identify this as an API request
$isApiRequest = true;

// Check session timeout
require_once __DIR__ . '/../config/session_timeout.php';

// Check doctor authorization
require_once __DIR__ . '/../config/authorization.php';

requireRole(['doctor']);

require_once __DIR__ . '/../config/conn.php';
require_once __DIR__ . '/../model/Doctor.php';

$loggedInUser = $_SESSION['staff_id'] ?? '';

$model = new DoctorModel($conn);

// Get the request method and input
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $action;
}

switch ($action) {
    case 'getStaffInfo':
        $staff = $model->getStaffById($loggedInUser);
        echo json_encode(['success' => true, 'data' => $staff]);
        break;

    case 'getPatients':
        $patients = $model->getPatients();
        echo json_encode(['success' => true, 'patients' => $patients]);
        break;

    case 'getPatientHistory':
        $patientId = $_GET['patient_id'] ?? '';
        $history = $model->getPatientPrescriptionHistory($patientId);
        echo json_encode(['success' => true, 'history' => $history]);
        break;

    case 'getPatientRecords':
        $records = $model->getPatientRecords();
        echo json_encode(['success' => true, 'records' => $records]);
        break;

    case 'searchMedicines':
        $query = $_GET['q'] ?? '';
        $medicines = $model->searchMedicines($query);
        echo json_encode(['success' => true, 'medicines' => $medicines]);
        break;
        
    case 'createPrescription':
        $patientId = $input['patient_id'] ?? '';
        $staffId = $loggedInUser; // Use authenticated doctor from session
        $medicines = $input['medicines'] ?? [];

        // Validate patient ID and medicine list
        if (!is_string($patientId) ||
            trim($patientId) === '' ||
            !is_array($medicines) ||
            count($medicines) === 0) {

            echo json_encode([
                'success' => false,
                'msg' => 'Invalid patient or medicine information'
            ]);
            break;
        }

        // Server-side medicine quantity validation
        $validMedicines = true;

        foreach ($medicines as $med) {
            if (!is_array($med) ||
                !isset($med['id'], $med['qty']) ||
                !is_string($med['id']) ||
                trim($med['id']) === '' ||
                filter_var(
                    $med['qty'],
                    FILTER_VALIDATE_INT,
                    ['options' => ['min_range' => 1]]
                ) === false) {

                $validMedicines = false;
                break;
            }
        }

        if (!$validMedicines) {
            echo json_encode([
                'success' => false,
                'msg' => 'Medicine quantity must be a positive integer'
            ]);
            break;
        }

        $res = $model->createPrescription(
            $patientId,
            $staffId,
            $medicines
        );

        echo json_encode($res);
        break;
        
    case 'getDashboardStats':
        $stats = $model->getDashboardStats();
        echo json_encode(['success' => true, 'stats' => $stats]);
        break;
        
    case 'getPrescriptionsByStaff':
        $search = $_GET['search'] ?? '';
        $staffId = $loggedInUser;
        
        if (empty($staffId)) {
            echo json_encode(['success' => false, 'msg' => 'Staff ID is required']);
            break;
        }
        
        $prescriptions = $model->getPrescriptionsByStaff($staffId, $search);
        echo json_encode(['success' => true, 'prescriptions' => $prescriptions]);
        break;

    default:
        echo json_encode(['success' => false, 'msg' => 'Invalid action']);
        break;
}
?>