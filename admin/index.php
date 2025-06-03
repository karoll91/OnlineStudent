<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Simple admin check (you can improve this)
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Get dashboard statistics
try {
    // Total students
    $stmt = $pdo->query("SELECT COUNT(*) FROM students WHERE is_active = 1");
    $total_students = $stmt->fetchColumn();

    // Total courses
    $stmt = $pdo->query("SELECT COUNT(*) FROM courses WHERE is_active = 1");
    $total_courses = $stmt->fetchColumn();

    // Total quizzes
    $stmt = $pdo->query("SELECT COUNT(*) FROM quizzes WHERE is_active = 1");
    $total_quizzes = $stmt->fetchColumn();

    // Quiz attempts this month
    $stmt = $pdo->query("SELECT COUNT(*) FROM quiz_attempts WHERE MONTH(start_time) = MONTH(CURDATE())");
    $monthly_attempts = $stmt->fetchColumn();

    // Recent students
    $stmt = $pdo->query("
        SELECT student_id, first_name, last_name, email, created_at 
        FROM students 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recent_students = $stmt->fetchAll();

    // Recent quiz attempts
    $stmt = $pdo->query("
        SELECT qa.*, s.first_name, s.last_name, q.title as quiz_title
        FROM quiz_attempts qa
        JOIN students s ON qa.student_id = s.id
        JOIN quizzes q ON qa.quiz_id = q.id
        WHERE qa.status = 'completed'
        ORDER BY qa.start_time DESC
        LIMIT 5
    ");
    $recent_attempts = $stmt->fetchAll();

    // Top performing students
    $stmt = $pdo->query("
        SELECT s.first_name, s.last_name, s.student_id, AVG(qa.score) as avg_score, COUNT(qa.id) as total_attempts
        FROM students s
        JOIN quiz_attempts qa ON s.id = qa.student_id
        WHERE qa.status = 'completed'
        GROUP BY s.id
        HAVING total_attempts >= 3
        ORDER BY avg_score DESC
        LIMIT 5
    ");
    $top_students = $stmt->fetchAll();

} catch (PDOException $e) {
    $total_students = $total_courses = $total_quizzes = $monthly_attempts = 0;
    $recent_students = $recent_attempts = $top_students = [];
}

$page_title = 'Admin Dashboard';
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            color: white;
            overflow-y: auto;
            z-index: 1000;
        }

        .admin-main {
            margin-left: 250px;
            min-height: 100vh;
            background: #f8f9fa;
        }

        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: rgba(255,255,255,0.1);
            color: white;
        }

        .sidebar-menu i {
            width: 20px;
            margin-right: 10px;
        }

        .admin-navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 2rem;
            margin-bottom: 2rem;
        }

        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #3498db;
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
        }

        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .stats-icon {
            font-size: 3rem;
            opacity: 0.3;
        }

        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
<!-- Sidebar -->
<div class="admin-sidebar">
    <div class="sidebar-header">
        <h4><i class="fas fa-graduation-cap me-2"></i>Admin Panel</h4>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="index.php" class="active">
                <i class="fas fa-tachometer-alt"></i>Dashboard
            </a>
        </li>
        <li>
            <a href="students.php">
                <i class="fas fa-users"></i>Students
            </a>
        </li>
        <li>
            <a href="courses.php">
                <i class="fas fa-book"></i>Courses
            </a>
        </li>
        <li>
            <a href="quizzes.php">
                <i class="fas fa-clipboard-list"></i>Quizzes
            </a>
        </li>
        <li>
            <a href="reports.php">
                <i class="fas fa-chart-bar"></i>Reports
            </a>
        </li>
        <li>
            <a href="settings.php">
                <i class="fas fa-cog"></i>Settings
            </a>
        </li>
        <li>
            <a href="../index.php">
                <i class="fas fa-home"></i>View Site
            </a>
        </li>
        <li>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i>Logout
            </a>
        </li>
    </ul>
</div>

<!-- Main Content -->
<div class="admin-main">
    <!-- Top Navigation -->
    <div class="admin-navbar">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h2>
            <div class="d-flex align-items-center">
                <span class="me-3">Welcome, Admin</span>
                <img src="https://via.placeholder.com/40" alt="Admin" class="rounded-circle">
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?= $total_students ?></div>
                            <div class="stats-label">Total Students</div>
                        </div>
                        <div class="stats-icon text-primary">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?= $total_courses ?></div>
                            <div class="stats-label">Active Courses</div>
                        </div>
                        <div class="stats-icon text-success">
                            <i class="fas fa-book"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?= $total_quizzes ?></div>
                            <div class="stats-label">Total Quizzes</div>
                        </div>
                        <div class="stats-icon text-info">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stats-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stats-number"><?= $monthly_attempts ?></div>
                            <div class="stats-label">Monthly Attempts</div>
                        </div>
                        <div class="stats-icon text-warning">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Students -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Recent Students</h5>
                        <a href="students.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_students)): ?>
                            <p class="text-muted text-center">No students yet</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Joined</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($recent_students as $student): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($student['student_id']) ?></td>
                                            <td><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></td>
                                            <td><?= htmlspecialchars($student['email']) ?></td>
                                            <td><?= time_ago($student['created_at']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Quiz Attempts -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Recent Quiz Attempts</h5>
                        <a href="reports.php" class="btn btn-sm btn-success">View Reports</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_attempts)): ?>
                            <p class="text-muted text-center">No quiz attempts yet</p>
                        <?php else: ?>
                            <?php foreach ($recent_attempts as $attempt): ?>
                                <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-check"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?= htmlspecialchars($attempt['first_name'] . ' ' . $attempt['last_name']) ?></h6>
                                        <small class="text-muted d-block"><?= htmlspecialchars($attempt['quiz_title']) ?></small>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge bg-success">Score: <?= number_format($attempt['score'], 1) ?>%</span>
                                            <small class="text-muted"><?= time_ago($attempt['start_time']) ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Performing Students -->
        <?php if (!empty($top_students)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Top Performing Students</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Average Score</th>
                                        <th>Total Attempts</th>
                                        <th>Performance</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($top_students as $index => $student): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-warning">#<?= $index + 1 ?></span>
                                            </td>
                                            <td><?= htmlspecialchars($student['student_id']) ?></td>
                                            <td><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></td>
                                            <td>
                                                <span class="fw-bold text-success"><?= number_format($student['avg_score'], 1) ?>%</span>
                                            </td>
                                            <td><?= $student['total_attempts'] ?></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-success" style="width: <?= $student['avg_score'] ?>%">
                                                        <?= number_format($student['avg_score'], 1) ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-refresh dashboard every 2 minutes
    setInterval(function() {
        location.reload();
    }, 120000);
</script>
</body>
</html>