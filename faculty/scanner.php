<?php
// faculty/scanner.php
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
$subject_id = $_GET['subject_id'] ?? 0;

$config_file = __DIR__ . '/../config/db_connect.php';
if (file_exists($config_file)) {
    include_once $config_file;
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qr_code'])) {
    header('Content-Type: application/json');
    $scanned_code = trim($_POST['qr_code']); 

    try {
        $stmt_user = $pdo->prepare("
            SELECT user_id, full_name 
            FROM users 
            WHERE TRIM(student_faculty_id) = ? OR TRIM(id_number) = ? OR TRIM(user_id) = ? OR TRIM(full_name) = ?
            LIMIT 1
        ");
        $stmt_user->execute([$scanned_code, $scanned_code, $scanned_code, $scanned_code]);
        $student = $stmt_user->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid QR Code: Student not found!']);
            exit;
        }

        $student_id_val = $student['user_id'];
        $today = date('Y-m-d');
        $current_datetime = date('Y-m-d H:i:s');

        // --- COMPUTATION GAMIT ANG START AT END TIME ---
        $status = 'Present';
        
        if (!empty($subject_info['class_time']) && !empty($subject_info['class_end_time'])) {
            $class_start = strtotime($today . ' ' . $subject_info['class_time']);
            $class_end   = strtotime($today . ' ' . $subject_info['class_end_time']);
            $now         = time();

            // 1. Kung nag-scan bago ang Start Time o pagkatapos ng End Time -> ABSENT
            if ($now < $class_start || $now >= $class_end) {
                $status = 'Absent';
            }
            // 2. Kung nag-scan sa loob ng unang 15 minutes mula sa Start Time -> PRESENT
            elseif ($now <= ($class_start + (15 * 60))) {
                $status = 'Present';
            }
            // 3. Kung nag-scan lagpas ng 15 mins hanggang bago mag End Time -> LATE
            else {
                $status = 'Late';
            }
        }

        // Check kung na-scan na ngayong araw
        $stmt_check = $pdo->prepare("SELECT attendance_id FROM attendance WHERE subject_id = ? AND student_id = ? AND DATE(scanned_at) = ?");
        $stmt_check->execute([$subject_id, $student_id_val, $today]);
        $existing_att = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($existing_att) {
            $stmt_update = $pdo->prepare("UPDATE attendance SET status = ?, scanned_at = ? WHERE attendance_id = ?");
            $stmt_update->execute([$status, $current_datetime, $existing_att['attendance_id']]);

            $badge_type = ($status === 'Present') ? 'success' : (($status === 'Late') ? 'warning' : 'danger');
            
            echo json_encode([
                'status' => $badge_type, 
                'message' => 'Already Scanned Today: ' . htmlspecialchars($student['full_name']) . ' (Recorded as ' . strtoupper($status) . ')'
            ]);
            exit;
        }

        $stmt_insert = $pdo->prepare("INSERT INTO attendance (subject_id, student_id, status, scanned_at) VALUES (?, ?, ?, ?)");
        $stmt_insert->execute([$subject_id, $student_id_val, $status, $current_datetime]);

        $badge_type = ($status === 'Present') ? 'success' : (($status === 'Late') ? 'warning' : 'danger');
        
        echo json_encode([
            'status' => $badge_type, 
            'message' => 'Attendance Recorded: ' . htmlspecialchars($student['full_name']) . ' (Recorded as ' . strtoupper($status) . ')'
        ]);
        exit;

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Server Error: ' . $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Scanner - <?= htmlspecialchars($subject_info['subject_code']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        #reader { border: none !important; }
        #reader span, #reader div, #reader label { color: #ffffff !important; font-weight: 500; }
        #reader select {
            background-color: #0f172a !important;
            color: #ffffff !important;
            border: 1px solid #6366f1 !important;
            padding: 6px 12px;
            border-radius: 8px;
            margin: 5px 0;
        }
        #reader button {
            background-color: #6366f1 !important;
            color: #ffffff !important;
            border: none !important;
            padding: 6px 16px !important;
            border-radius: 20px !important;
            font-weight: 600 !important;
            margin: 5px !important;
        }
        .scan-msg-box { color: #0f172a !important; font-weight: 700 !important; }
    </style>
</head>
<body>

<div class="container py-4">
    
    <div class="main-header text-center mb-4">
        <h2 class="fw-bold m-0 d-flex align-items-center justify-content-center gap-2">
            <i class="bi bi-qr-code-scan text-primary"></i> QR Attendance Scanner
        </h2>
        <p class="text-muted mb-0 mt-1"><?= htmlspecialchars($subject_info['subject_code']) ?> - <?= htmlspecialchars($subject_info['subject_title']) ?> (Sched: <?= !empty($subject_info['class_time']) ? date('h:i A', strtotime($subject_info['class_time'])) : 'N/A' ?> - <?= !empty($subject_info['class_end_time']) ? date('h:i A', strtotime($subject_info['class_end_time'])) : 'N/A' ?>)</p>
    </div>

    <div class="nav-pill-custom d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3 px-3">
        <div class="d-flex gap-2">
            <a href="index.php" class="nav-link-item"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="subjects.php" class="nav-link-item"><i class="bi bi-journal-bookmark"></i> Manage Subjects</a>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button id="themeToggleBtn" onclick="toggleTheme()" class="btn btn-sm btn-outline-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                <i id="themeIcon" class="bi bi-moon-stars-fill"></i>
            </button>
            <span class="fw-bold me-2 ms-2 text-secondary d-none d-md-inline"><i class="bi bi-person-circle"></i> Prof. <?= htmlspecialchars($full_name) ?></span>
            <a href="../logout.php" class="btn btn-outline-danger btn-sm rounded-pill fw-bold"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="content-card text-center">
                <h5 class="fw-bold mb-3"><i class="bi bi-camera-fill text-primary me-2"></i>Scan Student QR Code</h5>
                
                <div id="scanResult" class="mb-3">
                    <div class="alert alert-info py-2 rounded-3 mb-0 scan-msg-box"><i class="bi bi-info-circle me-1"></i> Point camera to a student's QR code.</div>
                </div>

                <div class="p-3 border rounded-4 inner-box overflow-hidden mb-3">
                    <div id="reader" style="width: 100%; min-height: 280px;"></div>
                </div>

                <a href="subjects.php" class="btn btn-outline-primary rounded-pill px-4 fw-bold">
                    <i class="bi bi-arrow-left me-1"></i> Back to Subjects
                </a>
            </div>
        </div>
    </div>
</div>

<script src="../assets/js/theme.js"></script>

<script>
let lastScannedCode = "";
let isProcessing = false;

function onScanSuccess(decodedText, decodedResult) {
    if (isProcessing || decodedText === lastScannedCode) {
        return; 
    }

    isProcessing = true;
    lastScannedCode = decodedText;

    const resultDiv = document.getElementById('scanResult');
    resultDiv.innerHTML = `<div class="alert alert-secondary py-2 rounded-3 scan-msg-box"><i class="bi bi-hourglass-split me-1"></i> Processing QR Code...</div>`;

    const formData = new FormData();
    formData.append('qr_code', decodedText);

    fetch('scanner.php?subject_id=<?= $subject_id ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        let alertClass = 'alert-success';
        if (data.status === 'warning') alertClass = 'alert-warning';
        if (data.status === 'danger' || data.status === 'error') alertClass = 'alert-danger';

        resultDiv.innerHTML = `<div class="alert ${alertClass} py-2 rounded-3 scan-msg-box"><i class="bi bi-info-circle-fill me-1"></i> ${data.message}</div>`;

        setTimeout(() => {
            isProcessing = false;
            lastScannedCode = "";
        }, 3000);
    })
    .catch(error => {
        resultDiv.innerHTML = `<div class="alert alert-danger py-2 rounded-3 scan-msg-box">Network or Server error!</div>`;
        setTimeout(() => {
            isProcessing = false;
            lastScannedCode = "";
        }, 3000);
    });
}

const html5QrcodeScanner = new Html5QrcodeScanner(
    "reader", 
    { fps: 10, qrbox: { width: 220, height: 220 } },
    false
);
html5QrcodeScanner.render(onScanSuccess);
</script>

</body>
</html>