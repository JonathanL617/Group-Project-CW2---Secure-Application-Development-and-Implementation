
<?php
session_start(); // required
header('Content-Type: application/json');

$loggedInUser = $_SESSION['user_id'] ?? 'P001'; // fallback
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

// Approve, reject or complete order
if (in_array($action, ['approve', 'reject', 'done'])) {
    $order_id = $input['order_id'] ?? '';
    $approver_id = $loggedInUser;
    $comment = $input['comment'] ?? '';

    $res = $model->updateOrder(
        $order_id,
        $action,
        $approver_id,
        $comment
    );

    echo json_encode($res);
    exit;
}

// List orders
if ($action === 'list_orders') {
    $filter = $input['filter'] ?? '';
    $search = $input['search'] ?? '';

    $orders = $model->getAllOrders($search, $filter);

    echo json_encode([
        'success' => true,
        'orders' => $orders
    ]);
    exit;
}

// Medicine Management
if ($action === 'list_medicines') {
    $search = $input['search'] ?? '';

    $medicines = $search
        ? $model->searchMedicines($search)
        : $model->getAllMedicines();

    echo json_encode([
        'success' => true,
        'medicines' => $medicines
    ]);
    exit;
}

// Add Medicine
if ($action === 'add_medicine') {
    $name = $input['name'] ?? '';
    $price = $input['price'] ?? 0;
    $qty = $input['quantity'] ?? 0;
    $desc = $input['description'] ?? '';

    // Validate medicine price
    if (!is_numeric($price) ||
        !is_finite((float)$price) ||
        (float)$price <= 0) {

        echo json_encode([
            'success' => false,
            'msg' => 'Medicine price must be greater than zero'
        ]);
        exit;
    }

    // Validate medicine name
    if (!is_string($name) ||
        trim($name) === '' ||
        strlen($name) > 100) {

        echo json_encode([
            'success' => false,
            'msg' => 'Medicine name must be 1 to 100 characters'
        ]);
        exit;
    }

    // Validate medicine quantity
    if (filter_var(
        $qty,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 0]]
    ) === false) {

        echo json_encode([
            'success' => false,
            'msg' => 'Medicine quantity must be a non-negative integer'
        ]);
        exit;
    }

    // Validate medicine description
    if (!is_string($desc) ||
        strlen($desc) > 255) {

        echo json_encode([
            'success' => false,
            'msg' => 'Medicine description must not exceed 255 characters'
        ]);
        exit;
    }

    $res = $model->addMedicine(
        $name,
        $price,
        $qty,
        $desc
    );

    echo json_encode($res);
    exit;
}

// Update Medicine
if ($action === 'update_medicine') {
    $id = $input['id'] ?? '';
    $name = $input['name'] ?? '';
    $price = $input['price'] ?? 0;
    $qty = $input['quantity'] ?? 0;
    $desc = $input['description'] ?? '';

    // Validate medicine ID
    if (!is_string($id) ||
        trim($id) === '') {

        echo json_encode([
            'success' => false,
            'msg' => 'Invalid medicine ID'
        ]);
        exit;
    }

    // Validate medicine price
    if (!is_numeric($price) ||
        !is_finite((float)$price) ||
        (float)$price <= 0) {

        echo json_encode([
            'success' => false,
            'msg' => 'Medicine price must be greater than zero'
        ]);
        exit;
    }

    // Validate medicine name
    if (!is_string($name) ||
        trim($name) === '' ||
        strlen($name) > 100) {

        echo json_encode([
            'success' => false,
            'msg' => 'Medicine name must be 1 to 100 characters'
        ]);
        exit;
    }

    // Validate medicine quantity
    if (filter_var(
        $qty,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 0]]
    ) === false) {

        echo json_encode([
            'success' => false,
            'msg' => 'Medicine quantity must be a non-negative integer'
        ]);
        exit;
    }

    // Validate medicine description
    if (!is_string($desc) ||
        strlen($desc) > 255) {

        echo json_encode([
            'success' => false,
            'msg' => 'Medicine description must not exceed 255 characters'
        ]);
        exit;
    }

    $res = $model->updateMedicine(
        $id,
        $name,
        $price,
        $qty,
        $desc
    );

    echo json_encode($res);
    exit;
}

// Delete Medicine - Preserved
if ($action === 'delete_medicine') {
    $id = $input['id'] ?? '';

    $res = $model->deleteMedicine($id);

    echo json_encode($res);
    exit;
}

// Unknown action
echo json_encode([
    'success' => false,
    'msg' => 'Unknown action'
]);
?>
