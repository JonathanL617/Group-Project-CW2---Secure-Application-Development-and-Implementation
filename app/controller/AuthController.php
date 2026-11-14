<?php
require_once __DIR__ . "/../config/conn.php";
require_once __DIR__ . "/password_functions.php";
require_once __DIR__ . "/../model/UserModel.php";
require_once dirname(__DIR__, 2) . "/q5/includes/q5_db.php";
require_once dirname(__DIR__, 2) . "/q5/includes/login_security.php";

class AuthController {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function login($email, $password) {
        try {
            if (empty($email) || empty($password)) {
                throw new Exception('All fields are required.');
            }

            $email = trim($email);

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format.');
            }

            // Connect to the separate Q5 security database.
            $q5Conn = q5_get_db_connection();

            // Get client IP and browser information.
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

            if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
                $ipAddress = '127.0.0.1';
            }

            $userAgent = substr(
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                0,
                512
            );

            // Check whether this account/IP combination is blocked.
            if (isLoginBlocked($q5Conn, $email, $ipAddress)) {
                throw new Exception(
                    'Too many failed login attempts. Please try again after 15 minutes.'
                );
            }

            // Retrieve the user account.
            $userModel = new UserModel($this->conn);
            $row = $userModel->getUserByEmail($email);

            // Verify the password.
            if (!$row || !isPasswordCorrect($password, $row['user_password'])) {

                // Record failed login and apply lockout if threshold is reached.
                $result = recordFailedLogin(
                    $q5Conn,
                    $email,
                    $ipAddress,
                    $userAgent
                );

                if ($result['blocked']) {
                    throw new Exception(
                        'Too many failed login attempts. Your account is temporarily restricted for 15 minutes.'
                    );
                }

                throw new Exception(
                    'Invalid email or password. Attempts remaining: ' .
                    $result['remaining']
                );
            }

            // Check whether the account is active.
            if (strtolower($row['user_status']) !== 'active') {
                throw new Exception(
                    'Your account is ' .
                    htmlspecialchars($row['user_status']) .
                    '. Please contact your administrator.'
                );
            }

            // Record successful login and clear previous failed attempts.
            recordSuccessfulLogin(
                $q5Conn,
                $email,
                $ipAddress,
                (string) $row['user_id'],
                (string) $row['user_role'],
                $userAgent
            );

            // Start session if not already started.
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            session_regenerate_id(true);

            // Set generic session variables.
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['user_email'] = $row['user_email'];
            $_SESSION['user_role'] = $row['user_role'];

            // Set role-specific session variables.
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

            // Handle Remember Me.
            if (isset($_POST['remember'])) {
                setcookie(
                    'user_login',
                    $email,
                    time() + (86400 * 30),
                    "/"
                );
            } else {
                setcookie('user_login', '', time() - 3600, "/");
            }

            // Redirect according to user role.
            $redirectUrl = BASE_URL .
                "/app/view/dashboard.php?page=" .
                $this->getDefaultPage($row['user_role']);

            header("Location: $redirectUrl");
            exit();

        } catch (Exception $e) {

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $_SESSION['login_error'] = $e->getMessage();

            header("Location: " . BASE_URL . "/app/view/login_page.php");
            exit();
        }
    }

    private function getDefaultPage($role) {
        $defaultPages = [
            'superadmin' => 'manage_accounts',
            'admin' => 'manage_users',
            'pharmacist' => 'stock_management',
            'doctor' => 'patient_records'
        ];
        
        return $defaultPages[$role] ?? 'manage_users';
    }

    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        header("Location: " . BASE_URL . '/app/view/login_page.php');
        exit();
    }

    public function resetPassword($email, $newPassword, $confirmPassword) {
        try {
            if (empty($email) || empty($newPassword) || empty($confirmPassword)) {
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
                if ($userModel->updatePassword($email, $hashedPassword, $user['user_table'])) {
                    return true; // Success
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

// Handle login request if this file is accessed directly
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $auth = new AuthController($conn);
    $auth->login($_POST['email'], $_POST['password']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    $auth = new AuthController($conn);
    $auth->logout();
}

?>