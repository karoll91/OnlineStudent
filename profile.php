<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Require login
require_login();

$student_id = $_SESSION['reg_id'];
$message = '';
$error = '';

// Handle enrollment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $course_id = (int)($_POST['course_id'] ?? 0);

    if ($action === 'enroll' && $course_id) {
        try {
            // Check if already enrolled
            $stmt = $pdo->prepare("SELECT id FROM course_enrollments WHERE student_id = ? AND course_id = ?");
            $stmt->execute([$student_id, $course_id]);

            if ($stmt->fetch()) {
                $error = 'You are already enrolled in this course.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO course_enrollments (student_id, course_id, enrollment_date) VALUES (?, ?, NOW())");
                $stmt->execute([$student_id, $course_id]);
                $message = 'Successfully enrolled in the course!';
            }
        } catch (PDOException $e) {
            $error = 'Failed to enroll in course.';
        }
    } elseif ($action === 'unenroll' && $course_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM course_enrollments WHERE student_id = ? AND course_id = ?");
            $stmt->execute([$student_id, $course_id]);
            $message = 'Successfully unenrolled from the course.';
        } catch (PDOException $e) {
            $error = 'Failed to unenroll from course.';
        }
    }
}

// Get all courses with enrollment status
try {
    $stmt = $pdo->prepare("
        SELECT c.*, 
        ce.id as enrollment_id,
        ce.enrollment_date,
        COUNT(DISTINCT ce2.student_id) as total_students,
        COUNT(DISTINCT q.id) as total_quizzes
        FROM courses c
        LEFT JOIN course_enrollments ce ON c.id = ce.course_id AND ce.student_id = ?
        LEFT JOIN course_enrollments ce2 ON c.id = ce2.course_id AND ce2.status = 'enrolled'
        LEFT JOIN quizzes q ON c.id = q.course_id AND q.is_active = 1
        WHERE c.is_active = 1
        GROUP BY c.id
        ORDER BY c.course_name
    ");
    $stmt->execute([$student_id]);
    $courses = $stmt->fetchAll();

    // Get enrolled courses count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM course_enrollments WHERE student_id = ? AND status = 'enrolled'");
    $stmt->execute([$student_id]);
    $enrolled_count = $stmt->fetchColumn();

} catch (PDOException $e) {
    $courses = [];
    $enrolled_count = 0;
}

$page_title = 'Courses';
include 'includes/header.php';
?>

    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col">
            <div class="bg-info text-white p-4 rounded">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2><i class="fas fa-book me-2"></i>Available Courses</h2>
                        <p class="mb-0">Explore and enroll in courses that interest you. Expand your knowledge and skills!</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="h4 mb-0"><?= $enrolled_count ?> Enrolled</div>
                        <small>Active Enrollments</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
        <h5 class="text-muted mt-3">No courses available</h5>
        <p class="text-muted">Check back later for new courses!</p>
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($courses as $course): ?>
            <div class="col-lg-6 col-xl-4 mb-4">
                <div class="card h-100 border-0 shadow-sm">
                    <?php if ($course['enrollment_id']): ?>
                        <div class="card-header bg-success text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-check-circle me-1"></i>Enrolled</span>
                                <small><?= time_ago($course['enrollment_date']) ?></small>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge bg-primary"><?= htmlspecialchars($course['course_code']) ?></span>
                            <span class="badge bg-secondary"><?= $course['credits'] ?> Credits</span>
                        </div>

                        <h5 class="card-title"><?= htmlspecialchars($course['course_name']) ?></h5>

                        <p class="card-text text-muted">
                            <?= htmlspecialchars(substr($course['description'] ?? '', 0, 150)) ?>
                            <?= strlen($course['description'] ?? '') > 150 ? '...' : '' ?>
                        </p>

                        <?php if ($course['instructor']): ?>
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="fas fa-user-tie me-1"></i>
                                    Instructor: <?= htmlspecialchars($course['instructor']) ?>
                                </small>
                            </div>
                        <?php endif; ?>

                        <div class="row text-center mb-3">
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="h6 mb-0 text-primary"><?= $course['total_students'] ?></div>
                                    <small class="text-muted">Students</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="border rounded p-2">
                                    <div class="h6 mb-0 text-info"><?= $course['total_quizzes'] ?></div>
                                    <small class="text-muted">Quizzes</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-transparent">
                        <?php if ($course['enrollment_id']): ?>
                            <div class="d-grid gap-2">
                                <a href="quiz.php?course_id=<?= $course['id'] ?>" class="btn btn-success">
                                    <i class="fas fa-play me-2"></i>Take Quizzes
                                </a>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="unenroll">
                                    <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm w-100"
                                            onclick="return confirm('Are you sure you want to unenroll from this course?')">
                                        <i class="fas fa-times me-1"></i>Unenroll
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="enroll">
                                <input type="hidden" name="course_id" value="<?= $course['id'] ?>">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-2"></i>Enroll Now
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

    <!-- Enrolled Courses Section -->
<?php if ($enrolled_count > 0): ?>
    <div class="row mt-5">
        <div class="col-12">
            <h4><i class="fas fa-graduation-cap me-2"></i>My Enrolled Courses</h4>
            <hr>
        </div>
    </div>

    <div class="row">
        <?php
        $enrolled_courses = array_filter($courses, function($course) {
            return $course['enrollment_id'] !== null;
        });
        ?>

        <?php foreach ($enrolled_courses as $course): ?>
            <div class="col-lg-4 col-md-6 mb-3">
                <div class="card border-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="card-title mb-0"><?= htmlspecialchars($course['course_code']) ?></h6>
                            <span class="badge bg-success">Enrolled</span>
                        </div>
                        <p class="card-text"><?= htmlspecialchars($course['course_name']) ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted"><?= $course['credits'] ?> credits</small>
                            <a href="quiz.php?course_id=<?= $course['id'] ?>" class="btn btn-sm btn-outline-success">
                                View Quizzes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

    <script>
        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                new bootstrap.Alert(alert).close();
            });
        }, 5000);

        // Add hover effects to course cards
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.transition = 'transform 0.3s ease';
            });

            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>

<?php include 'includes/footer.php'; ?>