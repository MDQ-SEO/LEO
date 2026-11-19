<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

$first_name = trim($_POST['first_name'] ?? '');
$last_name  = trim($_POST['last_name'] ?? '');
$email      = trim($_POST['email'] ?? '');
$phone      = trim($_POST['phone'] ?? '');
$subject    = trim($_POST['subject'] ?? '');
$message    = trim($_POST['message'] ?? '');

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
if (empty($subject)) {
    echo json_encode(['success' => false, 'message' => 'Subject is required.']);
    exit;
}
if (empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Message is required.']);
    exit;
}

// File Attachment handling
$attachment_path = null;
$attachment_name = null;
$full_attachment_path = null;

if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['attachment'];
    $allowed_exts = ['pdf', 'doc', 'docx', 'txt', 'png', 'jpg', 'jpeg', 'zip'];
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_ext, $allowed_exts)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file format. Allowed formats: PDF, DOC, DOCX, TXT, PNG, JPG, ZIP.']);
        exit;
    }

    if ($file['size'] > 15 * 1024 * 1024) { // 15MB limit
        echo json_encode(['success' => false, 'message' => 'File size exceeds maximum limit of 15MB.']);
        exit;
    }

    $upload_dir = __DIR__ . '/uploads/contacts/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $unique_name = 'contact_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $file_ext;
    $target_file = $upload_dir . $unique_name;

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        $attachment_path = 'uploads/contacts/' . $unique_name;
        $attachment_name = basename($file['name']);
        $full_attachment_path = $target_file;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save attached file.']);
        exit;
    }
}

// 1. Attempt Database Save
$db_saved = false;
try {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO contact_submissions 
        (first_name, last_name, email, phone, subject, message, attachment_path, attachment_name, status, ip_address, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'new', ?, NOW())");
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $stmt->execute([
        $first_name,
        $last_name,
        $email,
        $phone,
        $subject,
        $message,
        $attachment_path,
        $attachment_name,
        $ip
    ]);
    $db_saved = true;
} catch (\Throwable $e) {
    error_log("Database error in submit_contact.php: " . $e->getMessage());
}

// 2. Attempt Email Delivery
$mail_sent = false;
try {
    $mail_sent = sendContactEmail([
        'first_name'      => $first_name,
        'last_name'       => $last_name,
        'email'           => $email,
        'phone'           => $phone,
        'subject'         => $subject,
        'message'         => $message,
        'attachment_path' => $full_attachment_path,
        'attachment_name' => $attachment_name
    ]);
} catch (\Throwable $e) {
    error_log("Email sending error in submit_contact.php: " . $e->getMessage());
}

// 3. Response: Show success if either DB saved OR Mail sent
if ($db_saved || $mail_sent) {
    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your message has been received. Our team will get in touch with you shortly.'
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
