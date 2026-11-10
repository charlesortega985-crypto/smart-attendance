<?php
// register.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$config_file = __DIR__ . '/config/db_connect.php';
if (file_exists($config_file)) {
    include_once $config_file;
}

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name  = trim($_POST['full_name'] ?? '');
    $id_number  = trim($_POST['id_number'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   =$_POST['password'] ?? '';
    $role       =$_POST['role'] ?? 'student';

    if (!empty($full_name) && !empty($id_number) && !empty($email) && !empty($password)) {
        if (isset($pdo)) {
            try {
                $stmt_check =$pdo->prepare("SELECT user_id FROM users WHERE student_faculty_id = ? OR id_number = ?");
                $stmt_check->execute([$id_number,$id_number]);
                
                if ($stmt_check->rowCount() > 0) {$msg = "<div class='alert alert-danger py-2'><small><i class='bi bi-exclamation-triangle-fill me-1'></i> ID Number is already registered!</small></div>";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt_ins =$pdo->prepare("
                        INSERT INTO users (student_faculty_id, id_number, full_name, email, password, role) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt_ins->execute([$id_number, $id_number,$full_name, $email,$hashed_password, $role]);$_SESSION['msg'] = "<div class='alert alert-success py-2'><small><i class='bi bi-check-circle-fill me-1'></i> Registration successful! You can now log in.</small></div>";
                    header("Location: login.php");
                    exit;
                }
            } catch (Exception $e) {$msg = "<div class='alert alert-danger py-2'><small>Error: " . $e->getMessage() . "</small></div>";
            }
        } else {
            $msg = "<div class='alert alert-danger py-2'><small>Database connection not found.</small></div>";
        }
    } else {
        $msg = "<div class='alert alert-warning py-2'><small>Please fill in all required fields.</small></div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Smart Attendance System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS Style -->
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .strength-bar {
            height: 6px;
            border-radius: 3px;
            transition: width 0.3s ease, background-color 0.3s ease;
            width: 0%;
        }
        input::-ms-reveal,
        input::-ms-clear {
            display: none !important;
        }
        .password-toggle-btn {
            cursor: pointer;
            user-select: none;
        }

        /* Divider Design */
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

        /* Google Button Style */
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

<div class="login-card w-100" style="max-width: 480px; border-radius: 20px;">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="badge bg-primary px-3 py-2 rounded-pill small">System Register</span>
        <button id="themeToggleBtn" onclick="toggleTheme()" class="btn btn-sm btn-outline-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;" title="Toggle Theme">
            <i id="themeIcon" class="bi bi-moon-stars-fill"></i>
        </button>
    </div>

    <div class="text-center mb-4">
        <h3 class="fw-bold text-body m-0"><i class="bi bi-person-plus-fill text-primary me-2"></i>Create Account</h3>
        <p class="text-muted small mt-1">Smart Attendance System Registration</p>
    </div>

    <?= $msg ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label fw-bold small text-body">Full Name</label>
            <div class="input-group">
                <span class="input-group-text inner-box border-end-0 text-body"><i class="bi bi-person"></i></span>
                <input type="text" name="full_name" class="form-control border-start-0 inner-box text-body" required placeholder="e.g., Juan dela Cruz">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold small text-body">ID Number (Student / Faculty ID)</label>
            <div class="input-group">
                <span class="input-group-text inner-box border-end-0 text-body"><i class="bi bi-card-heading"></i></span>
                <input type="text" name="id_number" class="form-control border-start-0 inner-box text-body" required placeholder="e.g., 0322-0001">
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold small text-body">Email Address</label>
            <div class="input-group">
                <span class="input-group-text inner-box border-end-0 text-body"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control border-start-0 inner-box text-body" required placeholder="name@example.com">
            </div>
        </div>

        <div class="mb-2">
            <label class="form-label fw-bold small text-body">Password</label>
            <div class="input-group">
                <span class="input-group-text inner-box border-end-0 text-body"><i class="bi bi-lock"></i></span>
                <input type="password" id="passwordInput" name="password" class="form-control border-start-0 border-end-0 inner-box text-body" required placeholder="••••••••">
                <button type="button" class="btn btn-outline-secondary inner-box border-start-0 text-body password-toggle-btn" onclick="togglePasswordVisibility()">
                    <i id="toggleEyeIcon" class="bi bi-eye"></i>
                </button>
            </div>
        </div>

        <div class="mb-3">
            <div class="progress bg-secondary bg-opacity-25 mt-2" style="height: 6px; border-radius: 3px;">
                <div id="strengthBar" class="strength-bar bg-danger"></div>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-1">
                <small class="text-muted" style="font-size: 0.75rem;">Password Strength:</small>
                <small id="strengthText" class="fw-bold text-muted" style="font-size: 0.75rem;">None</small>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold small text-body">Register As</label>
            <select name="role" class="form-select inner-box text-body">
                <option value="student">Student Account</option>
                <option value="faculty">Faculty / Instructor Account</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary w-100 fw-bold py-2 rounded-pill shadow-sm" style="background-color: #6366f1; border: none;">
            <i class="bi bi-check-circle me-1"></i> Sign Up Now
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
        <small class="text-muted">Already have an account? <a href="login.php" class="fw-bold text-primary text-decoration-none">Log in here</a></small>
    </div>
</div>

<script src="assets/js/theme.js"></script>

<script>
    function togglePasswordVisibility() {
        const passInput = document.getElementById('passwordInput');
        const eyeIcon   = document.getElementById('toggleEyeIcon');
        
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

    document.getElementById('passwordInput').addEventListener('input', function() {
        const val = this.value;
        const bar = document.getElementById('strengthBar');
        const text = document.getElementById('strengthText');
        
        let score = 0;
        
        if (!val) {
            bar.style.width = '0%';
            bar.className = 'strength-bar';
            text.textContent = 'None';
            text.className = 'fw-bold text-muted';
            return;
        }

        if (val.length >= 6) score += 1;
        if (val.length >= 10) score += 1;
        if (/[a-z]/.test(val) && /[A-Z]/.test(val)) score += 1;
        if (/[0-9]/.test(val)) score += 1;
        if (/[^a-zA-Z0-9]/.test(val)) score += 1;

        if (score <= 2) {
            bar.style.width = '33%';
            bar.className = 'strength-bar bg-danger';
            text.textContent = 'Weak';
            text.className = 'fw-bold text-danger';
        } else if (score === 3 || score === 4) {
            bar.style.width = '66%';
            bar.className = 'strength-bar bg-warning';
            text.textContent = 'Medium';
            text.className = 'fw-bold text-warning';
        } else {
            bar.style.width = '100%';
            bar.className = 'strength-bar bg-success';
            text.textContent = 'Strong';
            text.className = 'fw-bold text-success';
        }
    });
</script>

</body>
</html>