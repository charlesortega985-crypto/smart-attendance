<?php
// student/subjects.php
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

// Fetch Enrolled Subjects
$enrolled_subjects = [];
if (isset($pdo) && $user_id > 0) {
    try {
        $stmt_sub = $pdo->prepare("
            SELECT s.subject_id, s.subject_code, s.subject_title, s.class_time, s.class_end_time, u.full_name AS prof_name, u.email AS prof_email 
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Subjects - Smart Attendance System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS Style -->
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
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
        <p class="text-muted mb-0 mt-1">Student Course & Subject Portal</p>
    </div>

    <!-- Navigation Bar -->
    <div class="nav-pill-custom d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 px-3">
        <div class="d-flex gap-2">
            <a href="index.php" class="nav-link-item"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="subjects.php" class="nav-link-item active"><i class="bi bi-journal-bookmark"></i> Subjects</a>
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
            <button id="themeToggleBtn" onclick="toggleTheme()" class="btn btn-sm btn-outline-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;" title="Toggle Theme">
                <i id="themeIcon" class="bi bi-moon-stars-fill"></i>
            </button>
            <span class="fw-bold me-2 ms-2 text-secondary d-none d-md-inline"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($full_name) ?></span>
            <a href="../logout.php" class="btn btn-outline-danger btn-sm rounded-pill fw-bold"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>

    <!-- Content Panel -->
    <div class="content-card">
        
        <?= $msg ?>

        <!-- Join Class Form Section -->
        <div class="p-4 rounded-4 inner-box mb-4">
            <h5 class="fw-bold mb-2"><i class="bi bi-plus-circle-fill text-primary me-2"></i>Join a New Subject Class</h5>
            <p class="text-muted small mb-3">Ask your professor for the 6-character class code to enroll in their subject.</p>
            
            <form method="POST" action="join_subject.php" class="row g-2 align-items-center">
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text inner-box border-end-0 text-body"><i class="bi bi-key-fill"></i></span>
                        <input type="text" name="join_code" class="form-control border-start-0 inner-box text-body" placeholder="Enter Class Code (e.g., ABK2P9)" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="submit" name="join_subject" class="btn btn-success w-100 fw-bold py-2 rounded-pill shadow-sm">
                        <i class="bi bi-check-lg me-1"></i> Enroll Subject
                    </button>
                </div>
            </form>
        </div>

        <!-- Joined Subjects Grid View -->
        <h5 class="fw-bold mb-3"><i class="bi bi-journal-check text-primary me-2"></i>My Enrolled Subjects (<?= count($enrolled_subjects) ?>)</h5>
        
        <div class="row g-3">
            <?php if (count($enrolled_subjects) > 0): ?>
                <?php foreach ($enrolled_subjects as $sub): ?>
                    <div class="col-md-6">
                        <div class="p-4 rounded-4 inner-box h-100 d-flex flex-column justify-content-between border">
                            <div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-primary px-3 py-2 fs-6"><?= htmlspecialchars($sub['subject_code']) ?></span>
                                    <span class="time-text-custom">
                                        <i class="bi bi-clock text-primary me-1"></i>
                                        <?= !empty($sub['class_time']) ? date('h:i A', strtotime($sub['class_time'])) : 'TBA' ?>
                                        <?php if(!empty($sub['class_end_time'])): ?>
                                            - <?= date('h:i A', strtotime($sub['class_end_time'])) ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <h4 class="fw-bold text-body mb-1"><?= htmlspecialchars($sub['subject_title']) ?></h4>
                                <p class="text-muted small mb-3">
                                    <i class="bi bi-person-video3 me-1 text-primary"></i><strong>Instructor:</strong> Prof. <?= htmlspecialchars($sub['prof_name']) ?>
                                </p>
                            </div>

                            <div class="pt-3 border-top d-flex justify-content-between align-items-center">
                                <small class="text-muted"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($sub['prof_email'] ?? 'No email listed') ?></small>
                                <a href="subject_view.php?id=<?= $sub['subject_id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill fw-bold px-3">
                                    View Class <i class="bi bi-chevron-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center text-muted py-5">
                    <i class="bi bi-journal-x fs-1 d-block mb-2 text-secondary"></i>
                    <small>No enrolled subjects yet. Use the code form above to add your classes!</small>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script src="../assets/js/theme.js"></script>
<script src="clock.js"></script>

</body>
</html>