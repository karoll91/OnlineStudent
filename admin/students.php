<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check admin login
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Handle actions
$message = '';
$error = '';

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $student_id = (int)($_GET['id'] ?? 0);

    if ($action === 'activate' && $student_id) {
        try {
            $stmt = $pdo->prepare("UPDATE students SET is_active = 1 WHERE id = ?");
            $stmt->execute([$student_id]);
            $message = 'Student activated successfully.';
        } catch (PDOException $e) {
            $error = 'Failed to activate student.';
        }
    } elseif ($action === 'deactivate' && $student_id) {
        try {
            $stmt = $pdo->prepare("UPDATE students SET is_active = 0 WHERE id = ?");
            $stmt->execute([$student_id]);
            $message = 'Student deactivated successfully.';
        } catch (PDOException $e) {
            $error = 'Failed to deactivate student.';
        }
    } elseif ($action === 'delete' && $student_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
            $message = 'Student deleted successfully.';
        } catch (PDOException $e) {
            $error = 'Failed to delete student.';
        }
    }
}

// Search and pagination
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$where_clause = "WHERE 1=1";
$params = [];

if ($search) {
    $where_clause .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR student_id LIKE ?)";
    $search_term = "%$search%";
    $params = [$search_term, $search_term, $search_term, $search_term];
}

// Get total count
$count_query = "SELECT COUNT(*) FROM students $where_clause";
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_students = $stmt->fetchColumn();

// Get students
$query = "
    SELECT s.*, 
    COUNT(qa.id) as quiz_attempts,
    AVG(qa.score) as avg_score
    FROM students s
    LEFT JOIN quiz_attempts qa ON s.id = qa.student_id AND qa.status = 'completed'
    $where_clause
    GROUP BY s.id
    ORDER BY s.created_at DESC
    LIMIT $per_page OFFSET $offset
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Calculate pagination
$total_pages = ceil($total_students / $per_page);

$page_title = 'Students Management';
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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

        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .table th {
            border-top: none;
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, #3498db, #2980b9);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
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
        <li><a href="index.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a></li>
        <li><a href="students.php" class="active"><i class="fas fa-users"></i>Students</a></li>
        <li><a href="courses.php"><i class="fas fa-book"></i>Courses</a></li>
        <li><a href="quizzes.php"><i class="fas fa-clipboard-list"></i>Quizzes</a></li>
        <li><a href="reports.php"><i class="fas fa-chart-bar"></i>Reports</a></li>
        <li><a href="settings.php"><i class="fas fa-cog"></i>Settings</a></li>
        <li><a href="../index.php"><i class="fas fa-home"></i>View Site</a></li>
        <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
    </ul>
</div>

<!-- Main Content -->
<div class="admin-main">
    <!-- Top Navigation -->
    <div class="admin-navbar">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-users me-2"></i>Students Management</h2>
            <div class="d-flex align-items-center">
                <span class="me-3">Welcome, Admin</span>
                <img src="https://via.placeholder.com/40" alt="Admin" class="rounded-circle">
            </div>
        </div>
    </div>

    <div class="container-fluid">

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?= $message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Search and Actions -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <form method="GET" class="d-flex">
                            <input type="text" class="form-control me-2" name="search"
                                   value="<?= htmlspecialchars($search) ?>"
                                   placeholder="Search students...">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="btn-group">
                            <button class="btn btn-success" onclick="exportStudents()">
                                <i class="fas fa-download me-1"></i>Export CSV
                            </button>
                            <button class="btn btn-info" onclick="window.print()">
                                <i class="fas fa-print me-1"></i>Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    All Students
                    <span class="badge bg-primary ms-2"><?= $total_students ?> total</span>
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($students)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users text-muted" style="font-size: 4rem;"></i>
                        <h5 class="text-muted mt-3">No students found</h5>
                        <?php if ($search): ?>
                            <p class="text-muted">Try adjusting your search criteria</p>
                            <a href="students.php" class="btn btn-primary">View All Students</a>
                        <?php else: ?>
                            <p class="text-muted">Students will appear here once they register</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                            <tr>
                                <th>Student</th>
                                <th>Student ID</th>
                                <th>Contact</th>
                                <th>Quiz Stats</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="student-avatar me-3">
                                                <?= strtoupper(substr($student['first_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></div>
                                                <?php if ($student['date_of_birth']): ?>
                                                    <small class="text-muted">Born: <?= date('M d, Y', strtotime($student['date_of_birth'])) ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($student['student_id']) ?></span>
                                    </td>
                                    <td>
                                        <div>
                                            <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($student['email']) ?>
                                        </div>
                                        <?php if ($student['phone']): ?>
                                            <div class="text-muted">
                                                <i class="fas fa-phone me-1"></i><?= htmlspecialchars($student['phone']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div>
                                            <span class="badge bg-info"><?= $student['quiz_attempts'] ?> attempts</span>
                                        </div>
                                        <?php if ($student['avg_score']): ?>
                                            <small class="text-muted">Avg: <?= number_format($student['avg_score'], 1) ?>%</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($student['is_active']): ?>
                                            <span class="status-badge status-active">Active</span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= time_ago($student['created_at']) ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="viewStudent(<?= $student['id'] ?>)" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($student['is_active']): ?>
                                                <a href="?action=deactivate&id=<?= $student['id'] ?>"
                                                   class="btn btn-outline-warning"
                                                   onclick="return confirm('Deactivate this student?')"
                                                   title="Deactivate">
                                                    <i class="fas fa-pause"></i>
                                                </a>
                                            <?php else: ?>
                                                <a href="?action=activate&id=<?= $student['id'] ?>"
                                                   class="btn btn-outline-success"
                                                   title="Activate">
                                                    <i class="fas fa-play"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="?action=delete&id=<?= $student['id'] ?>"
                                               class="btn btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to delete this student? This action cannot be undone.')"
                                               title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>

                        <div class="text-center text-muted">
                            Showing <?= $offset + 1 ?> to <?= min($offset + $per_page, $total_students) ?> of <?= $total_students ?> students
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Student Details Modal -->
<div class="modal fade" id="studentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Student Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="studentModalBody">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function viewStudent(studentId) {
        // Load student details via AJAX
        fetch(`../ajax/get-student.php?id=${studentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('studentModalBody').innerHTML = data.html;
                    new bootstrap.Modal(document.getElementById('studentModal')).show();
                } else {
                    alert('Failed to load student details');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading student details');
            });
    }

    function exportStudents() {
        const search = '<?= htmlspecialchars($search) ?>';
        const url = `../ajax/export-students.php?search=${encodeURIComponent(search)}`;
        window.location.href = url;
    }

    // Auto-hide alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            new bootstrap.Alert(alert).close();
        });
    }, 5000);
</script>
</body>
</html>