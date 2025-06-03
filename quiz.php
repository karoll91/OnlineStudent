<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Require login
require_login();

$student_id = $_SESSION['reg_id'];
$quiz_id = (int)($_GET['id'] ?? 0);
$course_id = (int)($_GET['course_id'] ?? 0);
$error = '';

// If no specific quiz, show quiz list
if (!$quiz_id) {
    // Get available quizzes
    try {
        $where_clause = "WHERE q.is_active = 1";
        $params = [];

        if ($course_id) {
            $where_clause .= " AND q.course_id = ?";
            $params[] = $course_id;
        }

        $stmt = $pdo->prepare("
            SELECT q.*, c.course_name, c.course_code,
            COUNT(DISTINCT qa.id) as attempts_count,
            MAX(qa.score) as best_score
            FROM quizzes q
            LEFT JOIN courses c ON q.course_id = c.id
            LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id AND qa.student_id = ?
            $where_clause
            GROUP BY q.id
            ORDER BY q.created_at DESC
        ");
        $params = array_merge([$student_id], $params);
        $stmt->execute($params);
        $quizzes = $stmt->fetchAll();

        // Get course name for header
        $course_name = '';
        if ($course_id) {
            $stmt = $pdo->prepare("SELECT course_name FROM courses WHERE id = ?");
            $stmt->execute([$course_id]);
            $course_name = $stmt->fetchColumn();
        }

    } catch (PDOException $e) {
        $quizzes = [];
    }

    $page_title = 'Quizzes' . ($course_name ? ' - ' . $course_name : '');
    include 'includes/header.php';
    ?>

    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col">
            <div class="bg-success text-white p-4 rounded">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2><i class="fas fa-clipboard-list me-2"></i>Available Quizzes</h2>
                        <?php if ($course_name): ?>
                            <p class="mb-0">Course: <?= htmlspecialchars($course_name) ?></p>
                        <?php else: ?>
                            <p class="mb-0">Test your knowledge with our interactive quizzes</p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-end">
                        <i class="fas fa-brain" style="font-size: 4rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quizzes List -->
    <?php if (empty($quizzes)): ?>
        <div class="text-center py-5">
            <i class="fas fa-clipboard-list text-muted" style="font-size: 4rem;"></i>
            <h5 class="text-muted mt-3">No quizzes available</h5>
            <p class="text-muted">Check back later for new quizzes!</p>
            <a href="courses.php" class="btn btn-primary">
                <i class="fas fa-book me-2"></i>Browse Courses
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($quizzes as $quiz): ?>
                <div class="col-lg-6 col-xl-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-header bg-transparent">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><?= htmlspecialchars($quiz['title']) ?></h6>
                                <?php if ($quiz['attempts_count'] > 0): ?>
                                    <span class="badge bg-info"><?= $quiz['attempts_count'] ?> attempts</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card-body">
                            <?php if ($quiz['course_name'] && !$course_id): ?>
                                <span class="badge bg-primary mb-2"><?= htmlspecialchars($quiz['course_code']) ?></span>
                            <?php endif; ?>

                            <p class="card-text text-muted">
                                <?= htmlspecialchars(substr($quiz['description'] ?? '', 0, 100)) ?>...
                            </p>

                            <div class="row text-center mb-3">
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <div class="h6 mb-0 text-primary"><?= $quiz['total_questions'] ?></div>
                                        <small class="text-muted">Questions</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <div class="h6 mb-0 text-warning"><?= $quiz['time_limit'] ?></div>
                                        <small class="text-muted">Minutes</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="border rounded p-2">
                                        <div class="h6 mb-0 text-success"><?= $quiz['passing_score'] ?>%</div>
                                        <small class="text-muted">Pass Score</small>
                                    </div>
                                </div>
                            </div>

                            <?php if ($quiz['best_score']): ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="text-muted">Best Score:</small>
                                        <span class="badge bg-<?= $quiz['best_score'] >= $quiz['passing_score'] ? 'success' : 'warning' ?>">
                                            <?= number_format($quiz['best_score'], 1) ?>%
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-<?= $quiz['best_score'] >= $quiz['passing_score'] ? 'success' : 'warning' ?>"
                                             style="width: <?= $quiz['best_score'] ?>%"></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="card-footer bg-transparent">
                            <div class="d-grid">
                                <a href="quiz.php?id=<?= $quiz['id'] ?>" class="btn btn-success">
                                    <i class="fas fa-play me-2"></i>
                                    <?= $quiz['attempts_count'] > 0 ? 'Retake Quiz' : 'Start Quiz' ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php
    include 'includes/footer.php';
    exit();
}

// Taking specific quiz
try {
    // Get quiz details
    $stmt = $pdo->prepare("
        SELECT q.*, c.course_name, c.course_code 
        FROM quizzes q 
        LEFT JOIN courses c ON q.course_id = c.id 
        WHERE q.id = ? AND q.is_active = 1
    ");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();

    if (!$quiz) {
        header('Location: quiz.php');
        exit();
    }

    // Check if student has active attempt
    $stmt = $pdo->prepare("
        SELECT * FROM quiz_attempts 
        WHERE student_id = ? AND quiz_id = ? AND status = 'in_progress'
        ORDER BY start_time DESC 
        LIMIT 1
    ");
    $stmt->execute([$student_id, $quiz_id]);
    $active_attempt = $stmt->fetch();

    // Handle quiz submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'start_quiz') {
            // Start new quiz attempt
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO quiz_attempts (student_id, quiz_id, start_time, status, total_questions) 
                    VALUES (?, ?, NOW(), 'in_progress', ?)
                ");
                $stmt->execute([$student_id, $quiz_id, $quiz['total_questions']]);

                // Refresh page to load quiz
                header("Location: quiz.php?id=$quiz_id");
                exit();
            } catch (PDOException $e) {
                $error = 'Failed to start quiz.';
            }
        } elseif ($action === 'submit_quiz' && $active_attempt) {
            // Calculate score and finish quiz
            $answers = $_POST['answers'] ?? [];
            $correct_count = 0;
            $total_points = 0;
            $earned_points = 0;

            // Get all questions
            $stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id");
            $stmt->execute([$quiz_id]);
            $questions = $stmt->fetchAll();

            foreach ($questions as $question) {
                $total_points += $question['points'];
                $selected_answer = $answers[$question['id']] ?? '';

                if ($selected_answer === $question['correct_answer']) {
                    $correct_count++;
                    $earned_points += $question['points'];
                }

                // Save individual answer
                $stmt = $pdo->prepare("
                    INSERT INTO quiz_answers (attempt_id, question_id, selected_answer, is_correct) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $active_attempt['id'],
                    $question['id'],
                    $selected_answer,
                    $selected_answer === $question['correct_answer'] ? 1 : 0
                ]);
            }

            // Calculate final score
            $score = $total_points > 0 ? ($earned_points / $total_points) * 100 : 0;
            $time_taken = time() - strtotime($active_attempt['start_time']);

            // Update attempt
            $stmt = $pdo->prepare("
                UPDATE quiz_attempts 
                SET end_time = NOW(), score = ?, correct_answers = ?, status = 'completed', time_taken = ?
                WHERE id = ?
            ");
            $stmt->execute([$score, $correct_count, $time_taken, $active_attempt['id']]);

            // Redirect to results
            header("Location: quiz-result.php?attempt=" . $active_attempt['id']);
            exit();
        }
    }

    // If no active attempt, show quiz info
    if (!$active_attempt) {
        $page_title = $quiz['title'];
        include 'includes/header.php';
        ?>

        <!-- Quiz Introduction -->
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow">
                    <div class="card-header bg-success text-white text-center">
                        <h3><i class="fas fa-clipboard-list me-2"></i><?= htmlspecialchars($quiz['title']) ?></h3>
                        <?php if ($quiz['course_name']): ?>
                            <p class="mb-0"><?= htmlspecialchars($quiz['course_name']) ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($quiz['description']): ?>
                            <div class="mb-4">
                                <p class="lead"><?= nl2br(htmlspecialchars($quiz['description'])) ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-question-circle text-primary me-2"></i>
                                        <strong><?= $quiz['total_questions'] ?></strong> questions
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-clock text-warning me-2"></i>
                                        <strong><?= $quiz['time_limit'] ?></strong> minutes time limit
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-target text-success me-2"></i>
                                        <strong><?= $quiz['passing_score'] ?>%</strong> required to pass
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-save text-info me-2"></i>
                                        Answers are auto-saved
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-redo text-secondary me-2"></i>
                                        You can review before submit
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                                        Cannot pause once started
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-lightbulb me-2"></i>
                            <strong>Tips:</strong> Read each question carefully, manage your time wisely,
                            and make sure to submit before time runs out.
                        </div>

                        <div class="text-center">
                            <form method="POST">
                                <input type="hidden" name="action" value="start_quiz">
                                <button type="submit" class="btn btn-success btn-lg" onclick="return confirm('Are you ready to start the quiz? You cannot pause once started.')">
                                    <i class="fas fa-play me-2"></i>Start Quiz Now
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
        include 'includes/footer.php';
        exit();
    }

    // Taking quiz - get questions
    $stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id");
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll();

    // Get existing answers
    $stmt = $pdo->prepare("SELECT question_id, selected_answer FROM quiz_answers WHERE attempt_id = ?");
    $stmt->execute([$active_attempt['id']]);
    $existing_answers = [];
    while ($row = $stmt->fetch()) {
        $existing_answers[$row['question_id']] = $row['selected_answer'];
    }

} catch (PDOException $e) {
    $error = 'Database error occurred.';
    $quiz = null;
}

