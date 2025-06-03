<?php
global $pdo;
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check admin access (simple check)
if (!isset($_SESSION['admin_logged_in'])) {
    die('Access denied');
}

$search = $_GET['search'] ?? '';

// Build query
$where_clause = "WHERE 1=1";
$params = [];

if ($search) {
    $where_clause .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR student_id LIKE ?)";
    $search_term = "%$search%";
    $params = [$search_term, $search_term, $search_term, $search_term];
}

try {
    $stmt = $pdo->prepare("
        SELECT s.*, 
        COUNT(qa.id) as quiz_attempts,
        AVG(qa.score) as avg_score,
        COUNT(ce.id) as enrolled_courses
        FROM students s
        LEFT JOIN quiz_attempts qa ON s.id = qa.student_id AND qa.status = 'completed'
        LEFT JOIN course_enrollments ce ON s.id = ce.student_id AND ce.status = 'enrolled'
        $where_clause
        GROUP BY s.id
        ORDER BY s.created_at DESC
    ");
    $stmt->execute($params);
    $students = $stmt->fetchAll();

    // Set headers for CSV download
    $filename = 'students_export_' . date('Y-m-d_H-i-s') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Create file pointer
    $output = fopen('php://output', 'w');

    // Add CSV headers
    fputcsv($output, [
        'Student ID',
        'First Name',
        'Last Name',
        'Email',
        'Phone',
        'Date of Birth',
        'Gender',
        'City',
        'Quiz Attempts',
        'Average Score',
        'Enrolled Courses',
        'Status',
        'Registration Date'
    ]);

    // Add data rows
    foreach ($students as $student) {
        fputcsv($output, [
            $student['student_id'],
            $student['first_name'],
            $student['last_name'],
            $student['email'],
            $student['phone'] ?: '',
            $student['date_of_birth'] ?: '',
            $student['gender'] ?: '',
            $student['city'] ?: '',
            $student['quiz_attempts'],
            $student['avg_score'] ? number_format($student['avg_score'], 2) . '%' : '',
            $student['enrolled_courses'],
            $student['is_active'] ? 'Active' : 'Inactive',
            date('Y-m-d H:i:s', strtotime($student['created_at']))
        ]);
    }

    fclose($output);

} catch (PDOException $e) {
    die('Export failed: Database error');
}
?>