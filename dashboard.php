<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Require login
require_login();

$student_id = $_SESSION['reg_id'];
$student_name = $_SESSION['reg_student_name'];

// Get student statistics
try {
    // Total quiz attempts
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM quiz_attempts WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $total_attempts = $stmt->fetchColumn();

    // Completed quizzes
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM quiz_attempts WHERE student_id = ? AND status = 'completed'");
    $stmt->execute([$student_id]);
    $completed_quizzes = $stmt->fetchColumn();

    // Average score
    $stmt = $pdo->prepare("SELECT AVG(score) FROM quiz_attempts WHERE student_id = ? AND status = 'completed'");
    $stmt->execute([$student_id]);
    $avg_score = round($stmt->fetchColumn() ?? 0, 1);

    // Enrolled courses
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM course_enrollments WHERE student_id = ? AND status = 'enrolled'");
    $stmt->execute([$student_id]);
    $enrolled_courses = $stmt->fetchColumn();

    // Recent quiz attempts
    $stmt = $pdo->prepare("
        SELECT qa.*, q.title as quiz_title, c.course_name 
        FROM quiz_attempts qa 
        JOIN quizzes q ON qa.quiz_id = q.id 
        LEFT JOIN courses c ON q.course_id = c.id 
        WHERE qa.student_id = ? 
        ORDER BY qa.start_time DESC 
        LIMIT 5
    ");
    $stmt->execute([$student_id]);
    $recent_attempts = $stmt->fetchAll();

    // Available quizzes
    $stmt = $pdo->prepare("
        SELECT q.*, c.course_name,
        (SELECT COUNT(*) FROM quiz_attempts qa WHERE qa.quiz_id = q.id AND qa.student_id = ?) as attempt_count
        FROM quizzes q 
        LEFT JOIN courses c ON q.course_id = c.id 
        WHERE q.is_active = 1 
        ORDER BY q.created_at DESC 
        LIMIT 6
    ");
    $stmt->execute([$student_id]);
    $available_quizzes = $stmt->fetchAll();

} catch (PDOException $e) {
    $total_attempts = $completed_quizzes = $avg_score = $enrolled_courses = 0;
    $recent_attempts = $available_quizzes = [];
}

$page_title = 'Dashboard';
include 'includes/header.php';
?>

    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col">
            <div class="bg-primary text-white p-4 rounded">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2><i class="fas fa-tachometer-alt me-2"></i>Welcome back, <?= htmlspecialchars(explode(' ', $student_name)[0]) ?>!</h2>
                        <p class="mb-0">Ready to continue your learning journey? Check out your progress and take new quizzes.</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <i class="fas fa-graduation-cap" style="font-size: 4rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number"><?= $total_attempts ?></div>
                        <div class="stats-label">Total Attempts</div>
                    </div>
                    <div class="stats-icon text-primary">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number"><?= $completed_quizzes ?></div>
                        <div class="stats-label">Completed Quizzes</div>
                    </div>
                    <div class="stats-icon text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number"><?= $avg_score ?>%</div>
                        <div class="stats-label">Average Score</div>
                    </div>
                    <div class="stats-icon text-info">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stats-number"><?= $enrolled_courses ?></div>
                        <div class="stats-label">Enrolled Courses</div>
                    </div>
                    <div class="stats-icon text-warning">
                        <i class="fas fa-book"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Available Quizzes -->
        <div class="col-lg-8 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list-check me-2"></i>Available Quizzes</h5>
                    <a href="quiz.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye me-1"></i>View All
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($available_quizzes)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-clipboard-list text-muted" style="font-size: 3rem;"></i>
                            <h6 class="text-muted mt-3">No quizzes available</h6>
                            <p class="text-muted">Check back later for new quizzes!</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($available_quizzes as $quiz): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card border">
                                        <div class="card-body">
                                            <h6 class="card-title"><?= htmlspecialchars($quiz['title']) ?></h6>
                                            <?php if ($quiz['course_name']): ?>
                                                <span class="badge bg-secondary mb-2"><?= htmlspecialchars($quiz['course_name']) ?></span>
                                            <?php endif; ?>
                                            <p class="card-text small text-muted">
                                                <?= htmlspecialchars(substr($quiz['description'] ?? '', 0, 100)) ?>...
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i><?= $quiz['time_limit'] ?> min
                                                    <i class="fas fa-question-circle ms-2 me-1"></i><?= $quiz['total_questions'] ?> questions
                                                </small>
                                                <?php if ($quiz['attempt_count'] > 0): ?>
                                                    <span class="badge bg-info">Attempted</span>
                                                <?php else: ?>
                                                    <a href="quiz.php?id=<?= $quiz['id'] ?>" class="btn btn-sm btn-success">
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activity</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_attempts)): ?>
                        <div class="text-center py-3">
                            <i class="fas fa-history text-muted" style="font-size: 2rem;"></i>
                            <p class="text-muted mt-2 mb-0">No recent activity</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_attempts as $attempt): ?>
                            <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                <div class="flex-shrink-0 me-3">
                                    <?php if ($attempt['status'] == 'completed'): ?>
                                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-check"></i>
                                        </div>
                                    <?php else: ?>
                                        <div class="rounded-circle bg-warning text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?= htmlspecialchars($attempt['quiz_title']) ?></h6>
                                    <?php if ($attempt['course_name']): ?>
                                        <small class="text-muted d-block"><?= htmlspecialchars($attempt['course_name']) ?></small>
                                    <?php endif; ?>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <?php if ($attempt['status'] == 'completed'): ?>
                                            <span class="badge bg-success">Score: <?= number_format($attempt['score'], 1) ?>%</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">In Progress</span>
                                        <?php endif; ?>
                                        <small class="text-muted"><?= time_ago($attempt['start_time']) ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="text-center">
                            <a href="results.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye me-1"></i>View All Results
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="quiz.php" class="btn btn-primary">
                            <i class="fas fa-clipboard-list me-2"></i>Take a Quiz
                        </a>
                        <a href="courses.php" class="btn btn-info">
                            <i class="fas fa-book me-2"></i>Browse Courses
                        </a>
                        <a href="results.php" class="btn btn-success">
                            <i class="fas fa-chart-bar me-2"></i>View Results
                        </a>
                        <a href="profile.php" class="btn btn-secondary">
                            <i class="fas fa-user-edit me-2"></i>Edit Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Progress Chart Section -->