$page_title = 'Taking Quiz: ' . ($quiz['title'] ?? 'Quiz');
include 'includes/header.php';
?>

    <!-- Quiz Timer -->
    <div class="quiz-timer" id="quizTimer">
        <div class="text-center">
            <i class="fas fa-clock mb-1"></i>
            <div class="h6 mb-0">Time Left</div>
            <div class="h4 mb-0" id="timeDisplay">--:--</div>
        </div>
    </div>

    <!-- Quiz Header -->
    <div class="bg-warning text-dark p-3 rounded mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h4><i class="fas fa-clipboard-list me-2"></i><?= htmlspecialchars($quiz['title']) ?></h4>
                <small>Answer all questions and submit before time runs out</small>
            </div>
            <div class="col-md-4 text-end">
                <div class="progress mb-2" style="height: 20px;">
                    <div class="progress-bar bg-success" id="progressBar" style="width: 0%">0%</div>
                </div>
                <small id="progressText">0 of <?= count($questions) ?> answered</small>
            </div>
        </div>
    </div>

    <form method="POST" id="quizForm">
        <input type="hidden" name="action" value="submit_quiz">

        <!-- Questions -->
        <?php foreach ($questions as $index => $question): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Question <?= $index + 1 ?> of <?= count($questions) ?></h6>
                        <span class="badge bg-primary"><?= $question['points'] ?> point<?= $question['points'] > 1 ? 's' : '' ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <h6 class="mb-3"><?= htmlspecialchars($question['question_text']) ?></h6>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check option-card" onclick="selectOption(<?= $question['id'] ?>, 'A')">
                                <input class="form-check-input" type="radio" name="answers[<?= $question['id'] ?>]"
                                       value="A" id="q<?= $question['id'] ?>_a"
                                    <?= ($existing_answers[$question['id']] ?? '') === 'A' ? 'checked' : '' ?>>
                                <label class="form-check-label w-100" for="q<?= $question['id'] ?>_a">
                                    <strong>A.</strong> <?= htmlspecialchars($question['option_a']) ?>
                                </label>
                            </div>

                            <div class="form-check option-card" onclick="selectOption(<?= $question['id'] ?>, 'B')">
                                <input class="form-check-input" type="radio" name="answers[<?= $question['id'] ?>]"
                                       value="B" id="q<?= $question['id'] ?>_b"
                                    <?= ($existing_answers[$question['id']] ?? '') === 'B' ? 'checked' : '' ?>>
                                <label class="form-check-label w-100" for="q<?= $question['id'] ?>_b">
                                    <strong>B.</strong> <?= htmlspecialchars($question['option_b']) ?>
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <?php if (!empty($question['option_c'])): ?>
                                <div class="form-check option-card" onclick="selectOption(<?= $question['id'] ?>, 'C')">
                                    <input class="form-check-input" type="radio" name="answers[<?= $question['id'] ?>]"
                                           value="C" id="q<?= $question['id'] ?>_c"
                                        <?= ($existing_answers[$question['id']] ?? '') === 'C' ? 'checked' : '' ?>>
                                    <label class="form-check-label w-100" for="q<?= $question['id'] ?>_c">
                                        <strong>C.</strong> <?= htmlspecialchars($question['option_c']) ?>
                                    </label>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($question['option_d'])): ?>
                                <div class="form-check option-card" onclick="selectOption(<?= $question['id'] ?>, 'D')">
                                    <input class="form-check-input" type="radio" name="answers[<?= $question['id'] ?>]"
                                           value="D" id="q<?= $question['id'] ?>_d"
                                        <?= ($existing_answers[$question['id']] ?? '') === 'D' ? 'checked' : '' ?>>
                                    <label class="form-check-label w-100" for="q<?= $question['id'] ?>_d">
                                        <strong>D.</strong> <?= htmlspecialchars($question['option_d']) ?>
                                    </label>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Submit Button -->
        <div class="card bg-light">
            <div class="card-body text-center">
                <h5>Ready to submit?</h5>
                <p class="text-muted">Make sure you have answered all questions before submitting.</p>
                <button type="submit" class="btn btn-success btn-lg" onclick="return confirmSubmit()">
                    <i class="fas fa-check me-2"></i>Submit Quiz
                </button>
            </div>
        </div>
    </form>

    <style>
        .quiz-timer {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 1rem;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            min-width: 120px;
        }

        .quiz-timer.warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
        }

        .quiz-timer.danger {
            background: #f8d7da;
            border: 2px solid #dc3545;
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .option-card {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .option-card:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }

        .option-card:has(input:checked) {
            border-color: #28a745;
            background-color: #d4edda;
        }
    </style>

    <script>
        // Quiz timer
        let startTime = new Date('<?= $active_attempt['start_time'] ?>').getTime();
        let timeLimit = <?= $quiz['time_limit'] ?> * 60 * 1000; // Convert to milliseconds
        let timerElement = document.getElementById('timeDisplay');
        let timerContainer = document.getElementById('quizTimer');

        function updateTimer() {
            let now = new Date().getTime();
            let elapsed = now - startTime;
            let remaining = timeLimit - elapsed;

            if (remaining <= 0) {
                // Time's up - auto submit
                document.getElementById('quizForm').submit();
                return;
            }

            let minutes = Math.floor(remaining / (1000 * 60));
            let seconds = Math.floor((remaining % (1000 * 60)) / 1000);

            timerElement.textContent =
                String(minutes).padStart(2, '0') + ':' +
                String(seconds).padStart(2, '0');

            // Warning states
            if (remaining <= 5 * 60 * 1000) { // 5 minutes
                timerContainer.className = 'quiz-timer danger';
            } else if (remaining <= 10 * 60 * 1000) { // 10 minutes
                timerContainer.className = 'quiz-timer warning';
            }
        }

        // Update timer every second
        setInterval(updateTimer, 1000);
        updateTimer();

        // Option selection
        function selectOption(questionId, option) {
            document.getElementById(`q${questionId}_${option.toLowerCase()}`).checked = true;
            updateProgress();
        }

        // Update progress
        function updateProgress() {
            const totalQuestions = <?= count($questions) ?>;
            const answeredQuestions = document.querySelectorAll('input[type="radio"]:checked').length;
            const progressPercent = (answeredQuestions / totalQuestions) * 100;

            document.getElementById('progressBar').style.width = progressPercent + '%';
            document.getElementById('progressBar').textContent = Math.round(progressPercent) + '%';
            document.getElementById('progressText').textContent = `${answeredQuestions} of ${totalQuestions} answered`;
        }

        // Confirm submit
        function confirmSubmit() {
            const totalQuestions = <?= count($questions) ?>;
            const answeredQuestions = document.querySelectorAll('input[type="radio"]:checked').length;

            if (answeredQuestions < totalQuestions) {
                const unanswered = totalQuestions - answeredQuestions;
                return confirm(`You have ${unanswered} unanswered question(s). Are you sure you want to submit?`);
            }

            return confirm('Are you sure you want to submit your quiz? This action cannot be undone.');
        }

        // Prevent page refresh
        window.addEventListener('beforeunload', function(e) {
            e.preventDefault();
            e.returnValue = 'Your quiz progress will be lost if you leave this page.';
            return e.returnValue;
        });

        // Remove beforeunload when submitting
        document.getElementById('quizForm').addEventListener('submit', function() {
            window.removeEventListener('beforeunload', arguments.callee);
        });

        // Initialize progress
        updateProgress();
    </script>

<?php include 'includes/footer.php'; ?>