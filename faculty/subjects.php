<?php
// faculty/subjects.php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../login.php");
    exit;
}

$full_name = $_SESSION['full_name'] ?? 'Faculty User';
$user_id   = $_SESSION['user_id'];

$msg = $_SESSION['msg'] ?? '';
unset($_SESSION['msg']);

$config_file = __DIR__ . '/../config/db_connect.php';
if (file_exists($config_file)) {
    include_once $config_file;
}

// 1. ADD SUBJECT PROCESS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $subject_code   = trim($_POST['subject_code'] ?? '');
    $subject_title  = trim($_POST['subject_title'] ?? '');
    $class_time     = trim($_POST['class_time'] ?? '');     // Start Time
    $class_end_time = trim($_POST['class_end_time'] ?? ''); // End Time
    $join_code      = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

    if (!empty($subject_code) && !empty($subject_title)) {
        if (isset($pdo)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO subjects (faculty_id, subject_code, subject_title, class_time, class_end_time, join_code) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$user_id, $subject_code, $subject_title, $class_time, $class_end_time, $join_code]);
                
                $_SESSION['msg'] = "<div class='alert alert-success py-2 rounded-3 mb-3'><small><i class='bi bi-check-circle-fill me-1'></i> Subject successfully added!</small></div>";
                header("Location: subjects.php");
                exit;
            } catch (Exception $e) {
                $msg = "<div class='alert alert-danger py-2 rounded-3 mb-3'><small>Database Error: " . $e->getMessage() . "</small></div>";
            }
        }
    } else {
        $msg = "<div class='alert alert-warning py-2 rounded-3 mb-3'><small>Please fill in both Subject Code and Title.</small></div>";
    }
}

// 2. EDIT SUBJECT PROCESS
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_subject'])) {
    $edit_id         = $_POST['subject_id'] ?? 0;
    $edit_code       = trim($_POST['subject_code'] ?? '');
    $edit_title      = trim($_POST['subject_title'] ?? '');
    $edit_start_time = trim($_POST['class_time'] ?? '');
    $edit_end_time   = trim($_POST['class_end_time'] ?? '');

    if ($edit_id > 0 && !empty($edit_code) && !empty($edit_title)) {
        if (isset($pdo)) {
            try {
                $stmt_update = $pdo->prepare("
                    UPDATE subjects 
                    SET subject_code = ?, subject_title = ?, class_time = ?, class_end_time = ? 
                    WHERE subject_id = ? AND faculty_id = ?
                ");
                $stmt_update->execute([$edit_code, $edit_title, $edit_start_time, $edit_end_time, $edit_id, $user_id]);

                $_SESSION['msg'] = "<div class='alert alert-success py-2 rounded-3 mb-3'><small><i class='bi bi-check-circle-fill me-1'></i> Subject successfully updated!</small></div>";
                header("Location: subjects.php");
                exit;
            } catch (Exception $e) {
                $msg = "<div class='alert alert-danger py-2 rounded-3 mb-3'><small>Update Error: " . $e->getMessage() . "</small></div>";
            }
        }
    }
}

// 3. DELETE SUBJECT PROCESS
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];
    if (isset($pdo) && $delete_id > 0) {
        try {
            // Burahin muna ang kaugnay na student_subjects at attendance logs kung mayroon
            $pdo->prepare("DELETE FROM student_subjects WHERE subject_id = ?")->execute([$delete_id]);
            $pdo->prepare("DELETE FROM attendance WHERE subject_id = ?")->execute([$delete_id]);

            // Burahin ang mismong subject
            $stmt_del = $pdo->prepare("DELETE FROM subjects WHERE subject_id = ? AND faculty_id = ?");
            $stmt_del->execute([$delete_id, $user_id]);

            $_SESSION['msg'] = "<div class='alert alert-danger py-2 rounded-3 mb-3'><small><i class='bi bi-trash-fill me-1'></i> Subject deleted successfully!</small></div>";
            header("Location: subjects.php");
            exit;
        } catch (Exception $e) {
            $msg = "<div class='alert alert-danger py-2 rounded-3 mb-3'><small>Delete Error: " . $e->getMessage() . "</small></div>";
        }
    }
}

