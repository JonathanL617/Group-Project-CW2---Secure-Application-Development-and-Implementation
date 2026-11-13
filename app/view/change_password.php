
<?php
require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/conn.php';
require_once __DIR__ . '/../controller/password_functions.php';

// Only authenticated staff can access this page
if (
    !isset($_SESSION['user_id']) ||
    !in_array($_SESSION['user_role'] ?? '', ['doctor', 'pharmacist'], true)
) {
    header('Location: ' . BASE_URL . '/app/view/login_page.php');
    exit();
}

// Only staff required to change their temporary password
if (empty($_SESSION['must_change_password'])) {
    header('Location: ' . BASE_URL . '/app/view/dashboard.php');
    exit();
}

$error = '';

if (empty($_SESSION['change_password_token'])) {
    $_SESSION['change_password_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF validation
        if (
            !isset($_POST['csrf_token']) ||
            !hash_equals(
                $_SESSION['change_password_token'],
                $_POST['csrf_token']
            )
        ) {
            throw new Exception('Invalid request. Please try again.');
        }

        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($newPassword) || empty($confirmPassword)) {
            throw new Exception('Please complete all fields.');
        }

        if ($newPassword !== $confirmPassword) {
            throw new Exception('Passwords do not match.');
        }

        if (strlen($newPassword) < 8) {
            throw new Exception('Password must be at least 8 characters.');
        }

        // Hash the new password using Argon2id
        $hashedPassword = hashPassword($newPassword);

        // Update the staff account
        $stmt = $conn->prepare("
            UPDATE staff
            SET staff_password = ?, must_change_password = 0
            WHERE staff_id = ?
        ");

        $stmt->bind_param(
            "ss",
            $hashedPassword,
            $_SESSION['user_id']
        );

        $stmt->execute();

        if ($stmt->affected_rows !== 1) {
            $stmt->close();
            throw new Exception('Unable to update password. Please try again.');
        }

        $stmt->close();

        // Remove the mandatory password change flag
        unset($_SESSION['must_change_password']);
        unset($_SESSION['change_password_token']);

        header('Location: ' . BASE_URL . '/app/view/dashboard.php');
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7f6;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .container {
            background: white;
            padding: 30px;
            width: 350px;
            max-width: calc(100% - 60px);
            border-radius: 10px;
            box-shadow: 0 3px 12px rgba(0,0,0,0.1);
        }

        h2 {
            text-align: center;
            color: #004d40;
        }

        p {
            color: #555;
            text-align: center;
            font-size: 14px;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        input {
            box-sizing: border-box;
            width: 100%;
            padding: 11px;
            margin-top: 7px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 20px;
            background: #00796b;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #004d40;
        }

        .error {
            color: #c62828;
            background: #ffebee;
            padding: 10px;
            border-radius: 5px;
            font-size: 14px;
        }
    </style>
</head>

<body>

<div class="container">

    <h2>Change Password</h2>

    <p>
        Welcome! For security reasons, you must change
        your temporary password before accessing the system.
    </p>

    <?php if (!empty($error)): ?>
        <div class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($_SESSION['change_password_token'], ENT_QUOTES, 'UTF-8') ?>"
        >

        <label>New Password</label>
        <input
            type="password"
            name="new_password"
            minlength="8"
            required
        >

        <label>Confirm New Password</label>
        <input
            type="password"
            name="confirm_password"
            minlength="8"
            required
        >

        <button type="submit">Update Password</button>

    </form>

</div>

</body>
</html>
