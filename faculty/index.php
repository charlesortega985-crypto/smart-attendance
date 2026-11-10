<?php
// faculty/index.php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit;
}

$full_name = $_SESSION['full_name'] ?? 'Faculty User';
$user_id   = $_SESSION['user_id'];

$config_file = __DIR__ . '/../config/db_connect.php';
if (file_exists($config_file)) {
    include_once $config_file;
}

// Fetch Faculty Subjects with Enrolled Count
$faculty_subjects = [];
if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("
            SELECT s.subject_id, s.subject_code, s.subject_title, s.class_time, s.class_end_time, s.join_code,
                   (SELECT COUNT(*) FROM student_subjects ss WHERE ss.subject_id = s.subject_id) AS enrolled_count
            FROM subjects s
            WHERE s.faculty_id = ?
            ORDER BY s.subject_code ASC
        ");
        $stmt->execute([$user_id]);
        $faculty_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $faculty_subjects = [];
    }
}

// Fetch Today's Total Attendance Scans across all subjects handled by this faculty
$today_scanned = 0;
$today_date = date('Y-m-d');
if (isset($pdo)) {
    try {
        $stmt_today = $pdo->prepare("
            SELECT COUNT(*) 
            FROM attendance a
            JOIN subjects s ON a.subject_id = s.subject_id
            WHERE s.faculty_id = ? AND DATE(a.scanned_at) = ?
        ");
        $stmt_today->execute([$user_id, $today_date]);
        $today_scanned = $stmt_today->fetchColumn();
    } catch (Exception $e) {
        $today_scanned = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - Smart Attendance System</title>
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
            <i class="bi bi-speedometer2 text-primary"></i> Faculty Dashboard
        </h2>
        <p class="text-muted mb-0 mt-1">Smart Attendance Monitoring System - Overview</p>
    </div>

    <!-- Navigation Bar -->
    <div class="nav-pill-custom d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 px-3">
        <div class="d-flex gap-2">
            <a href="index.php" class="nav-link-item active"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="subjects.php" class="nav-link-item"><i class="bi bi-journal-bookmark"></i> Manage Subjects</a>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button id="themeToggleBtn" onclick="toggleTheme()" class="btn btn-sm btn-outline-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;" title="Toggle Theme">
                <i id="themeIcon" class="bi bi-moon-stars-fill"></i>
            </button>
            <span class="fw-bold me-2 ms-2 text-secondary d-none d-md-inline"><i class="bi bi-person-circle"></i> Prof. <?= htmlspecialchars($full_name) ?></span>
            <a href="../logout.php" class="btn btn-outline-danger btn-sm rounded-pill fw-bold"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>

    <!-- Stats Overview Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="p-4 rounded-4 inner-box border d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted small mb-1">Total Handled Subjects</h6>
                    <h2 class="fw-bold text-body mb-0"><?= count($faculty_subjects) ?></h2>
                </div>
                <div class="rounded-circle bg-primary bg-opacity-10 p-3 text-primary fs-2">
                    <i class="bi bi-journals"></i>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="p-4 rounded-4 inner-box border d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="text-muted small mb-1">Total Scans Recorded Today (<?= date('M d, Y') ?>)</h6>
                    <h2 class="fw-bold text-body mb-0"><?= $today_scanned ?></h2>
                </div>
                <div class="rounded-circle bg-success bg-opacity-10 p-3 text-success fs-2">
                    <i class="bi bi-qr-code-scan"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Access Subject Cards -->
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold m-0"><i class="bi bi-journal-check text-primary me-2"></i>My Active Class Monitors</h5>
            <a href="subjects.php" class="btn btn-sm btn-outline-primary rounded-pill fw-bold">
                <i class="bi bi-plus-lg me-1"></i> Add / Manage Subjects
            </a>
        </div>

        <div class="row g-3">
            <?php if (count($faculty_subjects) > 0): ?>
                <?php foreach ($faculty_subjects as $sub): ?>
                    <div class="col-md-6">
                        <div class="p-4 rounded-4 inner-box border h-100 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-primary px-3 py-2 fs-6"><?= htmlspecialchars($sub['subject_code']) ?></span>
                                    <span class="badge bg-dark bg-opacity-75 text-light border border-secondary font-monospace px-3 py-1">
                                        <i class="bi bi-clock me-1 text-primary"></i>
                                        <?= !empty($sub['class_time']) ? date('h:i A', strtotime($sub['class_time'])) : 'TBA' ?>
                                        - 
                                        <?= !empty($sub['class_end_time']) ? date('h:i A', strtotime($sub['class_end_time'])) : 'TBA' ?>
                                    </span>
                                </div>
                                <h4 class="fw-bold text-body mb-2"><?= htmlspecialchars($sub['subject_title']) ?></h4>
                                <p class="text-muted small mb-3">
                                    <i class="bi bi-people-fill me-1 text-primary"></i> Enrolled: <strong><?= $sub['enrolled_count'] ?> Student(s)</strong> | 
                                    Join Code: <span class="badge bg-secondary font-monospace"><?= htmlspecialchars($sub['join_code']) ?></span>
                                </p>
                            </div>

                            <div class="d-flex gap-2 pt-3 border-top flex-wrap">
                                <a href="scanner.php?subject_id=<?= $sub['subject_id'] ?>" class="btn btn-success btn-sm rounded-pill fw-bold px-3">
                                    <i class="bi bi-qr-code-scan me-1"></i> QR Scanner
                                </a>
                                <a href="attendance_monitor.php?id=<?= $sub['subject_id'] ?>" class="btn btn-primary btn-sm rounded-pill fw-bold px-3">
                                    <i class="bi bi-display me-1"></i> Live Monitor
                                </a>
                                <a href="subject_reports.php?id=<?= $sub['subject_id'] ?>" class="btn btn-outline-secondary btn-sm rounded-pill fw-bold px-3">
                                    <i class="bi bi-file-earmark-bar-graph me-1"></i> Reports Log
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center text-muted py-5">
                    <i class="bi bi-journal-x fs-1 d-block mb-2 text-secondary"></i>
                    <h6>No subjects found.</h6>
                    <small>Go to Manage Subjects to create your first class schedule.</small>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="../assets/js/theme.js"></script>

</body>
</html>