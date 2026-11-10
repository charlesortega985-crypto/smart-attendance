<?php
// student/index.php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Siguraduhing student ang naka-login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$full_name = $_SESSION['full_name'] ?? 'Student User';
$user_id   = $_SESSION['user_id'] ?? 0;

$msg = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);

$config_file = __DIR__ . '/../config/db_connect.php';
if (file_exists($config_file)) {
    include_once $config_file;
}

// 1. Kunin ang ID Number at Profile Pic ng Student
$id_number   = $_SESSION['id_number'] ?? '2026-0000';
$profile_pic = null;

if (isset($pdo) && $user_id > 0) {
    try {
        $stmt_user = $pdo->prepare("SELECT profile_pic, COALESCE(student_faculty_id, id_number, user_id) AS student_code FROM users WHERE user_id = ?");
        $stmt_user->execute([$user_id]);
        $user_row = $stmt_user->fetch(PDO::FETCH_ASSOC);
        if ($user_row) {
            $profile_pic = $user_row['profile_pic'] ?? null;
            $id_number   = $user_row['student_code'] ?? $id_number;
        }
    } catch (Exception $e) {
        $profile_pic = null;
    }
}

// 2. Fetch Enrolled Subjects
$enrolled_subjects = [];
if (isset($pdo) && $user_id > 0) {
    try {
        $stmt_sub = $pdo->prepare("
            SELECT s.subject_id, s.subject_code, s.subject_title, s.class_time, s.class_end_time, u.full_name AS prof_name 
            FROM student_subjects ss
            JOIN subjects s ON ss.subject_id = s.subject_id
            JOIN users u ON s.faculty_id = u.user_id
            WHERE ss.student_id = ?
            ORDER BY s.subject_code ASC
        ");
        $stmt_sub->execute([$user_id]);
        $enrolled_subjects = $stmt_sub->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $enrolled_subjects = [];
    }
}

// 3. Fetch Attendance logs mula sa attendance table
$attendance_records = [];
if (isset($pdo) && $user_id > 0) {
    try {
        $stmt_logs = $pdo->prepare("
            SELECT a.scanned_at, a.status, s.subject_code, s.subject_title 
            FROM attendance a
            JOIN subjects s ON a.subject_id = s.subject_id
            WHERE a.student_id = ? 
            ORDER BY a.scanned_at DESC
        ");
        $stmt_logs->execute([$user_id]);
        $attendance_records = $stmt_logs->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $attendance_records = [];
    }
}

$total_scans = count($attendance_records);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Smart Attendance System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- QRCode Generator Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --header-bg: rgba(255, 255, 255, 0.95);
            --card-bg: #ffffff;
            --text-main: #212529;
            --inner-bg: #f8fafc;
            --border-color: #e2e8f0;
            --table-head-bg: #f1f5f9;
        }

        [data-theme="dark"] {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            --header-bg: rgba(30, 41, 59, 0.95);
            --card-bg: #1e293b;
            --text-main: #ffffff;
            --inner-bg: #0f172a;
            --border-color: #334155;
            --table-head-bg: #0f172a;
        }

        body {
            background: var(--bg-gradient);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding-bottom: 40px;
            color: var(--text-main);
            transition: background 0.3s ease, color 0.3s ease;
        }

        [data-theme="dark"] h1, [data-theme="dark"] h2, [data-theme="dark"] h3, 
        [data-theme="dark"] h4, [data-theme="dark"] h5, [data-theme="dark"] h6,
        [data-theme="dark"] p, [data-theme="dark"] span, [data-theme="dark"] small,
        [data-theme="dark"] strong, [data-theme="dark"] b, [data-theme="dark"] label,
        [data-theme="dark"] .text-dark, [data-theme="dark"] .text-muted, [data-theme="dark"] .text-secondary {
            color: #ffffff !important;
        }

        .main-header {
            background: var(--header-bg);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .nav-pill-custom {
            background: var(--header-bg);
            border-radius: 15px;
            padding: 8px;
        }

        .nav-link-item {
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            color: #555;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        [data-theme="dark"] .nav-link-item {
            color: #e2e8f0 !important;
        }

        .nav-link-item.active {
            background: #6366f1;
            color: #ffffff !important;
        }

        .content-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            color: var(--text-main);
        }

        .stat-card {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 16px;
            padding: 20px;
            color: #ffffff !important;
        }

        .stat-card * {
            color: #ffffff !important;
        }

        .inner-box {
            background: var(--inner-bg) !important;
            border: 1px solid var(--border-color) !important;
        }

        .profile-img-header {
            width: 75px;
            height: 75px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #6366f1;
        }

        .profile-icon-fallback {
            width: 75px;
            height: 75px;
            background: #6366f1;
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            margin: 0 auto;
        }

        .table {
            color: var(--text-main) !important;
        }

        [data-theme="dark"] .table {
            --bs-table-bg: transparent;
            --bs-table-color: #ffffff;
        }

        [data-theme="dark"] .table-light {
            background-color: var(--table-head-bg) !important;
            color: #ffffff !important;
        }

        #qrcode img {
            margin: 0 auto;
            padding: 15px;
            background: #ffffff;
            border-radius: 16px;
            border: 2px dashed #6366f1;
        }

        /* Pure Text Time Display without Background */
        .time-text-custom {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-main) !important;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        [data-theme="dark"] .time-text-custom {
            color: #ffffff !important;
        }
    </style>
