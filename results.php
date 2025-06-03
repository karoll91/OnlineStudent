<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Require login
require_login();

$student_id = $_SESSION['reg_id'];

// Get filter parameters
$course_filter = (int)($_GET['course'] ?? 0);
$status_filter = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query conditions
$where_conditions = ["qa.student_id = ?"];
$params = [$student_id];

if ($course_filter) {
    $where_conditions[] = "q.course_id = ?";
    $params[] = $course_filter;
}

if ($status_filter) {
    $where_conditions[] = "qa.status = ?";
    $params[] = $status_filter;
}

$where_clause = "WHERE " . implode(" AND ", $where_conditions);

try {
    // Get total count
    $count_query = "
        SELECT COUNT(*) 
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        $where_clause
    ";
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_attempts = $stmt->fetchColumn();

    // Get attempts with pagination
    $query = "
        SELECT qa.*, q.title as quiz_title, q.passing_score, q.total_questions as quiz_total_questions,
        c.course_name, c.course_code
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        LEFT JOIN courses c ON q.course_id = c.id
        $where_clause
        ORDER BY qa.start_time DESC
        LIMIT $per_page OFFSET $offset
    ";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $attempts = $stmt->fetchAll();

    // Get courses for filter dropdown
    $stmt = $pdo->prepare("
        SELECT DISTINCT c.id, c.course_name, c.course_code
        FROM courses c
        JOIN quizzes q ON c.id = q.course_id
        JOIN quiz_attempts qa ON q.id = qa.quiz_id
        WHERE qa.student_id = ?
        ORDER BY c.course_name
    ");
    $stmt->execute([$student_id]);
    $courses = $stmt->fetchAll();

    // Get student statistics
    $stmt = $pdo->prepare("
        SELECT 
        COUNT(*) as total_attempts,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_attempts,
        AVG(CASE WHEN status = 'completed' THEN score END) as avg_score,
        MAX(CASE WHEN status = 'completed' THEN score END) as best_score,
        COUNT(CASE WHEN status = 'completed' AND score >= passing_score THEN 1 END) as passed_quizzes
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        WHERE qa.student_id = ?
    ");
    $stmt->execute([$student_id]);
    $stats = $stmt->fetch();

} catch (PDOException $e) {
    $attempts = [];
    $courses = [];
    $stats = ['total_attempts' => 0, 'completed_attempts' => 0, 'avg_score' => 0, 'best_score' => 0, 'passed_quizzes' => 0];
    $total_attempts = 0;
}

// Calculate pagination
$total_pages = ceil($total_attempts / $per_page);

$page_title = 'My Results';
include 'includes/header.php';
?>

    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col">
            <div class="bg-info text-white p-4 rounded">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2><i class="fas fa-chart-bar me-2"></i>My Quiz Results</h2>
                        <p class="mb-0">Track your progress and view detailed quiz performance</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="h4 mb-0"><?= number_format($stats['avg_score'] ?? 0, 1) ?>%</div>
                        <small>Average Score</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="h4 mb-0 text-primary"><?= $stats['total_attempts'] ?></div>
                    <small class="text-muted">Total Attempts</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="h4 mb-0 text-success"><?= $stats['completed_attempts'] ?></div>
                    <small class="text-muted">Completed</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="h4 mb-0 text-info"><?= number_format($stats['avg_score'] ?? 0, 1) ?>%</div>
                    <small class="text-muted">Average</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="h4 mb-0 text-warning"><?= number_format($stats['best_score'] ?? 0, 1) ?>%</div>
                    <small class="text-muted">Best Score</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="h4 mb-0 text-success"><?= $stats['passed_quizzes'] ?></div>
                    <small class="text-muted">Passed</small>
                </div>
            </div>
        </div>
        <div class="col-lg-2 col-md-4 col-6 mb-3">
            <div class="card text-center">
                <div class="card-body">
                    <div class="h4 mb-0 text-danger"><?= $stats['completed_attempts'] - $stats['passed_quizzes'] ?></div>
                    <small class="text-muted">Failed</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row align-items-end">
                <div class="col-md-4 mb-2">
                    <label class="form-label">Filter by Course</label>
                    <select name="course" class="form-select">
                        <option value="">All Courses</option>
                        <?php foreach ($courses as $course): ?>
                            <option value="<?= $course['id'] ?>" <?= $course_filter == $course['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <label class="form-label">Filter by Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>Completed</option>
                        <option value="in_progress" <?= $status_filter == 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="abandoned" <?= $status_filter == 'abandoned' ? 'selected' : '' ?>>Abandoned</option>
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                    <a href="results.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">
                Quiz Attempts
                <span class="badge bg-primary ms-2"><?= $total_attempts ?> total</span>
            </h5>
        </div>
        <div class="card-body">
            <?php if (empty($attempts)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-chart-bar text-muted" style="font-size: 4rem;"></i>
                    <h5 class="text-muted mt-3">No quiz attempts found</h5>
                    <?php if ($course_filter || $status_filter): ?>
                        <p class="text-muted">Try adjusting your filters</p>
                        <a href="results.php" class="btn btn-primary">View All Results</a>
                    <?php else: ?>
                        <p class="text-muted">Start taking quizzes to see your results here</p>
                        <a href="quiz.php" class="btn btn-primary">Take a Quiz</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                        <tr>
                            <th>Quiz</th>
                            <th>Course</th>
                            <th>Score</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Time Taken</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($attempts as $attempt): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($attempt['quiz_title']) ?></div>
                                    <small class="text-muted">
                                        <?= $attempt['correct_answers'] ?>/<?= $attempt['total_questions'] ?> correct
                                    </small>
                                </td>
                                <td>
                                    <?php if ($attempt['course_name']): ?>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($attempt['course_code']) ?></span>
                                        <br><small class="text-muted"><?= htmlspecialchars($attempt['course_name']) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">No course</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($attempt['status'] == 'completed'): ?>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-<?= $attempt['score'] >= $attempt['passing_score'] ? 'success' : 'danger' ?> me-2">
                                                <?= number_format($attempt['score'], 1) ?>%
                                            </span>
                                            <?php if ($attempt['score'] >= $attempt['passing_score']): ?>
                                                <i class="fas fa-check-circle text-success" title="Passed"></i>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle text-danger" title="Failed"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="progress mt-1" style="height: 6px;">
                                            <div class="progress-bar bg-<?= $attempt['score'] >= $attempt['passing_score'] ? 'success' : 'danger' ?>"
                                                 style="width: <?= $attempt['score'] ?>%"></div>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $status_colors = [
                                        'completed' => 'success',
                                        'in_progress' => 'warning',
                                        'abandoned' => 'secondary'
                                    ];
                                    $status_color = $status_colors[$attempt['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $status_color ?>">
                                        <?= ucfirst(str_replace('_', ' ', $attempt['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <div><?= date('M d, Y', strtotime($attempt['start_time'])) ?></div>
                                    <small class="text-muted"><?= date('H:i', strtotime($attempt['start_time'])) ?></small>
                                </td>
                                <td>
                                    <?php if ($attempt['time_taken']): ?>
                                        <?= gmdate('H:i:s', $attempt['time_taken']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php if ($attempt['status'] == 'completed'): ?>
                                            <a href="quiz-result.php?attempt=<?= $attempt['id'] ?>"
                                               class="btn btn-outline-primary" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php elseif ($attempt['status'] == 'in_progress'): ?>
                                            <a href="quiz.php?id=<?= $attempt['quiz_id'] ?>"
                                               class="btn btn-outline-warning" title="Continue Quiz">
                                                <i class="fas fa-play"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="quiz.php?id=<?= $attempt['quiz_id'] ?>"
                                           class="btn btn-outline-success" title="Retake Quiz">
                                            <i class="fas fa-redo"></i>
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
                                    <a class="page-link" href="?page=<?= $page - 1 ?>&course=<?= $course_filter ?>&status=<?= urlencode($status_filter) ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&course=<?= $course_filter ?>&status=<?= urlencode($status_filter) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>&course=<?= $course_filter ?>&status=<?= urlencode($status_filter) ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>

                    <div class="text-center text-muted">
                        Showing <?= $offset + 1 ?> to <?= min($offset + $per_page, $total_attempts) ?> of <?= $total_attempts ?> results
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body text-center">
                    <h6>Ready for more challenges?</h6>
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <a href="quiz.php" class="btn btn-primary">
                            <i class="fas fa-clipboard-list me-1"></i>Take New Quiz
                        </a>
                        <a href="courses.php" class="btn btn-info">
                            <i class="fas fa-book me-1"></i>Browse Courses
                        </a>
                        <a href="dashboard.php" class="btn btn-success">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-submit form on filter change
        document.querySelectorAll('select[name="course"], select[name="status"]').forEach(select => {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });

        // Add hover effects to table rows
        document.querySelectorAll('tbody tr').forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f8f9fa';
            });

            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });

        // Smooth scroll to top when pagination is clicked
        document.querySelectorAll('.pagination a').forEach(link => {
            link.addEventListener('click', function() {
                setTimeout(() => {
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }, 100);
            });
        });
    </script>

<?php include 'includes/footer.php'; ?>