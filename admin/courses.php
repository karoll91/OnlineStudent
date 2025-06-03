<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check admin login
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_course') {
        $course_code = clean_input($_POST['course_code'] ?? '');
        $course_name = clean_input($_POST['course_name'] ?? '');
        $description = clean_input($_POST['description'] ?? '');
        $credits = (int)($_POST['credits'] ?? 3);
        $instructor = clean_input($_POST['instructor'] ?? '');

        if (empty($course_code) || empty($course_name)) {
            $error = 'Course code and name are required.';
        } else {
            try {
                // Check if course code exists
                $stmt = $pdo->prepare("SELECT id FROM courses WHERE course_code = ?");
                $stmt->execute([$course_code]);
                if ($stmt->fetch()) {
                    $error = 'Course code already exists.';
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO courses (course_code, course_name, description, credits, instructor, created_at) 
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$course_code, $course_name, $description, $credits, $instructor]);
                    $message = 'Course added successfully.';
                }
            } catch (PDOException $e) {
                $error = 'Failed to add course.';
            }
        }
    } elseif ($action === 'edit_course') {
        $course_id = (int)($_POST['course_id'] ?? 0);
        $course_code = clean_input($_POST['course_code'] ?? '');
        $course_name = clean_input($_POST['course_name'] ?? '');
        $description = clean_input($_POST['description'] ?? '');
        $credits = (int)($_POST['credits'] ?? 3);
        $instructor = clean_input($_POST['instructor'] ?? '');

        if ($course_id && !empty($course_code) && !empty($course_name)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE courses 
                    SET course_code = ?, course_name = ?, description = ?, credits = ?, instructor = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$course_code, $course_name, $description, $credits, $instructor, $course_id]);
                $message = 'Course updated successfully.';
            } catch (PDOException $e) {
                $error = 'Failed to update course.';
            }
        }
    }
}

// Handle actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $course_id = (int)($_GET['id'] ?? 0);

    if ($action === 'activate' && $course_id) {
        try {
            $stmt = $pdo->prepare("UPDATE courses SET is_active = 1 WHERE id = ?");
            $stmt->execute([$course_id]);
            $message = 'Course activated successfully.';
        } catch (PDOException $e) {
            $error = 'Failed to activate course.';
        }
    } elseif ($action === 'deactivate' && $course_id) {
        try {
            $stmt = $pdo->prepare("UPDATE courses SET is_active = 0 WHERE id = ?");
            $stmt->execute([$course_id]);
            $message = 'Course deactivated successfully.';
        } catch (PDOException $e) {
            $error = 'Failed to deactivate course.';
        }
    } elseif ($action === 'delete' && $course_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
            $stmt->execute([$course_id]);
            $message = 'Course deleted successfully.';
        } catch (PDOException $e) {
            $error = 'Failed to delete course. Make sure no quizzes are associated with this course.';
        }
    }
}

