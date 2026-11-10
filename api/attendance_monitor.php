<?php
// faculty/attendance_monitor.php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

date_default_timezone_set('Asia/Manila');

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

// Fetch Subject Details
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

// 1. FETCH ALL ENROLLED STUDENTS & THEIR LATEST TODAY ATTENDANCE
$enrolled_students = [];
if (isset($pdo)) {
    try {
        $stmt_students = $pdo->prepare("
            SELECT u.user_id, u.full_name, 
                   COALESCE(u.student_faculty_id, u.id_number, u.user_id) AS id_number
            FROM student_subjects ss
            JOIN users u ON ss.student_id = u.user_id
            WHERE ss.subject_id = ?
            ORDER BY u.full_name ASC
        ");
        $stmt_students->execute([$subject_id]);
        $enrolled_students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $enrolled_students = [];
    }
}

// 2. FETCH ALL TODAY LOGS FOR THIS SUBJECT
$today_date = date('Y-m-d');
$today_logs = [];
if (isset($pdo)) {
    try {
        $stmt_logs = $pdo->prepare("
            SELECT attendance_id, student_id, status, scanned_at 
            FROM attendance 
            WHERE subject_id = ? AND DATE(scanned_at) = ?
            ORDER BY scanned_at DESC
        ");
        $stmt_logs->execute([$subject_id, $today_date]);
        $today_logs = $stmt_logs->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $today_logs = [];
    }
}

// 3. MAP ATTENDANCE STATUS TO EACH ENROLLED STUDENT
$count_present = 0;
$count_late    = 0;
$count_absent  = 0;

foreach ($enrolled_students as &$st) {
    $st['today_status'] = 'Not Scanned Yet';
    $st['scanned_time'] = '--:--:--';

    foreach ($today_logs as $log) {
        // I-check kung nag-ma-match sa user_id O sa id_number (student_faculty_id)
        if ($log['student_id'] == $st['user_id'] || trim($log['student_id']) === trim($st['id_number'])) {
            $st['today_status'] = $log['status'];
            $st['scanned_time'] = date('h:i:s A', strtotime($log['scanned_at']));
            break;
        }
    }

    if ($st['today_status'] === 'Present') $count_present++;
    elseif ($st['today_status'] === 'Late') $count_late++;
    elseif ($st['today_status'] === 'Absent') $count_absent++;
}
unset($st);

$total_enrolled = count($enrolled_students);
$total_scanned  = $count_present + $count_late + $count_absent;
$scanned_percentage = ($total_enrolled > 0) ? round(($total_scanned / $total_enrolled) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Monitor - <?= htmlspecialchars($subject_info['subject_code']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container py-4">
    
    <div class="main-header text-center mb-4">
        <h2 class="fw-bold m-0 d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-display text-primary"></i> Live Class Attendance Monitor
        </h2>
        <p class="text-muted mb-0 mt-1"><?= htmlspecialchars($subject_info['subject_code']) ?> - <?= htmlspecialchars($subject_info['subject_title']) ?> (Sched: <?= !empty($subject_info['class_time']) ? date('h:i A', strtotime($subject_info['class_time'])) : 'N/A' ?> - <?= !empty($subject_info['class_end_time']) ? date('h:i A', strtotime($subject_info['class_end_time'])) : 'N/A' ?>)</p>
    </div>

    <div class="nav-pill-custom d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 px-3">
        <div class="d-flex gap-2">
            <a href="index.php" class="nav-link-item"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="subjects.php" class="nav-link-item"><i class="bi bi-journal-bookmark"></i> Subjects</a>
            <a href="subject_reports.php?id=<?= $subject_id ?>" class="nav-link-item"><i class="bi bi-file-earmark-bar-graph"></i> Reports Log</a>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button id="themeToggleBtn" onclick="toggleTheme()" class="btn btn-sm btn-outline-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                <i id="themeIcon" class="bi bi-moon-stars-fill"></i>
            </button>
            <span class="fw-bold me-2 ms-2 text-secondary d-none d-md-inline"><i class="bi bi-person-circle"></i> Prof. <?= htmlspecialchars($full_name) ?></span>
            <a href="../logout.php" class="btn btn-outline-danger btn-sm rounded-pill fw-bold"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="p-3 rounded-4 inner-box border d-flex align-items-center gap-3">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3 text-primary fs-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div>
                    <h6 class="text-muted small mb-0">Total Enrolled</h6>
                    <h4 class="fw-bold text-body mb-0"><?= $total_enrolled ?> Students</h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="p-3 rounded-4 inner-box border d-flex align-items-center gap-3">
                <div class="rounded-circle bg-success bg-opacity-10 p-3 text-success fs-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div>
                    <h6 class="text-muted small mb-0">Present Today</h6>
                    <h4 class="fw-bold text-body mb-0"><?= $count_present ?></h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="p-3 rounded-4 inner-box border d-flex align-items-center gap-3">
                <div class="rounded-circle bg-warning bg-opacity-10 p-3 text-warning fs-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div>
                    <h6 class="text-muted small mb-0">Late Today</h6>
                    <h4 class="fw-bold text-body mb-0"><?= $count_late ?></h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="p-3 rounded-4 inner-box border d-flex align-items-center gap-3">
                <div class="rounded-circle bg-danger bg-opacity-10 p-3 text-danger fs-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-x-circle-fill"></i>
                </div>
                <div>
                    <h6 class="text-muted small mb-0">Absent Today</h6>
                    <h4 class="fw-bold text-body mb-0"><?= $count_absent ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Progress Bar -->
    <div class="content-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="fw-bold m-0"><i class="bi bi-speedometer text-primary me-2"></i>Class Turnout Progress (<?= date('M d, Y') ?>)</h6>
            <span class="badge bg-primary rounded-pill px-3 py-1 fs-6"><?= $scanned_percentage ?>% Scanned</span>
        </div>
        <div class="progress" style="height: 14px; border-radius: 10px;">
            <div class="progress-bar bg-success" role="progressbar" style="width: <?= $scanned_percentage ?>%" aria-valuenow="<?= $scanned_percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
    </div>

    <!-- Student Table -->
    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="fw-bold m-0"><i class="bi bi-person-lines-fill text-primary me-2"></i>Enrolled Students Real-Time Status</h5>
            <div class="d-flex gap-2">
                <a href="scanner.php?subject_id=<?= $subject_id ?>" class="btn btn-success btn-sm rounded-pill px-3 fw-bold">
                    <i class="bi bi-qr-code-scan me-1"></i> Open QR Scanner
                </a>
                <button onclick="location.reload();" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold">
                    <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle table-hover">
                <thead class="table-light">
                    <tr>
                        <th class="text-body">#</th>
                        <th class="text-body">Student Name</th>
                        <th class="text-body">Student ID</th>
                        <th class="text-body text-center">Today's Status</th>
                        <th class="text-body text-center">Time Scanned</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($enrolled_students) > 0): ?>
                        <?php $n = 1; foreach ($enrolled_students as $st): ?>
                            <tr>
                                <td class="text-body small"><?= $n++ ?></td>
                                <td class="fw-bold text-body"><?= htmlspecialchars($st['full_name']) ?></td>
                                <td><span class="badge bg-secondary font-monospace px-2 py-1"><?= htmlspecialchars($st['id_number']) ?></span></td>
                                <td class="text-center">
                                    <?php if ($st['today_status'] === 'Present'): ?>
                                        <span class="badge bg-success px-3 py-1 rounded-pill"><i class="bi bi-check-circle me-1"></i> Present</span>
                                    <?php elseif ($st['today_status'] === 'Late'): ?>
                                        <span class="badge bg-warning text-dark px-3 py-1 rounded-pill"><i class="bi bi-clock-history me-1"></i> Late</span>
                                    <?php elseif ($st['today_status'] === 'Absent'): ?>
                                        <span class="badge bg-danger px-3 py-1 rounded-pill"><i class="bi bi-x-circle me-1"></i> Absent</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-50 text-light px-3 py-1 rounded-pill"><i class="bi bi-dash-circle me-1"></i> Not Scanned Yet</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-dark bg-opacity-75 text-light border border-secondary font-monospace px-3 py-1 fw-normal">
                                        <?= $st['scanned_time'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">No enrolled students yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="../assets/js/theme.js"></script>

</body>
</html>