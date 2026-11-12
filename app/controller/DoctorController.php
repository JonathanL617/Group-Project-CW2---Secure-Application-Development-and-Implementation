
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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

header('Content-Type: application/json');

switch ($action) {
    case 'getPatients':
        $search = $_GET['search'] ?? '';
        $patients = $model->getAllPatients($search);
        echo json_encode(['success' => true, 'patients' => $patients]);
        break;
        
    case 'getPatientHistory':
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            echo json_encode(['success' => false, 'msg' => 'Patient ID is required']);
            break;
        }
        $history = $model->getPatientHistory($id);
        echo json_encode(['success' => true, 'history' => $history]);
        break;
        
    case 'getPrescriptions':
        $search = $_GET['search'] ?? '';
        $prescriptions = $model->getAllPrescriptions($search);
        echo json_encode(['success' => true, 'prescriptions' => $prescriptions]);
        break;
        
    case 'getMedicines':
        $medicines = $model->getAllMedicines();
        echo json_encode(['success' => true, 'medicines' => $medicines]);
        break;
        
    case 'createPrescription':
        $patientId = $input['patient_id'] ?? '';
        $staffId = $loggedInUser;
        $medicines = $input['medicines'] ?? [];

        if (empty($patientId) || empty($medicines)) {
            echo json_encode(['success' => false, 'msg' => 'Missing required fields']);
            break;
        }

        $res = $model->createPrescription($patientId, $staffId, $medicines);
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

    // Add this case to your switch statement in the controller
    case 'getDoctorStats':
        $stats = $model->getDoctorStats($loggedInUser);
        echo json_encode(['success' => true, 'stats' => $stats]);
        break;


    case 'getPrescriptionDetails':
        $orderId = $_GET['order_id'] ?? '';
        if (empty($orderId)) {
            echo json_encode(['success' => false, 'msg' => 'Order ID is required']);
            break;
        }
        $result = $model->getPrescriptionDetails($orderId);
        echo json_encode($result);
        break;

}
?>