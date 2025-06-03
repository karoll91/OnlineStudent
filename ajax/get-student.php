<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if request is AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    die('Direct access not allowed');
}

$student_id = (int)($_GET['id'] ?? 0);

if (!$student_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid student ID']);
    exit();
}

try {
    // Get student details
    $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found']);
        exit();
    }

    // Get student statistics
    $stmt = $pdo->prepare("
        SELECT 
        COUNT(CASE WHEN qa.status = 'completed' THEN 1 END) as completed_quizzes,
        AVG(CASE WHEN qa.status = 'completed' THEN qa.score END) as avg_score,
        MAX(CASE WHEN qa.status = 'completed' THEN qa.score END) as best_score,
        COUNT(DISTINCT ce.course_id) as enrolled_courses
        FROM students s
        LEFT JOIN quiz_attempts qa ON s.id = qa.student_id
        LEFT JOIN course_enrollments ce ON s.id = ce.student_id AND ce.status = 'enrolled'
        WHERE s.id = ?
        GROUP BY s.id
    ");
    $stmt->execute([$student_id]);
    $stats = $stmt->fetch();

    // Get recent quiz attempts
    $stmt = $pdo->prepare("
        SELECT qa.*, q.title as quiz_title, c.course_name
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        LEFT JOIN courses c ON q.course_id = c.id
        WHERE qa.student_id = ? AND qa.status = 'completed'
        ORDER BY qa.start_time DESC
        LIMIT 5
    ");
    $stmt->execute([$student_id]);
    $recent_attempts = $stmt->fetchAll();

    // Generate HTML for modal
    $html = '
    <div class="row">
        <div class="col-md-4 text-center">
            <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px; font-size: 2rem;">
                ' . strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)) . '
            </div>
            <h5>' . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . '</h5>
            <p class="text-muted">' . htmlspecialchars($student['student_id']) . '</p>
        </div>
        <div class="col-md-8">
            <h6>Personal Information</h6>
            <table class="table table-sm">
                <tr><td><strong>Email:</strong></td><td>' . htmlspecialchars($student['email']) . '</td></tr>
                <tr><td><strong>Phone:</strong></td><td>' . htmlspecialchars($student['phone'] ?: 'Not provided') . '</td></tr>
                <tr><td><strong>Date of Birth:</strong></td><td>' . ($student['date_of_birth'] ? date('M d, Y', strtotime($student['date_of_birth'])) : 'Not provided') . '</td></tr>
                <tr><td><strong>Gender:</strong></td><td>' . htmlspecialchars($student['gender'] ?: 'Not specified') . '</td></tr>
                <tr><td><strong>City:</strong></td><td>' . htmlspecialchars($student['city'] ?: 'Not provided') . '</td></tr>
                <tr><td><strong>Joined:</strong></td><td>' . date('M d, Y', strtotime($student['created_at'])) . '</td></tr>
                <tr><td><strong>Status:</strong></td><td><span class="badge bg-' . ($student['is_active'] ? 'success">Active' : 'danger">Inactive') . '</span></td></tr>
            </table>
        </div>
    </div>
    
    <hr>
    
    <div class="row">
        <div class="col-md-6">
            <h6>Academic Statistics</h6>
            <div class="row text-center">
                <div class="col-6">
                    <div class="border rounded p-2">
                        <div class="h5 mb-0 text-success">' . ($stats['completed_quizzes'] ?: 0) . '</div>
                        <small class="text-muted">Completed Quizzes</small>
                    </div>
                </div>
                <div class="col-6">
                    <div class="border rounded p-2">
                        <div class="h5 mb-0 text-info">' . ($stats['enrolled_courses'] ?: 0) . '</div>
                        <small class="text-muted">Enrolled Courses</small>
                    </div>
                </div>
            </div>
            <div class="row text-center mt-2">
                <div class="col-6">
                    <div class="border rounded p-2">
                        <div class="h5 mb-0 text-warning">' . number_format($stats['avg_score'] ?: 0, 1) . '%</div>
                        <small class="text-muted">Average Score</small>
                    </div>
                </div>
                <div class="col-6">
                    <div class="border rounded p-2">
                        <div class="h5 mb-0 text-primary">' . number_format($stats['best_score'] ?: 0, 1) . '%</div>
                        <small class="text-muted">Best Score</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <h6>Recent Quiz Attempts</h6>';

    if (empty($recent_attempts)) {
        $html .= '<p class="text-muted">No quiz attempts yet</p>';
    } else {
        foreach ($recent_attempts as $attempt) {
            $score_class = $attempt['score'] >= 60 ? 'success' : 'warning';
            $html .= '
            <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                <div>
                    <div class="fw-bold small">' . htmlspecialchars($attempt['quiz_title']) . '</div>
                    <small class="text-muted">' . time_ago($attempt['start_time']) . '</small>
                </div>
                <span class="badge bg-' . $score_class . '">' . number_format($attempt['score'], 1) . '%</span>
            </div>';
        }
    }

    $html .= '
        </div>
    </div>';

    if ($student['address']) {
        $html .= '
        <hr>
        <h6>Address</h6>
        <p>' . nl2br(htmlspecialchars($student['address'])) . '</p>';
    }

    echo json_encode([
        'success' => true,
        'html' => $html
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>