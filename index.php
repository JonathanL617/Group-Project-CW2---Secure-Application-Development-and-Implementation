<?php
    require_once __DIR__ . '/app/config/session_config.php';
    require_once __DIR__ . '/app/config/config.php';

    // If the session does not contain user_id, redirect to login page
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . BASE_URL . '/app/view/login_page.php');
        exit();
    }

    header('Location: ' . BASE_URL . '/app/view/dashboard.php');
    exit();
?>
