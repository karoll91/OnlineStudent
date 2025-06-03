<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Require login
require_login();

$student_id = $_SESSION['reg_id'];
$attempt_id = (int)($_GET['attempt'] ?? 0);

if (!$attempt_id) {
    header('Location: dashboard.php');
    exit();
}

// Get attempt details
try {
    $stmt = $pdo->prepare("
        SELECT qa.*, q.title as quiz_title, q.passing_score, q.total_questions as quiz_total_questions,
        c.course_name, c.course_code, s.first_name, s.last_name
        FROM quiz_attempts qa
        JOIN quizzes q ON qa.quiz_id = q.id
        LEFT JOIN courses c ON q.course_id = c.id
        JOIN students s ON qa.student_id = s.id
        WHERE qa.id = ? AND qa.student_id = ?
    ");
    $stmt->execute([$attempt_id, $student_id]);
    $attempt = $stmt->fetch();

    if (!$attempt) {
        header('Location: dashboard.php');
        exit();
    }

    // Get detailed answers
    $stmt = $pdo->prepare("
        SELECT qans.*, qq.question_text, qq.option_a, qq.option_b, qq.option_c, qq.option_d, 
        qq.correct_answer, qq.points
        FROM quiz_answers qans
        JOIN quiz_questions qq ON qans.question_id = qq.id
        WHERE qans.attempt_id = ?
        ORDER BY qq.id
    ");
    $stmt->execute([$attempt_id]);
    $answers = $stmt->fetchAll();

    // Calculate performance metrics
    $total_points = array_sum(array_column($answers, 'points'));
    $earned_points = 0;
    foreach ($answers as $answer) {
        if ($answer['is_correct']) {
            $earned_points += $answer['points'];
        }
    }

    $score_percentage = $total_points > 0 ? ($earned_points / $total_points) * 100 : 0;
    $passed = $score_percentage >= $attempt['passing_score'];

    // Get student's other attempts for this quiz
    $stmt = $pdo->prepare("
        SELECT score, start_time FROM quiz_attempts 
        WHERE student_id = ? AND quiz_id = (SELECT quiz_id FROM quiz_attempts WHERE id = ?) AND status = 'completed'
        ORDER BY start_time DESC
    ");
    $stmt->execute([$student_id, $attempt_id]);
    $all_attempts = $stmt->fetchAll();

} catch (PDOException $e) {
    header('Location: dashboard.php');
    exit();
}

$page_title = 'Quiz Results';
include 'includes/header.php';
?>

    <!-- Results Header -->
    <div class="row mb-4">
        <div class="col">
            <div class="bg-<?= $passed ? 'success' : 'warning' ?> text-<?= $passed ? 'white' : 'dark' ?> p-4 rounded">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2>
                            <i class="fas fa-<?= $passed ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                            Quiz <?= $passed ? 'Passed!' : 'Needs Improvement' ?>
                        </h2>
                        <h4><?= htmlspecialchars($attempt['quiz_title']) ?></h4>
                        <?php if ($attempt['course_name']): ?>
                            <p class="mb-0"><?= htmlspecialchars($attempt['course_name']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="display-4 fw-bold"><?= number_format($score_percentage, 1) ?>%</div>
                        <div>Your Score</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Score Details -->
        <div class="col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Score Breakdown</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <canvas id="scoreChart" width="200" height="200"></canvas>
                    </div>

                    <div class="row text-center mb-3">
                        <div class="col-6">
                            <div class="border rounded p-3">
                                <div class="h4 mb-0 text-success"><?= $attempt['correct_answers'] ?></div>
                                <small class="text-muted">Correct</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border rounded p-3">
                                <div class="h4 mb-0 text-danger"><?= $attempt['total_questions'] - $attempt['correct_answers'] ?></div>
                                <small class="text-muted">Incorrect</small>
                            </div>
                        </div>
                    </div>

                    <div class="progress mb-3" style="height: 25px;">
                        <div class="progress-bar bg-<?= $passed ? 'success' : 'warning' ?>"
                             style="width: <?= $score_percentage ?>%">
                            <?= number_format($score_percentage, 1) ?>%
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Passing Score:</span>
                        <span class="fw-bold"><?= $attempt['passing_score'] ?>%</span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Your Score:</span>
                        <span class="fw-bold text-<?= $passed ? 'success' : 'warning' ?>"><?= number_format($score_percentage, 1) ?>%</span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Points Earned:</span>
                        <span class="fw-bold"><?= $earned_points ?> / <?= $total_points ?></span>
                    </div>

                    <div class="d-flex justify-content-between">
                        <span>Time Taken:</span>
                        <span class="fw-bold"><?= gmdate('H:i:s', $attempt['time_taken']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Performance Chart -->
            <?php if (count($all_attempts) > 1): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Progress History</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="progressChart" height="200"></canvas>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Detailed Review -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>Question Review</h5>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary" onclick="showAll()">Show All</button>
                        <button class="btn btn-sm btn-outline-success" onclick="showCorrect()">Correct Only</button>
                        <button class="btn btn-sm btn-outline-danger" onclick="showIncorrect()">Incorrect Only</button>
                    </div>
                </div>
                <div class="card-body">
                    <?php foreach ($answers as $index => $answer): ?>
                        <div class="question-review mb-4 <?= $answer['is_correct'] ? 'correct-answer' : 'incorrect-answer' ?>">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h6>Question <?= $index + 1 ?></h6>
                                <div class="d-flex align-items-center">
                                <span class="badge bg-<?= $answer['is_correct'] ? 'success' : 'danger' ?> me-2">
                                    <?= $answer['is_correct'] ? $answer['points'] : '0' ?> / <?= $answer['points'] ?> points
                                </span>
                                    <i class="fas fa-<?= $answer['is_correct'] ? 'check-circle text-success' : 'times-circle text-danger' ?> fa-lg"></i>
                                </div>
                            </div>

                            <p class="question-text mb-3"><?= htmlspecialchars($answer['question_text']) ?></p>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="option-review option-a <?= $answer['selected_answer'] == 'A' ? 'selected' : '' ?> <?= $answer['correct_answer'] == 'A' ? 'correct' : '' ?>">
                                        <strong>A.</strong> <?= htmlspecialchars($answer['option_a']) ?>
                                        <?php if ($answer['correct_answer'] == 'A'): ?>
                                            <i class="fas fa-check text-success float-end"></i>
                                        <?php endif; ?>
                                    </div>

                                    <div class="option-review option-b <?= $answer['selected_answer'] == 'B' ? 'selected' : '' ?> <?= $answer['correct_answer'] == 'B' ? 'correct' : '' ?>">
                                        <strong>B.</strong> <?= htmlspecialchars($answer['option_b']) ?>
                                        <?php if ($answer['correct_answer'] == 'B'): ?>
                                            <i class="fas fa-check text-success float-end"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <?php if (!empty($answer['option_c'])): ?>
                                        <div class="option-review option-c <?= $answer['selected_answer'] == 'C' ? 'selected' : '' ?> <?= $answer['correct_answer'] == 'C' ? 'correct' : '' ?>">
                                            <strong>C.</strong> <?= htmlspecialchars($answer['option_c']) ?>
                                            <?php if ($answer['correct_answer'] == 'C'): ?>
                                                <i class="fas fa-check text-success float-end"></i>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($answer['option_d'])): ?>
                                        <div class="option-review option-d <?= $answer['selected_answer'] == 'D' ? 'selected' : '' ?> <?= $answer['correct_answer'] == 'D' ? 'correct' : '' ?>">
                                            <strong>D.</strong> <?= htmlspecialchars($answer['option_d']) ?>
                                            <?php if ($answer['correct_answer'] == 'D'): ?>
                                                <i class="fas fa-check text-success float-end"></i>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (!$answer['is_correct']): ?>
                                <div class="mt-3 p-3 bg-light rounded">
                                    <small class="text-muted">
                                        <strong>Your answer:</strong> <?= $answer['selected_answer'] ?: 'Not answered' ?> |
                                        <strong>Correct answer:</strong> <?= $answer['correct_answer'] ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="card mt-4">
                <div class="card-body text-center">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="quiz.php" class="btn btn-primary">
                            <i class="fas fa-list me-2"></i>Back to Quizzes
                        </a>
                        <a href="results.php" class="btn btn-info">
                            <i class="fas fa-chart-bar me-2"></i>All Results
                        </a>
                        <button class="btn btn-secondary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print Results
                        </button>
                        <a href="dashboard.php" class="btn btn-success">
                            <i class="fas fa-home me-2"></i>Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .question-review {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 1.5rem;
            background: white;
        }

        .option-review {
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            background: #f8f9fa;
        }

        .option-review.selected {
            border-color: #007bff;
            background: #e3f2fd;
        }

        .option-review.correct {
            border-color: #28a745;
            background: #d4edda;
        }

        .option-review.selected.correct {
            border-color: #28a745;
            background: #d4edda;
        }

        .option-review.selected:not(.correct) {
            border-color: #dc3545;
            background: #f8d7da;
        }

        @media print {
            .btn, .card-header .btn-group {
                display: none !important;
            }
            .card {
                border: 1px solid #000 !important;
                break-inside: avoid;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Score pie chart
        const scoreCtx = document.getElementById('scoreChart').getContext('2d');
        new Chart(scoreCtx, {
            type: 'doughnut',
            data: {
                labels: ['Correct', 'Incorrect'],
                datasets: [{
                    data: [<?= $attempt['correct_answers'] ?>, <?= $attempt['total_questions'] - $attempt['correct_answers'] ?>],
                    backgroundColor: ['#28a745', '#dc3545'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        <?php if (count($all_attempts) > 1): ?>
        // Progress chart
        const progressCtx = document.getElementById('progressChart').getContext('2d');
        new Chart(progressCtx, {
            type: 'line',
            data: {
                labels: [<?php
                    $labels = [];
                    foreach (array_reverse($all_attempts) as $i => $att) {
                        $labels[] = '"Attempt ' . ($i + 1) . '"';
                    }
                    echo implode(',', $labels);
                    ?>],
                datasets: [{
                    label: 'Score %',
                    data: [<?= implode(',', array_reverse(array_column($all_attempts, 'score'))) ?>],
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
                    }
                }
            }
        });
        <?php endif; ?>

        // Filter functions
        function showAll() {
            document.querySelectorAll('.question-review').forEach(el => {
                el.style.display = 'block';
            });
        }

        function showCorrect() {
            document.querySelectorAll('.question-review').forEach(el => {
                el.style.display = el.classList.contains('correct-answer') ? 'block' : 'none';
            });
        }

        function showIncorrect() {
            document.querySelectorAll('.question-review').forEach(el => {
                el.style.display = el.classList.contains('incorrect-answer') ? 'block' : 'none';
            });
        }

        // Scroll to top on load
        window.scrollTo(0, 0);
    </script>

<?php include 'includes/footer.php'; ?>