<?php if ($completed_quizzes > 0): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-area me-2"></i>Your Progress</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <canvas id="scoreChart" height="200"></canvas>
                        </div>
                        <div class="col-md-6">
                            <h6>Performance Summary</h6>
                            <div class="progress mb-3">
                                <div class="progress-bar bg-success" style="width: <?= min($avg_score, 100) ?>%">
                                    <?= $avg_score ?>% Average
                                </div>
                            </div>

                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <div class="h5 mb-0 text-success"><?= $completed_quizzes ?></div>
                                        <small class="text-muted">Completed</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <div class="h5 mb-0 text-primary"><?= $total_attempts - $completed_quizzes ?></div>
                                        <small class="text-muted">In Progress</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <div class="h5 mb-0 text-warning"><?= $enrolled_courses ?></div>
                                        <small class="text-muted">Courses</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

    <script>
        // Chart.js for progress visualization
        <?php if ($completed_quizzes > 0): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('scoreChart').getContext('2d');

            // Get recent scores for chart
            const scores = [
                <?php
                $stmt = $pdo->prepare("SELECT score FROM quiz_attempts WHERE student_id = ? AND status = 'completed' ORDER BY start_time DESC LIMIT 10");
                $stmt->execute([$student_id]);
                $scores = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo implode(',', array_reverse($scores));
                ?>
            ];

            const labels = scores.map((_, index) => `Quiz ${index + 1}`);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Quiz Scores',
                        data: scores,
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Score: ' + context.parsed.y + '%';
                                }
                            }
                        }
                    }
                }
            });
        });
        <?php endif; ?>

        // Auto-refresh stats every 30 seconds
        setInterval(function() {
            // You can implement AJAX to refresh stats without page reload
        }, 30000);

        // Welcome animation
        document.addEventListener('DOMContentLoaded', function() {
            const statsCards = document.querySelectorAll('.stats-card');
            statsCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.5s ease';

                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });
        });
    </script>

<?php include 'includes/footer.php'; ?>