<?php
// Use the centralised secure session configuration
require_once __DIR__ . '/../config/session_config.php';

header('Content-Type: application/json; charset=utf-8');

// Identify this as an API request
$isApiRequest = true;

// Check whether the login session has expired
require_once __DIR__ . '/../config/session_timeout.php';

// Check whether the user has pharmacist access
require_once __DIR__ . '/../config/authorization.php';

requireRole(['pharmacist']);

$loggedInUser = $_SESSION['user_id'] ?? '';

require_once __DIR__ . '/../model/Pharmacist.php';

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$model = new PharmacyModel($conn);

// Get order details
if ($action === 'get_details') {
    $order_id = $input['order_id'] ?? '';
    $data = $model->getOrderDetails($order_id);

    if (!$data['order']) {
        echo json_encode([
            'success' => false,
            'msg' => 'Order not found'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'order' => $data['order'],
            'details' => $data['details']
        ]);
    }
    exit;
}

if (in_array($action, ['approve', 'reject', 'done'])) {
    $order_id = $input['order_id'] ?? '';
    $note = $input['note'] ?? '';
    $res = $model->updateOrder($order_id, $action, $loggedInUser, $note);
    echo json_encode($res);
    exit;
}

if ($action === 'add_medicine') {
    $name = trim($input['name'] ?? '');
    $price = $input['price'] ?? null;
    $quantity = $input['quantity'] ?? null;
    $description = trim($input['description'] ?? '');

    // Server-Side Input Validation
    if ($name === '') {
        echo json_encode([
            'success' => false,
            'msg' => 'Medicine name cannot be empty'
        ]);
        exit;
    }

    if (!is_numeric($price) || (float)$price <= 0) {
        echo json_encode([
            'success' => false,
            'msg' => 'Medicine price must be greater than zero'
        ]);
        exit;
    }

    if (filter_var($quantity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
        echo json_encode([
            'success' => false,
            'msg' => 'Medicine quantity must be a non-negative integer'
        ]);
        exit;
    }

    $res = $model->addMedicine($name, (float)$price, (int)$quantity, $description);
    echo json_encode($res);
    exit;
}

if ($action === 'update_stock') {
    $id = trim($input['id'] ?? '');
    $quantity = $input['quantity'] ?? null;

    if ($id === '') {
        echo json_encode([
            'success' => false,
            'msg' => 'Medicine ID is required'
        ]);
        exit;
    }

    if (filter_var($quantity, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
        echo json_encode([
            'success' => false,
            'msg' => 'Medicine quantity must be a non-negative integer'
        ]);
        exit;
    }

    $res = $model->updateStock($id, (int)$quantity);
    echo json_encode($res);
    exit;
}

if ($action === 'delete_medicine') {
    $id = trim($input['id'] ?? '');

    if ($id === '') {
        echo json_encode([
            'success' => false,
            'msg' => 'Medicine ID is required'
        ]);
        exit;
    }

    $res = $model->deleteMedicine($id);
    echo json_encode($res);
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Invalid action']);
?>