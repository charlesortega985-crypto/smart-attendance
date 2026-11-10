<?php
// complete_google_reg.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['google_reg'])) {
    header("Location: login.php");
    exit;
}

$google_email = $_SESSION['google_reg']['email'];
$google_name  = $_SESSION['google_reg']['full_name'];

$config_file = __DIR__ . '/config/db_connect.php';
if (file_exists($config_file)) {
    include_once $config_file;
}

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_number = trim($_POST['id_number'] ?? '');
    $role      = $_POST['role'] ?? 'student';

    if (!empty($id_number)) {
        if (isset($pdo)) {
            try {
                $stmt_chk = $pdo->prepare("SELECT user_id FROM users WHERE student_faculty_id = ? OR id_number = ?");
                $stmt_chk->execute([$id_number, $id_number]);

                if ($stmt_chk->rowCount() > 0) {
                    $msg = "<div class='alert alert-danger py-2'><small>ID Number is already registered to another account!</small></div>";
                } else {
                    $dummy_pass = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);

                    $stmt_ins = $pdo->prepare("
                        INSERT INTO users (student_faculty_id, id_number, full_name, email, password, role) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt_ins->execute([$id_number, $id_number, $google_name, $google_email, $dummy_pass, $role]);

                    $new_user_id = $pdo->lastInsertId();

                    $_SESSION['user_id']   = $new_user_id;
                    $_SESSION['full_name'] = $google_name;
                    $_SESSION['role']      = $role;
                    $_SESSION['id_number'] = $id_number;

                    unset($_SESSION['google_reg']);

                    if ($role === 'faculty') {
                        header("Location: faculty/index.php");
                    } else {
                        header("Location: student/index.php");
                    }
                    exit;
                }
            } catch (Exception $e) {
                $msg = "<div class='alert alert-danger py-2'><small>Error: " . $e->getMessage() . "</small></div>";
            }
        }
    } else {
        $msg = "<div class='alert alert-warning py-2'><small>Please enter your ID Number.</small></div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Google Account - Smart Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="d-flex align-items-center justify-content-center p-3" style="min-height: 100vh;">

<div class="login-card w-100" style="max-width: 420px; border-radius: 20px;">
    <div class="text-center mb-4">
        <h4 class="fw-bold text-body m-0"><i class="bi bi-google text-primary me-2"></i>One Last Step!</h4>
        <p class="text-muted small mt-1">Link your Google Account (<?= htmlspecialchars($google_email) ?>)</p>
    </div>

    <?= $msg ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label fw-bold small text-body">Full Name (from Google)</label>
            <input type="text" class="form-control inner-box text-body" value="<?= htmlspecialchars($google_name) ?>" readonly>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold small text-body">ID Number (Student / Faculty ID)</label>
            <div class="input-group">
                <span class="input-group-text inner-box border-end-0 text-body"><i class="bi bi-card-heading"></i></span>
                <input type="text" name="id_number" class="form-control border-start-0 inner-box text-body" required placeholder="e.g., 0322-0001">
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold small text-body">Account Role</label>
            <select name="role" class="form-select inner-box text-body">
                <option value="student">Student Account</option>
                <option value="faculty">Faculty Account</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary w-100 fw-bold py-2 rounded-pill shadow-sm" style="background-color: #6366f1; border: none;">
            Complete & Continue <i class="bi bi-arrow-right ms-1"></i>
        </button>
    </form>
</div>

<script src="assets/js/theme.js"></script>
</body>
</html>