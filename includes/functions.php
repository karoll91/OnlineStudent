<?php
/**
 * Online Student Registration System
 * Helper Functions
 */

// Sanitize input data
function clean_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['reg_id']) && !empty($_SESSION['reg_id']);
}

// Redirect if not logged in
function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit();
    }
}

// Redirect if already logged in
function redirect_if_logged_in() {
    if (is_logged_in()) {
        header('Location: dashboard.php');
        exit();
    }
}

// Hash password
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Validate email
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Generate random string
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

// Format date
function format_date($date, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($date));
}

// Show alert message
function show_alert($message, $type = 'info') {
    return "<div class='alert alert-$type alert-dismissible fade show' role='alert'>
                $message
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}

// Calculate quiz score
function calculate_score($correct_answers, $total_questions) {
    if ($total_questions == 0) return 0;
    return round(($correct_answers / $total_questions) * 100, 2);
}

// Time ago function
function time_ago($datetime) {
    $time = time() - strtotime($datetime);

    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';

    return date('M j, Y', strtotime($datetime));
}

// Upload file function
function upload_file($file, $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf']) {
    $upload_dir = UPLOAD_PATH;

    // Create directory if not exists
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Check file type
    if (!in_array($file_extension, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type'];
    }

    // Check file size (5MB max)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'File too large'];
    }

    // Generate unique filename
    $filename = uniqid() . '.' . $file_extension;
    $filepath = $upload_dir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'path' => $filepath];
    } else {
        return ['success' => false, 'message' => 'Upload failed'];
    }
}

// Pagination function
function paginate($total_records, $current_page, $records_per_page = 10) {
    $total_pages = ceil($total_records / $records_per_page);
    $offset = ($current_page - 1) * $records_per_page;

    return [
        'total_pages' => $total_pages,
        'current_page' => $current_page,
        'offset' => $offset,
        'limit' => $records_per_page,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages
    ];
}

// Generate student ID
function generate_student_id() {
    return 'STU' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

// Check if student ID exists
function student_id_exists($student_id, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    return $stmt->fetch() ? true : false;
}

// Get unique student ID
function get_unique_student_id($pdo) {
    do {
        $student_id = generate_student_id();
    } while (student_id_exists($student_id, $pdo));

    return $student_id;
}
?>