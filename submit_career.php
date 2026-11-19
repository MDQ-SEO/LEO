<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$first_name      = trim($_POST['first_name'] ?? '');
$last_name       = trim($_POST['last_name'] ?? '');
$email           = trim($_POST['email'] ?? '');
$phone           = trim($_POST['phone'] ?? '');
$role_applied    = trim($_POST['role_applied'] ?? '');
$current_company = trim($_POST['current_company'] ?? '');

// Validation
if (empty($first_name)) {
    echo json_encode(['success' => false, 'message' => 'First name is required.']);
    exit;
}
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'A valid email address is required.']);
    exit;
}
if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Phone number is required.']);
    exit;
}
if (empty($role_applied)) {
    echo json_encode(['success' => false, 'message' => 'Please select or specify the role you are applying for.']);
    exit;
}

// CV / Resume (Required)
$cv_path = null;
$cv_name = null;
$full_cv_path = null;

if (!isset($_FILES['cv']) || $_FILES['cv']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Please upload your CV / Resume (PDF, DOC, or DOCX).']);
    exit;
}

$allowed_doc_exts = ['pdf', 'doc', 'docx'];
$cv_file = $_FILES['cv'];
$cv_ext = strtolower(pathinfo($cv_file['name'], PATHINFO_EXTENSION));

if (!in_array($cv_ext, $allowed_doc_exts)) {
    echo json_encode(['success' => false, 'message' => 'Invalid CV file format. Only PDF, DOC, or DOCX files are allowed.']);
    exit;
}

if ($cv_file['size'] > 15 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'CV file size exceeds 15MB limit.']);
    exit;
}

$upload_dir = __DIR__ . '/uploads/careers/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$unique_cv_name = 'cv_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $cv_ext;
$target_cv = $upload_dir . $unique_cv_name;

if (!move_uploaded_file($cv_file['tmp_name'], $target_cv)) {
    echo json_encode(['success' => false, 'message' => 'Failed to upload your CV. Please try again.']);
    exit;
}
$cv_path = 'uploads/careers/' . $unique_cv_name;
$cv_name = basename($cv_file['name']);
$full_cv_path = $target_cv;

// Cover Letter (Optional)
$cover_path = null;
$cover_name = null;
$full_cover_path = null;

if (isset($_FILES['cover_letter']) && $_FILES['cover_letter']['error'] === UPLOAD_ERR_OK) {
    $cl_file = $_FILES['cover_letter'];
    $cl_ext = strtolower(pathinfo($cl_file['name'], PATHINFO_EXTENSION));
    $allowed_cl_exts = ['pdf', 'doc', 'docx', 'txt'];

    if (!in_array($cl_ext, $allowed_cl_exts)) {
        echo json_encode(['success' => false, 'message' => 'Invalid Cover Letter format. Only PDF, DOC, DOCX, or TXT allowed.']);
        exit;
    }

    if ($cl_file['size'] > 15 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Cover Letter file size exceeds 15MB limit.']);
        exit;
    }

    $unique_cl_name = 'cover_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $cl_ext;
    $target_cl = $upload_dir . $unique_cl_name;
    if (move_uploaded_file($cl_file['tmp_name'], $target_cl)) {
        $cover_path = 'uploads/careers/' . $unique_cl_name;
        $cover_name = basename($cl_file['name']);
        $full_cover_path = $target_cl;
    }
}

// 1. Attempt Database Save
$db_saved = false;
try {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO career_submissions 
        (first_name, last_name, email, phone, role_applied, current_company, cover_letter_path, cover_letter_name, cv_path, cv_name, status, ip_address, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'new', ?, NOW())");
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt->execute([
        $first_name,
        $last_name,
        $email,
        $phone,
        $role_applied,
        $current_company,
        $cover_path,
        $cover_name,
        $cv_path,
        $cv_name,
        $ip
    ]);
    $db_saved = true;
} catch (\Throwable $e) {
    error_log("Database error in submit_career.php: " . $e->getMessage());
}

// 2. Attempt Email Delivery
$mail_sent = false;
try {
    $mail_sent = sendCareerEmail([
        'first_name'      => $first_name,
        'last_name'       => $last_name,
        'email'           => $email,
        'phone'           => $phone,
        'role_applied'    => $role_applied,
        'current_company' => $current_company,
        'cv_path'         => $full_cv_path,
        'cv_name'         => $cv_name,
        'cover_path'      => $full_cover_path,
        'cover_name'      => $cover_name
    ]);
} catch (\Throwable $e) {
    error_log("Email sending error in submit_career.php: " . $e->getMessage());
}

// 3. Response: Show success if either DB saved OR Mail sent
if ($db_saved || $mail_sent) {
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your application! We have received your submission and will review it carefully. We will be in touch soon regarding the next steps.'
    ]);
} else {
    // Never show backend/database errors in frontend
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Something went wrong. Please try again later.'
    ]);
}
?>
