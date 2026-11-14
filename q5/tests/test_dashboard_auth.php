<?php

require_once __DIR__ . '/../includes/dashboard_auth.php';

$tests = [
    'superadmin' => 'full',
    'admin' => 'limited',
    'doctor' => null,
    'pharmacist' => null,
    'unknown' => null
];

echo "Starting dashboard permission tests...\n\n";

$passed = 0;

foreach ($tests as $role => $expected) {
    $actual = q5_get_dashboard_access_level($role);

    if ($actual === $expected) {
        echo "[PASS] {$role}: " .
            ($actual ?? 'No access') . "\n";
        $passed++;
    } else {
        echo "[FAIL] {$role}\n";
    }
}

echo "\nResults: {$passed}/" . count($tests) . " tests passed.\n";

if ($passed === count($tests)) {
    echo "DASHBOARD PERMISSION TEST PASSED!\n";
}