// Get courses with statistics
try {
    $stmt = $pdo->query("
        SELECT c.*, 
        COUNT(DISTINCT ce.student_id) as enrolled_students,
        COUNT(DISTINCT q.id) as total_quizzes
        FROM courses c
        LEFT JOIN course_enrollments ce ON c.id = ce.course_id AND ce.status = 'enrolled'
        LEFT JOIN quizzes q ON c.id = q.course_id AND q.is_active = 1
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $courses = $stmt->fetchAll();
} catch (PDOException $e) {
    $courses = [];
}

$page_title = 'Courses Management';
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

        .course-card {
            transition: transform 0.3s ease;
            border-left: 4px solid #3498db;
        }

        .course-card:hover {
            transform: translateY(-5px);
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
        <li><a href="students.php"><i class="fas fa-users"></i>Students</a></li>
        <li><a href="courses.php" class="active"><i class="fas fa-book"></i>Courses</a></li>
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
            <h2><i class="fas fa-book me-2"></i>Courses Management</h2>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                <i class="fas fa-plus me-2"></i>Add New Course
            </button>
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

        <!-- Courses Grid -->
        <?php if (empty($courses)): ?>
            <div class="text-center py-5">
                <i class="fas fa-book text-muted" style="font-size: 4rem;"></i>
                <h5 class="text-muted mt-3">No courses found</h5>
                <p class="text-muted">Start by adding your first course</p>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                    <i class="fas fa-plus me-2"></i>Add First Course
                </button>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($courses as $course): ?>
                    <div class="col-lg-6 col-xl-4 mb-4">
                        <div class="card course-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><?= htmlspecialchars($course['course_code']) ?></h6>
                                <?php if ($course['is_active']): ?>
                                    <span class="status-badge status-active">Active</span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">Inactive</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($course['course_name']) ?></h5>
                                <p class="card-text text-muted"><?= htmlspecialchars(substr($course['description'] ?? '', 0, 100)) ?>...</p>

                                <div class="row text-center mb-3">
                                    <div class="col-4">
                                        <div class="border rounded p-2">
                                            <div class="h6 mb-0 text-primary"><?= $course['credits'] ?></div>
                                            <small class="text-muted">Credits</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border rounded p-2">
                                            <div class="h6 mb-0 text-success"><?= $course['enrolled_students'] ?></div>
                                            <small class="text-muted">Students</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="border rounded p-2">
                                            <div class="h6 mb-0 text-info"><?= $course['total_quizzes'] ?></div>
                                            <small class="text-muted">Quizzes</small>
                                        </div>
                                    </div>
                                </div>

                                <?php if ($course['instructor']): ?>
                                    <div class="mb-3">
                                        <small class="text-muted">
                                            <i class="fas fa-user-tie me-1"></i>Instructor: <?= htmlspecialchars($course['instructor']) ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="btn-group w-100">
                                    <button class="btn btn-outline-primary btn-sm" onclick="editCourse(<?= $course['id'] ?>)" title="Edit Course">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($course['is_active']): ?>
                                        <a href="?action=deactivate&id=<?= $course['id'] ?>"
                                           class="btn btn-outline-warning btn-sm"
                                           onclick="return confirm('Deactivate this course?')"
                                           title="Deactivate">
                                            <i class="fas fa-pause"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="?action=activate&id=<?= $course['id'] ?>"
                                           class="btn btn-outline-success btn-sm"
                                           title="Activate">
                                            <i class="fas fa-play"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="?action=delete&id=<?= $course['id'] ?>"
                                       class="btn btn-outline-danger btn-sm"
                                       onclick="return confirm('Are you sure you want to delete this course?')"
                                       title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Course Modal -->
<div class="modal fade" id="addCourseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_course">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="course_code" class="form-label">Course Code *</label>
                            <input type="text" class="form-control" id="course_code" name="course_code" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="credits" class="form-label">Credits</label>
                            <input type="number" class="form-control" id="credits" name="credits" value="3" min="1" max="10">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="course_name" class="form-label">Course Name *</label>
                        <input type="text" class="form-control" id="course_name" name="course_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="instructor" class="form-label">Instructor</label>
                        <input type="text" class="form-control" id="instructor" name="instructor">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Add Course
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Course Modal -->
<div class="modal fade" id="editCourseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Course</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body" id="editCourseForm">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Course
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function editCourse(courseId) {
        // Get course data and populate edit form
        fetch(`../ajax/get-course.php?id=${courseId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('editCourseForm').innerHTML = `
                            <input type="hidden" name="action" value="edit_course">
                            <input type="hidden" name="course_id" value="${data.course.id}">

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="edit_course_code" class="form-label">Course Code *</label>
                                    <input type="text" class="form-control" id="edit_course_code" name="course_code" value="${data.course.course_code}" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="edit_credits" class="form-label">Credits</label>
                                    <input type="number" class="form-control" id="edit_credits" name="credits" value="${data.course.credits}" min="1" max="10">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="edit_course_name" class="form-label">Course Name *</label>
                                <input type="text" class="form-control" id="edit_course_name" name="course_name" value="${data.course.course_name}" required>
                            </div>

                            <div class="mb-3">
                                <label for="edit_instructor" class="form-label">Instructor</label>
                                <input type="text" class="form-control" id="edit_instructor" name="instructor" value="${data.course.instructor || ''}">
                            </div>

                            <div class="mb-3">
                                <label for="edit_description" class="form-label">Description</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="4">${data.course.description || ''}</textarea>
                            </div>
                        `;
                    new bootstrap.Modal(document.getElementById('editCourseModal')).show();
                } else {
                    alert('Failed to load course details');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading course details');
            });
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