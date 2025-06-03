<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if request is AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    die('Direct access not allowed');
}

$course_id = (int)($_GET['id'] ?? 0);

if (!$course_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid course ID']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
    $stmt->execute([$course_id]);
    $course = $stmt->fetch();

    if ($course) {
        echo json_encode([
            'success' => true,
            'course' => $course
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Course not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>