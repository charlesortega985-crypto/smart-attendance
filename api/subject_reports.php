<?php
// faculty/subject_reports.php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

date_default_timezone_set('Asia/Manila');

// Siguraduhing faculty ang naka-login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit;
}

$full_name  = $_SESSION['full_name'] ?? 'Faculty User';
$user_id    = $_SESSION['user_id'];
$subject_id = $_GET['id'] ?? 0;

$config_file = __DIR__ . '/../config/db_connect.php';
if (file_exists($config_file)) {
    include_once $config_file;
}

// Kunin ang detalye ng piling subject
$subject_info = null;
if (isset($pdo) && $subject_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE subject_id = ? AND faculty_id = ?");
    $stmt->execute([$subject_id, $user_id]);
    $subject_info = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$subject_info) {
    header("Location: index.php");
    exit;
}

// Kunin ang lahat ng attendance logs ng subject
$attendance_logs = [];
if (isset($pdo)) {
    try {
        $stmt_logs = $pdo->prepare("
            SELECT a.attendance_id, a.status, a.scanned_at, u.full_name, 
                   COALESCE(u.student_faculty_id, u.id_number, u.user_id) AS id_number 
            FROM attendance a
            JOIN users u ON a.student_id = u.user_id
            WHERE a.subject_id = ?
            ORDER BY a.scanned_at DESC
        ");
        $stmt_logs->execute([$subject_id]);
        $attendance_logs = $stmt_logs->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $attendance_logs = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Reports - <?= htmlspecialchars($subject_info['subject_code']) ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS Style -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container py-4">
    
    <!-- Header Banner -->
    <div class="main-header text-center mb-4">
        <h2 class="fw-bold m-0 d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-file-earmark-bar-graph-fill text-primary"></i> Subject Attendance Reports
        </h2>
        <p class="text-muted mb-0 mt-1"><?= htmlspecialchars($subject_info['subject_code']) ?> - <?= htmlspecialchars($subject_info['subject_title']) ?></p>
    </div>

    <!-- Navigation Bar -->
    <div class="nav-pill-custom d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 px-3">
        <div class="d-flex gap-2">
            <a href="index.php" class="nav-link-item"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="subjects.php" class="nav-link-item"><i class="bi bi-journal-bookmark"></i> Subjects</a>
            <a href="attendance_monitor.php?id=<?= $subject_id ?>" class="nav-link-item"><i class="bi bi-display"></i> Live Monitor</a>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button id="themeToggleBtn" onclick="toggleTheme()" class="btn btn-sm btn-outline-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;" title="Toggle Theme">
                <i id="themeIcon" class="bi bi-moon-stars-fill"></i>
            </button>
            <span class="fw-bold me-2 ms-2 text-secondary d-none d-md-inline"><i class="bi bi-person-circle"></i> Prof. <?= htmlspecialchars($full_name) ?></span>
            <a href="../logout.php" class="btn btn-outline-danger btn-sm rounded-pill fw-bold"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>

    <!-- Attendance Log History Table -->
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="fw-bold m-0"><i class="bi bi-journal-text text-primary me-2"></i>Attendance Log History</h5>
            <div class="d-flex gap-2">
                <a href="attendance_monitor.php?id=<?= $subject_id ?>" class="btn btn-primary btn-sm rounded-pill px-3 fw-bold">
                    <i class="bi bi-display me-1"></i> Live Monitor
                </a>
                <a href="scanner.php?subject_id=<?= $subject_id ?>" class="btn btn-success btn-sm rounded-pill px-3 fw-bold">
                    <i class="bi bi-qr-code-scan me-1"></i> Open QR Scanner
                </a>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle table-hover">
                <thead class="table-light">
                    <tr>
                        <th class="text-body">#</th>
                        <th class="text-body">Student Name</th>
                        <th class="text-body">Student ID</th>
                        <th class="text-body">Status</th>
                        <th class="text-body">Date & Time Scanned</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($attendance_logs) > 0): ?>
                        <?php $num = 1; foreach ($attendance_logs as $log): ?>
                            <tr>
                                <td class="text-body small"><?= $num++ ?></td>
                                <td class="fw-bold text-body"><?= htmlspecialchars($log['full_name']) ?></td>
                                <td><span class="badge bg-secondary font-monospace px-2 py-1"><?= htmlspecialchars($log['id_number']) ?></span></td>
                                <td>
                                    <?php if ($log['status'] === 'Present'): ?>
                                        <span class="badge bg-success px-3 py-1 rounded-pill"><i class="bi bi-check-circle me-1"></i> Present</span>
                                    <?php elseif ($log['status'] === 'Late'): ?>
                                        <span class="badge bg-warning text-dark px-3 py-1 rounded-pill"><i class="bi bi-clock-history me-1"></i> Late</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger px-3 py-1 rounded-pill"><i class="bi bi-x-circle me-1"></i> Absent</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-dark bg-opacity-75 text-light border border-secondary font-monospace px-3 py-2 fw-normal">
                                        <i class="bi bi-calendar3 me-1 text-primary"></i><?= date('M d, Y', strtotime($log['scanned_at'])) ?>
                                        <span class="text-muted mx-1">|</span>
                                        <i class="bi bi-clock me-1 text-primary"></i><?= date('h:i:s A', strtotime($log['scanned_at'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-journal-x fs-1 d-block mb-2 text-secondary"></i>
                                <h6>No Attendance Records Found</h6>
                                <small>No students have scanned their QR code for this subject yet.</small>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Theme JS -->
<script src="../assets/js/theme.js"></script>

</body>
</html>