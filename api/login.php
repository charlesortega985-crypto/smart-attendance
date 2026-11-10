<?php
// login.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$config_file = __DIR__ . '/config/db_connect.php';
if (file_exists($config_file)) {
    include_once $config_file;
}

$msg = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_number = trim($_POST['id_number'] ?? '');
    $password  = $_POST['password'] ?? '';

    if (!empty($id_number) && !empty($password)) {
        if (isset($pdo)) {
            try {
                $stmt = $pdo->prepare("
                    SELECT * FROM users 
                    WHERE TRIM(student_faculty_id) = ? 
                       OR TRIM(id_number) = ? 
                       OR TRIM(user_id) = ? 
                    LIMIT 1
                ");
                $stmt->execute([$id_number, $id_number, $id_number]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_id']   = $user['user_id'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role']      = $user['role'];
                    $_SESSION['id_number'] = $user['student_faculty_id'] ?? $user['id_number'];

                    if ($user['role'] === 'faculty') {
                        header("Location: faculty/index.php");
                    } else {
                        header("Location: student/index.php");
                    }
                    exit;
                } else {
                    $msg = "<div class='alert alert-danger py-2 rounded-3 mb-3'><small><i class='bi bi-exclamation-triangle-fill me-1'></i> Invalid ID Number or Password!</small></div>";
                }
            } catch (Exception $e) {
                $msg = "<div class='alert alert-danger py-2 rounded-3 mb-3'><small>Database Error: " . $e->getMessage() . "</small></div>";
            }
        }
    } else {
        $msg = "<div class='alert alert-warning py-2 rounded-3 mb-3'><small>Please fill in all fields.</small></div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Attendance System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS Style -->
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        input::-ms-reveal,
        input::-ms-clear {
            display: none !important;
        }
        .password-toggle-btn {
            cursor: pointer;
            user-select: none;
            background: transparent;
        }
        .divider-container {
            display: flex;
            align-items: center;
            text-align: center;
            color: #94a3b8;
            margin: 20px 0;
        }
        .divider-container::before,
        .divider-container::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid var(--border-color, #e2e8f0);
        }
        .divider-container span {
            padding: 0 10px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .btn-google {
            background-color: #ffffff;
            color: #1e293b;
            border: 1px solid #cbd5e1;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }
        .btn-google:hover {
            background-color: #f8fafc;
            border-color: #94a3b8;
            color: #0f172a;
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center p-3" style="min-height: 100vh;">

<div class="login-card w-100" style="max-width: 420px; border-radius: 20px;">
    
    <div class="d-flex justify-content-end mb-2">
        <button id="themeToggleBtn" onclick="toggleTheme()" class="btn btn-sm btn-outline-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;" title="Toggle Theme">
            <i id="themeIcon" class="bi bi-moon-stars-fill"></i>
        </button>
    </div>

    <div class="text-center mb-4">
        <h3 class="fw-bold text-body m-0 d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-mortarboard-fill text-primary"></i> Smart Attendance
        </h3>
        <p class="text-muted small mt-1">Sign in to your portal account</p>
    </div>

    <?= $msg ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label fw-bold small text-body">ID Number (Student / Faculty ID)</label>
            <div class="input-group">
                <span class="input-group-text inner-box border-end-0 text-body"><i class="bi bi-card-heading"></i></span>
                <input type="text" name="id_number" class="form-control border-start-0 inner-box text-body" required placeholder="e.g., 2026-0001">
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold small text-body">Password</label>
            <div class="input-group">
                <span class="input-group-text inner-box border-end-0 text-body"><i class="bi bi-lock"></i></span>
                <input type="password" id="loginPasswordInput" name="password" class="form-control border-start-0 border-end-0 inner-box text-body" required placeholder="••••••••">
                <button type="button" class="btn btn-outline-secondary inner-box border-start-0 text-body password-toggle-btn" onclick="toggleLoginPassword()">
                    <i id="loginEyeIcon" class="bi bi-eye"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 fw-bold py-2 rounded-pill shadow-sm" style="background-color: #6366f1; border: none;">
            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
        </button>
    </form>

    <!-- Google Button positioned at the bottom -->
    <div class="divider-container">
        <span>OR</span>
    </div>

    <a href="google_auth.php" class="btn btn-google w-100 py-2 rounded-pill shadow-sm text-decoration-none">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48">
            <path fill="#FFC107" d="M43.611 20.083H42V20H24v8h11.303c-1.649 4.657-6.08 8-11.303 8c-6.627 0-12-5.373-12-12s5.373-12 12-12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4C12.955 4 4 12.955 4 24s8.955 20 20 20s20-8.955 20-20c0-1.341-.138-2.65-.389-3.917z"/>
            <path fill="#FF3D00" d="m6.306 14.691l6.571 4.819C14.655 15.108 18.961 12 24 12c3.059 0 5.842 1.154 7.961 3.039l5.657-5.657C34.046 6.053 29.268 4 24 4C16.318 4 9.656 8.337 6.306 14.691z"/>
            <path fill="#4CAF50" d="M24 44c5.166 0 9.86-1.977 13.409-5.192l-6.19-5.238A11.91 11.91 0 0 1 24 36c-5.202 0-9.619-3.317-11.283-7.946l-6.522 5.025C9.505 39.556 16.227 44 24 44z"/>
            <path fill="#1976D2" d="M43.611 20.083H42V20H24v8h11.303a12.04 12.04 0 0 1-4.087 5.571l.003-.002l6.19 5.238C36.971 39.205 44 34 44 24c0-1.341-.138-2.65-.389-3.917z"/>
        </svg>
        Continue with Google
    </a>

    <div class="text-center mt-4 pt-3 border-top">
        <small class="text-muted">Don't have an account yet? <a href="register.php" class="fw-bold text-primary text-decoration-none">Sign up here</a></small>
    </div>
</div>

<script src="assets/js/theme.js"></script>

<script>
    function toggleLoginPassword() {
        const passInput = document.getElementById('loginPasswordInput');
        const eyeIcon   = document.getElementById('loginEyeIcon');
        
        if (passInput.type === 'password') {
            passInput.type = 'text';
            eyeIcon.classList.remove('bi-eye');
            eyeIcon.classList.add('bi-eye-slash');
        } else {
            passInput.type = 'password';
            eyeIcon.classList.remove('bi-eye-slash');
            eyeIcon.classList.add('bi-eye');
        }
    }
</script>

</body>
</html>