
<?php
// APIController.php
// This file must output JSON only.

session_start();

header("Content-Type: application/json; charset=utf-8");

// Identify this as an API request
$isApiRequest = true;

// Check session timeout
require_once __DIR__ . '/../config/session_timeout.php';

// RBAC: Only Doctor and Pharmacist can access
require_once __DIR__ . '/../config/authorization.php';

requireRole(['doctor', 'pharmacist']);


// Validate drug name
if (!isset($_GET['name']) || trim($_GET['name']) === '') {
    http_response_code(400);

    echo json_encode([
        "success" => false,
        "message" => "No drug name provided."
    ]);
    exit;
}

require_once __DIR__ . '/../model/API.php';

$name = trim($_GET['name']);

$model = new DrugModel();
$result = $model->searchDrug($name);

echo json_encode($result);
exit;