</head>
<body>

<div class="container py-4">
    
    <!-- Header Banner -->
    <div class="main-header text-center mb-4">
        <h2 class="fw-bold m-0 d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-mortarboard-fill text-primary"></i> Smart Attendance System
        </h2>
        <p class="text-muted mb-0 mt-1">Student Portal & QR Attendance Badge</p>
    </div>

    <!-- Navigation Bar -->
    <div class="nav-pill-custom d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 px-3">
        <div class="d-flex gap-2">
            <a href="index.php" class="nav-link-item active"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="subjects.php" class="nav-link-item"><i class="bi bi-journal-bookmark"></i> Subjects</a>
            <a href="profile.php" class="nav-link-item"><i class="bi bi-person-badge"></i> Profile</a>
        </div>

        <!-- Real-Time Clock Display -->
        <div class="text-center px-3 py-1 rounded shadow-sm border inner-box d-none d-lg-block">
            <span id="liveClock" class="fw-bold text-primary small">--:--:-- --</span>
            <span class="text-muted small mx-1">|</span>
            <span id="liveDate" class="text-muted small">Loading date...</span>
        </div>

        <!-- Theme Toggle & Profile Info -->
        <div class="d-flex align-items-center gap-2">
            <!-- Icon-only Theme Toggle Button -->
            <button id="themeToggleBtn" onclick="toggleTheme()" class="btn btn-sm btn-outline-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;" title="Toggle Theme">
                <i id="themeIcon" class="bi bi-moon-stars-fill"></i>
            </button>
            <span class="fw-bold me-2 ms-2 text-secondary d-none d-md-inline"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($full_name) ?></span>
            <a href="../logout.php" class="btn btn-outline-danger btn-sm rounded-pill fw-bold"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>

    <!-- Main Content Panel -->
    <div class="content-card">
        
        <?= $msg ?>

        <!-- Welcome Banner / Quick Stats -->
        <div class="p-4 rounded-4 inner-box mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h4 class="fw-bold mb-1">Welcome back, <?= htmlspecialchars($full_name) ?>!</h4>
                <p class="text-muted small mb-0">Check your subjects, review attendance logs, and keep your QR badge ready for class.</p>
            </div>
            <div>
                <span class="badge bg-primary px-4 py-2 rounded-pill fs-6">
                    <i class="bi bi-person-badge me-1"></i> ID: <?= htmlspecialchars($id_number) ?>
                </span>
            </div>
        </div>

        <!-- Stat Cards Grid -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card d-flex align-items-center justify-content-between">
                    <div>
                        <h2 class="fw-bold mb-0"><?= count($enrolled_subjects) ?></h2>
                        <small class="font-weight-bold">Enrolled Subjects</small>
                    </div>
                    <i class="bi bi-journal-bookmark fs-1 opacity-75"></i>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat-card d-flex align-items-center justify-content-between">
                    <div>
                        <h2 class="fw-bold mb-0"><?= $total_scans ?></h2>
                        <small class="font-weight-bold">Total Scans Recorded</small>
                    </div>
                    <i class="bi bi-qr-code-scan fs-1 opacity-75"></i>
                </div>
            </div>

            <div class="col-md-4">
                <div class="stat-card d-flex align-items-center justify-content-between">
                    <div>
                        <h2 class="fw-bold mb-0"><?= htmlspecialchars($id_number) ?></h2>
                        <small class="font-weight-bold">Student ID</small>
                    </div>
                    <i class="bi bi-card-heading fs-1 opacity-75"></i>
                </div>
            </div>
        </div>

        <!-- MAIN LAYOUT -->
        <div class="row g-4 mb-4">
            
            <!-- LEFT COLUMN: Profile & QR Badge Card -->
            <div class="col-lg-5">
                <div class="p-4 rounded-4 inner-box text-center h-100 d-flex flex-column justify-content-center">
                    
                    <div class="mb-3">
                        <?php if ($profile_pic && file_exists(__DIR__ . '/../uploads/' . $profile_pic)): ?>
                            <img src="../uploads/<?= htmlspecialchars($profile_pic) ?>" alt="Profile Picture" class="profile-img-header mb-2">
                        <?php else: ?>
                            <div class="profile-icon-fallback mb-2"><i class="bi bi-person-fill"></i></div>
                        <?php endif; ?>
                        <h5 class="fw-bold m-0"><?= htmlspecialchars($full_name) ?></h5>
                        <small class="text-muted fw-bold">ID: <?= htmlspecialchars($id_number) ?></small>
                    </div>

                    <hr class="my-2 opacity-25">

                    <h6 class="fw-bold text-secondary mb-1">Your Attendance QR Badge</h6>
                    <p class="text-muted small mb-2">Show this code to your instructor to scan attendance</p>

                    <div id="qrcode" class="my-2"></div>

                    <div class="mt-2">
                        <span class="badge bg-primary fs-6 px-4 py-2 rounded-pill">
                            <i class="bi bi-qr-code me-1"></i> Ready for Scanning
                        </span>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: Enrolled Subjects Quick View -->
            <div class="col-lg-7">
                <div class="p-4 rounded-4 inner-box h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold m-0"><i class="bi bi-journal-check text-primary me-2"></i>My Enrolled Classes</h5>
                            <a href="subjects.php" class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3">
                                View All Subjects <i class="bi bi-arrow-right ms-1"></i>
                            </a>
                        </div>
                        
                        <div class="d-flex flex-column gap-3" style="max-height: 280px; overflow-y: auto;">
                            <?php if (count($enrolled_subjects) > 0): ?>
                                <?php foreach ($enrolled_subjects as $sub): ?>
                                    <a href="subject_view.php?id=<?= $sub['subject_id'] ?>" class="text-decoration-none">
                                        <div class="p-3 rounded-3 border inner-box d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <span class="badge bg-primary px-2 py-1"><?= htmlspecialchars($sub['subject_code']) ?></span>
                                                    <strong class="fs-6 text-body"><?= htmlspecialchars($sub['subject_title']) ?></strong>
                                                </div>
                                                <small class="text-muted fw-bold">
                                                    <i class="bi bi-person-video3 me-1 text-primary"></i>Prof. <?= htmlspecialchars($sub['prof_name']) ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <span class="time-text-custom">
                                                    <i class="bi bi-clock text-primary"></i>
                                                    <?= !empty($sub['class_time']) ? date('h:i A', strtotime($sub['class_time'])) : 'TBA' ?>
                                                    <?php if(!empty($sub['class_end_time'])): ?>
                                                        - <?= date('h:i A', strtotime($sub['class_end_time'])) ?>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="bi bi-journal-x fs-2 d-block mb-2 text-secondary"></i>
                                    <small>No enrolled subjects yet.</small><br>
                                    <a href="subjects.php" class="btn btn-sm btn-primary mt-2 rounded-pill fw-bold px-3">Join a Class Now</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mt-3 pt-3 border-top text-center">
                        <small class="text-muted">Want to join a new class? Go to the <a href="subjects.php" class="fw-bold text-primary">Subjects Page</a> to enter your join code.</small>
                    </div>
                </div>
            </div>

        </div>

        <!-- Bottom Attendance Logs Section -->
        <div class="p-4 rounded-4 inner-box">
            <h5 class="fw-bold mb-3"><i class="bi bi-clock-history text-primary me-2"></i>Recent Attendance History</h5>
            
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Subject</th>
                            <th>Date & Time Scanned</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($attendance_records) > 0): ?>
                            <?php $count = 1; foreach ($attendance_records as $log): ?>
                                <tr>
                                    <td><strong><?= $count++ ?></strong></td>
                                    <td>
                                        <span class="badge bg-primary px-2 py-1 me-1"><?= htmlspecialchars($log['subject_code'] ?? 'General') ?></span>
                                        <small class="fw-bold text-body"><?= htmlspecialchars($log['subject_title'] ?? '') ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark bg-opacity-75 text-light border border-secondary font-monospace px-3 py-2 fw-normal">
                                            <i class="bi bi-calendar3 me-1 text-primary"></i><?= date('M d, Y', strtotime($log['scanned_at'])) ?>
                                            <span class="text-muted mx-1">|</span>
                                            <i class="bi bi-clock me-1 text-primary"></i><?= date('h:i:s A', strtotime($log['scanned_at'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($log['status'] === 'Present'): ?>
                                            <span class="badge bg-success rounded-pill px-3 py-2"><i class="bi bi-check-circle me-1"></i> Present</span>
                                        <?php elseif ($log['status'] === 'Late'): ?>
                                            <span class="badge bg-warning text-dark rounded-pill px-3 py-2"><i class="bi bi-clock-history me-1"></i> Late</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger rounded-pill px-3 py-2"><i class="bi bi-x-circle me-1"></i> Absent</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary"></i>
                                    <small>No attendance logs recorded yet.</small>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const studentIdVal = "<?= htmlspecialchars($id_number) ?>";
        const qrContainer = document.getElementById("qrcode");
        
        if (qrContainer && studentIdVal) {
            qrContainer.innerHTML = "";
            new QRCode(qrContainer, {
                text: studentIdVal,
                width: 170,
                height: 170,
                colorDark : "#4f46e5",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });
        }
    });
</script>

<!-- Theme JS & Clock JS -->
<script src="../assets/js/theme.js"></script>
<script src="clock.js"></script>

</body>
</html>