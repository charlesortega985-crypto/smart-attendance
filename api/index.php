<?php
// index.php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$config_file = __DIR__ . '/config/db_connect.php';
if (file_exists($config_file)) {
    include_once $config_file;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['login_input'] ?? '');
    $password    = trim($_POST['password'] ?? '');

    if (!empty($login_input) && !empty($password)) {
        if (isset($pdo)) {
            try {
                // Mag-login gamit ang ID Number o Email
                $stmt = $pdo->prepare("SELECT * FROM users WHERE student_faculty_id = ? OR email = ? LIMIT 1");
                $stmt->execute([$login_input, $login_input]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // I-set ang Session variables
                    $_SESSION['user_id']   = $user['user_id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role']      = $user['role'];
                    $_SESSION['id_number'] = $user['student_faculty_id'];

                    // Redirect batay sa Role
                    if ($user['role'] === 'student') {
                        header("Location: student/index.php");
                    } elseif ($user['role'] === 'faculty') {
                        header("Location: faculty/index.php");
                    } else {
                        header("Location: admin/index.php");
                    }
                    exit;
                } else {
                    $error = "Invalid ID/Email or Password.";
                }
            } catch (Exception $e) {
                $error = "Database error: " . $e->getMessage();
            }
        } else {
            $error = "Database connection not found.";
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Attendance Monitoring System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Hiwalay na CSS file mula sa assets -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Partikular na layout para sa Login Page */
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            background: var(--card-bg, #ffffff);
            color: var(--text-main, #212529);
        }
        .login-header {
            background: #1d4ed8;
            color: white;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
            padding: 25px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="card login-card">
    <div class="login-header">
        <h4 class="mb-1 fw-bold"><i class="bi bi-shield-lock-fill me-1"></i> Attendance Portal</h4>
        <small class="opacity-75">Smart Attendance Monitoring System</small>
    </div>
    
    <div class="card-body p-4">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger py-2 rounded-3"><small><i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?></small></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label"><small class="fw-bold">Student/Faculty ID or Email</small></label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-person-badge"></i></span>
                    <input type="text" class="form-control" name="login_input" placeholder="e.g. 2026-01234" required>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="form-label"><small class="fw-bold">Password</small></label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control" name="password" placeholder="Enter password" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold rounded-pill shadow-sm">
                <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
            </button>
        </form>
    </div>

    <div class="card-footer text-center py-3 bg-light rounded-bottom border-top">
        <small class="text-muted">Don't have an account? <a href="register.php" class="text-primary fw-bold text-decoration-none">Sign Up here</a></small>
    </div>
</div>

<!-- Theme JS para gumana ang dark mode sakaling i-toggle sa ibang pahina -->
<script src="assets/js/theme.js"></script>

</body>
</html>