<?php
// student/profile.php
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

// Fetch Student Profile Info
$user_info = null;
if (isset($pdo) && $user_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT user_id, full_name, email, role, created_at, profile_pic, COALESCE(student_faculty_id, id_number, user_id) AS student_code FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user_info = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $user_info = null;
    }
}

$id_number   = $user_info['student_code'] ?? ($_SESSION['id_number'] ?? '2026-0000');
$email       = !empty($user_info['email']) ? $user_info['email'] : 'Not provided';
$created_at  = !empty($user_info['created_at']) ? date('F d, Y', strtotime($user_info['created_at'])) : 'N/A';
$profile_pic = $user_info['profile_pic'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - Smart Attendance System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS Style -->
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        .profile-img-large {
            width: 110px;
            height: 110px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #6366f1;
        }
        .profile-icon-fallback-large {
            width: 110px;
            height: 110px;
            background: #6366f1;
            color: #ffffff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.2rem;
            margin: 0 auto;
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
        <p class="text-muted mb-0 mt-1">Student Account Profile & Details</p>
    </div>

    <!-- Navigation Bar -->
    <div class="nav-pill-custom d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 px-3">
        <div class="d-flex gap-2">
            <a href="index.php" class="nav-link-item"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="subjects.php" class="nav-link-item"><i class="bi bi-journal-bookmark"></i> Subjects</a>
            <a href="profile.php" class="nav-link-item active"><i class="bi bi-person-badge"></i> Profile</a>
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

    <!-- Content Panel -->
    <div class="content-card">
        
        <?= $msg ?>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <!-- Profile Avatar Card -->
                <div class="text-center mb-4">
                    <div class="position-relative d-inline-block mb-3">
                        <?php if ($profile_pic && file_exists(__DIR__ . '/../uploads/' . $profile_pic)): ?>
                            <img src="../uploads/<?= htmlspecialchars($profile_pic) ?>" alt="Profile Picture" class="profile-img-large shadow">
                        <?php else: ?>
                            <div class="profile-icon-fallback-large shadow"><i class="bi bi-person-fill"></i></div>
                        <?php endif; ?>
                        
                        <label for="profile_upload" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle p-2 shadow" style="cursor: pointer; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;" title="Upload Picture">
                            <i class="bi bi-camera-fill"></i>
                        </label>
                    </div>

                    <!-- Hidden Profile Form -->
                    <form id="uploadForm" action="upload_profile.php" method="POST" enctype="multipart/form-data" class="d-none">
                        <input type="file" id="profile_upload" name="profile_pic" accept="image/*" onchange="document.getElementById('uploadForm').submit();">
                    </form>

                    <h3 class="fw-bold text-body m-0"><?= htmlspecialchars($full_name) ?></h3>
                    <span class="badge bg-primary px-3 py-1 rounded-pill mt-2">
                        <i class="bi bi-shield-check me-1"></i> Student Account
                    </span>
                </div>

                <!-- Account Information Grid -->
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 rounded-4 inner-box border">
                            <small class="text-muted fw-bold d-block mb-1 text-uppercase">Student ID Number</small>
                            <h6 class="fw-bold text-body mb-0 d-flex align-items-center gap-2">
                                <i class="bi bi-card-heading text-primary"></i> <?= htmlspecialchars($id_number) ?>
                            </h6>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 rounded-4 inner-box border">
                            <small class="text-muted fw-bold d-block mb-1 text-uppercase">Email Address</small>
                            <h6 class="fw-bold text-body mb-0 d-flex align-items-center gap-2">
                                <i class="bi bi-envelope text-primary"></i> <?= htmlspecialchars($email) ?>
                            </h6>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 rounded-4 inner-box border">
                            <small class="text-muted fw-bold d-block mb-1 text-uppercase">User Role</small>
                            <h6 class="fw-bold text-body mb-0 d-flex align-items-center gap-2">
                                <i class="bi bi-person-badge text-primary"></i> Student
                            </h6>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 rounded-4 inner-box border">
                            <small class="text-muted fw-bold d-block mb-1 text-uppercase">Registration Date</small>
                            <h6 class="fw-bold text-body mb-0 d-flex align-items-center gap-2">
                                <i class="bi bi-calendar-check text-primary"></i> <?= htmlspecialchars($created_at) ?>
                            </h6>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm" style="background-color: #6366f1; border: none;">
                        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                    </a>
                </div>

            </div>
        </div>

    </div>
</div>

<script src="../assets/js/theme.js"></script>
<script src="clock.js"></script>

</body>
</html>