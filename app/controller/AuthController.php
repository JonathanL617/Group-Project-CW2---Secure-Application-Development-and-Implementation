
<?php

require_once __DIR__ . '/../config/session_config.php';
require_once __DIR__ . "/../config/conn.php";
require_once __DIR__ . "/password_functions.php";
require_once __DIR__ . "/../model/UserModel.php";

class AuthController {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // =========================
    // LOGIN
    // =========================

    public function login($email, $password) {

        try {

            if (empty($email) || empty($password)) {
                throw new Exception('All fields are required.');
            }

            $email = trim($email);

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format.');
            }

            $userModel = new UserModel($this->conn);
            $row = $userModel->getUserByEmail($email);

            // Check email and password
            if (!$row || !isPasswordCorrect($password, $row['user_password'])) {
                throw new Exception('Invalid email or password.');
            }
            // Check whether staff must change their temporary password


            // Check account status
            if (strtolower($row['user_status']) !== 'active') {
                throw new Exception(
                    'Your account is ' .
                    $row['user_status'] .
                    '. Please contact your administrator.'
                );
            }
            // Check whether newly approved staff must change their temporary password


            // Start session if not already started
         //   if (session_status() === PHP_SESSION_NONE) {
          //      session_start();
            //}

            // Prevent session fixation
            session_regenerate_id(true);

            // Store generic session variables
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['user_email'] = $row['user_email'];
            $_SESSION['user_role'] = $row['user_role'];

            // Set role-specific session variables
            switch ($row['user_role']) {

                case 'superadmin':
                    $_SESSION['super_admin_id'] = $row['user_id'];
                    break;

                case 'admin':
                    $_SESSION['admin_id'] = $row['user_id'];
                    break;

                case 'doctor':
                case 'pharmacist':
                    $_SESSION['staff_id'] = $row['user_id'];
                    break;
            }

            // =========================
            // CREATE DATABASE SESSION
            // =========================

            $stmt = $this->conn->prepare("
                INSERT INTO sessions (
                    user_id,
                    user_role,
                    login_time,
                    last_activity,
                    status
                )
                VALUES (?, ?, NOW(), NOW(), 'Active')
            ");

            if (!$stmt) {
                throw new Exception('Unable to create login session.');
            }

            $userId = (string) $row['user_id'];
            $userRole = $row['user_role'];

            $stmt->bind_param("ss", $userId, $userRole);

            if (!$stmt->execute()) {
                error_log("Session insert failed: " . $stmt->error);
                $stmt->close();

                throw new Exception('Unable to create login session.');
            }

            // Store database session ID
            $_SESSION['session_id'] = $stmt->insert_id;
            $_SESSION['last_activity'] = time();
            // Check whether newly approved staff must change their temporary password
                if (
                    in_array($row['user_role'], ['doctor', 'pharmacist'], true) &&
                    (int)($row['must_change_password'] ?? 0) === 1
                ) {
                    $_SESSION['must_change_password'] = true;

                    header(
                        'Location: ' .
                        BASE_URL .
                        '/app/view/change_password.php'
                    );

                    exit();
                }
                            error_log(
                "Session created. ID: " . $stmt->insert_id .
                " | User: " . $userId
            );

            $stmt->close();

            // =========================
            // REMEMBER ME
            // =========================

            if (isset($_POST['remember'])) {

                setcookie(
                    'user_login',
                    $email,
                    time() + (86400 * 30),
                    "/"
                );

            } else {

                setcookie(
                    'user_login',
                    '',
                    time() - 3600,
                    "/"
                );
            }

            // =========================
            // REDIRECT BASED ON ROLE
            // =========================

            $redirectUrl =
                BASE_URL .
                "/app/view/dashboard.php?page=" .
                $this->getDefaultPage($row['user_role']);

            header("Location: $redirectUrl");
            exit();

        } catch (Exception $e) {

            // Remove partially created login information
            unset(
                $_SESSION['user_id'],
                $_SESSION['user_email'],
                $_SESSION['user_role'],
                $_SESSION['super_admin_id'],
                $_SESSION['admin_id'],
                $_SESSION['staff_id'],
                $_SESSION['session_id'],
                $_SESSION['last_activity']
            );

            $_SESSION['login_error'] = $e->getMessage();

            header(
                "Location: " .
                BASE_URL .
                "/app/view/login_page.php"
            );

            exit();
        }
    }


    // =========================
    // DEFAULT PAGE BY ROLE
    // =========================

    private function getDefaultPage($role) {

        $defaultPages = [
            'superadmin' => 'manage_accounts',
            'admin' => 'manage_users',
            'pharmacist' => 'stock_management',
            'doctor' => 'patient_records'
        ];

        return $defaultPages[$role] ?? 'manage_users';
    }


    // =========================
    // LOGOUT
    // =========================

    public function logout() {

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Update database session record
        if (
            isset($_SESSION['user_id']) &&
            isset($_SESSION['session_id'])
        ) {

            $sessionId = (int) $_SESSION['session_id'];
            $userId = (string) $_SESSION['user_id'];

            $stmt = $this->conn->prepare("
                UPDATE sessions
                SET
                    logout_time = NOW(),
                    status = 'Logged Out'
                WHERE
                    session_id = ?
                    AND user_id = ?
                    AND status = 'Active'
            ");

            if ($stmt) {

                $stmt->bind_param("is", $sessionId, $userId);

                $stmt->execute();
                $stmt->close();
            }
        }

        // Clear session data
        $_SESSION = [];

        // Clear session cookie
        if (ini_get('session.use_cookies')) {

            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        // Destroy PHP session
        session_destroy();

        // Return to login page
        header(
            "Location: " .
            BASE_URL .
            "/app/view/login_page.php"
        );

        exit();
    }


    // =========================
    // RESET PASSWORD
    // =========================

    public function resetPassword($email, $newPassword, $confirmPassword) {

        try {

            if (
                empty($email) ||
                empty($newPassword) ||
                empty($confirmPassword)
            ) {
                throw new Exception('All fields are required.');
            }

            $email = trim($email);

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format.');
            }

            if ($newPassword !== $confirmPassword) {
                throw new Exception('Passwords do not match.');
            }

            if (strlen($newPassword) < 8) {
                throw new Exception('Password must be at least 8 characters long.');
            }

            $userModel = new UserModel($this->conn);
            $user = $userModel->getUserByEmail($email);

            if ($user) {

                $hashedPassword = hashPassword($newPassword);

                if (
                    $userModel->updatePassword(
                        $email,
                        $hashedPassword,
                        $user['user_table']
                    )
                ) {
                    return true;

                } else {

                    throw new Exception('Password reset failed. Please try again.');
                }

            } else {

                throw new Exception('Email not found.');
            }

        } catch (Exception $e) {

            throw $e;
        }
    }

}


// =========================
// HANDLE LOGIN REQUEST
// =========================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['login'])
) {

    $auth = new AuthController($conn);

    $auth->login(
        $_POST['email'],
        $_POST['password']
    );
}


// =========================
// HANDLE LOGOUT REQUEST
// =========================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['logout'])
) {

    $auth = new AuthController($conn);

    $auth->logout();
}

?>