// Kunin ang lahat ng subjects ng Faculty
$faculty_subjects = [];
if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("
            SELECT subject_id, subject_code, subject_title, class_time, class_end_time, join_code 
            FROM subjects 
            WHERE faculty_id = ? 
            ORDER BY subject_code ASC
        ");
        $stmt->execute([$user_id]);
        $faculty_subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $faculty_subjects = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects - Faculty Portal</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container py-4">
    
    <!-- Header Banner -->
    <div class="main-header text-center mb-4">
        <h2 class="fw-bold m-0 d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-journal-bookmark-fill text-primary"></i> Manage Subjects
        </h2>
        <p class="text-muted mb-0 mt-1">Smart Attendance Monitoring System - Faculty Portal</p>
    </div>

    <!-- Navigation Bar -->
    <div class="nav-pill-custom d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 px-3">
        <div class="d-flex gap-2">
            <a href="index.php" class="nav-link-item"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="subjects.php" class="nav-link-item active"><i class="bi bi-journal-bookmark"></i> Manage Subjects</a>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button id="themeToggleBtn" onclick="toggleTheme()" class="btn btn-sm btn-outline-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                <i id="themeIcon" class="bi bi-moon-stars-fill"></i>
            </button>
            <span class="fw-bold me-2 ms-2 text-secondary d-none d-md-inline"><i class="bi bi-person-circle"></i> Prof. <?= htmlspecialchars($full_name) ?></span>
            <a href="../logout.php" class="btn btn-outline-danger btn-sm rounded-pill fw-bold"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>

    <div class="row g-4">
        
        <!-- Add Subject Form Column -->
        <div class="col-lg-4">
            <div class="content-card h-100">
                <h5 class="fw-bold mb-3"><i class="bi bi-plus-circle-fill text-primary me-2"></i>Add New Subject</h5>
                
                <?= $msg ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-body">Subject Code</label>
                        <input type="text" name="subject_code" class="form-control inner-box text-body" required placeholder="e.g., IT 201">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-body">Subject Title / Description</label>
                        <input type="text" name="subject_title" class="form-control inner-box text-body" required placeholder="e.g., Platform Technologies">
                    </div>

                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <label class="form-label fw-bold small text-body">Start Time</label>
                            <input type="time" name="class_time" class="form-control inner-box text-body" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small text-body">End Time</label>
                            <input type="time" name="class_end_time" class="form-control inner-box text-body" required>
                        </div>
                    </div>

                    <button type="submit" name="add_subject" class="btn btn-primary w-100 fw-bold py-2 rounded-pill shadow-sm" style="background-color: #6366f1; border: none;">
                        <i class="bi bi-save me-1"></i> Save Subject
                    </button>
                </form>
            </div>
        </div>

        <!-- Subjects List Column -->
        <div class="col-lg-8">
            <div class="content-card h-100">
                <h5 class="fw-bold mb-3"><i class="bi bi-journals text-primary me-2"></i>Your Handled Subjects List</h5>

                <div class="table-responsive">
                    <table class="table align-middle table-hover">
                        <thead class="table-light">
                            <tr>
                                <th class="text-body">Subject Code</th>
                                <th class="text-body">Subject Title</th>
                                <th class="text-body">Class Schedule</th>
                                <th class="text-body">Join Code</th>
                                <th class="text-center text-body">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($faculty_subjects) > 0): ?>
                                <?php foreach ($faculty_subjects as $sub): ?>
                                    <tr>
                                        <td><span class="badge bg-primary px-3 py-2 fs-6"><?= htmlspecialchars($sub['subject_code']) ?></span></td>
                                        <td class="fw-bold text-body"><?= htmlspecialchars($sub['subject_title']) ?></td>
                                        <td>
                                            <span class="badge bg-dark bg-opacity-75 text-light border border-secondary font-monospace px-3 py-2">
                                                <i class="bi bi-clock me-1 text-primary"></i>
                                                <?= !empty($sub['class_time']) ? date('h:i A', strtotime($sub['class_time'])) : 'TBA' ?>
                                                - 
                                                <?= !empty($sub['class_end_time']) ? date('h:i A', strtotime($sub['class_end_time'])) : 'TBA' ?>
                                            </span>
                                        </td>
                                        <td><span class="badge bg-secondary font-monospace px-3 py-2 fs-6"><?= htmlspecialchars($sub['join_code']) ?></span></td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1 flex-wrap">
                                                <!-- Open Scanner -->
                                                <a href="scanner.php?subject_id=<?= $sub['subject_id'] ?>" class="btn btn-success btn-sm rounded-pill px-2 py-1 fw-bold" title="Scan QR Code">
                                                    <i class="bi bi-qr-code-scan me-1"></i> Scan
                                                </a>

                                                <!-- Per-Subject Live Monitor -->
                                                <a href="attendance_monitor.php?id=<?= $sub['subject_id'] ?>" class="btn btn-primary btn-sm rounded-pill px-2 py-1 fw-bold" title="Live Monitor for this Subject">
                                                    <i class="bi bi-display me-1"></i> Monitor
                                                </a>

                                                <!-- Per-Subject Reports -->
                                                <a href="subject_reports.php?id=<?= $sub['subject_id'] ?>" class="btn btn-info text-dark btn-sm rounded-pill px-2 py-1 fw-bold" title="View Subject Reports">
                                                    <i class="bi bi-file-earmark-bar-graph me-1"></i> Logs
                                                </a>

                                                <!-- Edit Modal Trigger -->
                                                <button class="btn btn-warning btn-sm rounded-pill px-2 py-1 fw-bold text-dark" data-bs-toggle="modal" data-bs-target="#editModal<?= $sub['subject_id'] ?>" title="Edit Subject">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>

                                                <!-- Delete Button -->
                                                <a href="subjects.php?delete_id=<?= $sub['subject_id'] ?>" class="btn btn-danger btn-sm rounded-pill px-2 py-1 fw-bold" onclick="return confirm('Are you sure you want to delete <?= htmlspecialchars($sub['subject_code']) ?>?');" title="Delete Subject">
                                                    <i class="bi bi-trash-fill"></i>
                                                </a>
                                            </div>

                                            <!-- EDIT MODAL FOR THIS SUBJECT -->
                                            <div class="modal fade" id="editModal<?= $sub['subject_id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content text-start inner-box">
                                                        <div class="modal-header border-bottom">
                                                            <h5 class="modal-title fw-bold text-body"><i class="bi bi-pencil-square text-warning me-2"></i>Edit Subject Info</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="subject_id" value="<?= $sub['subject_id'] ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold small text-body">Subject Code</label>
                                                                    <input type="text" name="subject_code" class="form-control inner-box text-body" value="<?= htmlspecialchars($sub['subject_code']) ?>" required>
                                                                </div>

                                                                <div class="mb-3">
                                                                    <label class="form-label fw-bold small text-body">Subject Title</label>
                                                                    <input type="text" name="subject_title" class="form-control inner-box text-body" value="<?= htmlspecialchars($sub['subject_title']) ?>" required>
                                                                </div>

                                                                <div class="row g-2">
                                                                    <div class="col-6">
                                                                        <label class="form-label fw-bold small text-body">Start Time</label>
                                                                        <input type="time" name="class_time" class="form-control inner-box text-body" value="<?= htmlspecialchars($sub['class_time']) ?>" required>
                                                                    </div>
                                                                    <div class="col-6">
                                                                        <label class="form-label fw-bold small text-body">End Time</label>
                                                                        <input type="time" name="class_end_time" class="form-control inner-box text-body" value="<?= htmlspecialchars($sub['class_end_time']) ?>" required>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer border-top">
                                                                <button type="button" class="btn btn-secondary btn-sm rounded-pill fw-bold" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" name="edit_subject" class="btn btn-primary btn-sm rounded-pill fw-bold">Save Changes</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No subjects added yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Bootstrap JS & Custom Theme JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/theme.js"></script>

</body>
</html>