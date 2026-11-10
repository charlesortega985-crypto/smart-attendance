<?php
// student/join_subject.php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$config_file = __DIR__ . '/../config/db_connect.php';
if (file_exists($config_file)) {
    include_once $config_file;
}

$user_id = $_SESSION['user_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_subject']) && $user_id > 0) {
    $code = strtoupper(trim($_POST['join_code']));
    
    if (!empty($code) && isset($pdo)) {
        try {
            $stmt_find = $pdo->prepare("SELECT subject_id, subject_code FROM subjects WHERE join_code = ? LIMIT 1");
            $stmt_find->execute([$code]);
            $subject = $stmt_find->fetch(PDO::FETCH_ASSOC);

            if ($subject) {
                $stmt_enroll = $pdo->prepare("INSERT INTO student_subjects (student_id, subject_id) VALUES (?, ?)");
                $stmt_enroll->execute([$user_id, $subject['subject_id']]);
                $_SESSION['msg'] = "<div class='alert alert-success py-2 mb-3'><small><i class='bi bi-check-circle-fill me-1'></i> Successfully joined class: <strong>" . htmlspecialchars($subject['subject_code']) . "</strong>!</small></div>";
            } else {
                $_SESSION['msg'] = "<div class='alert alert-danger py-2 mb-3'><small><i class='bi bi-exclamation-triangle-fill me-1'></i> Invalid Class Join Code.</small></div>";
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $_SESSION['msg'] = "<div class='alert alert-warning py-2 mb-3'><small><i class='bi bi-info-circle-fill me-1'></i> You are already enrolled in this class.</small></div>";
            } else {
                $_SESSION['msg'] = "<div class='alert alert-danger py-2 mb-3'><small>An error occurred while joining the class.</small></div>";
            }
        }
    }
}

header("Location: index.php");
exit;