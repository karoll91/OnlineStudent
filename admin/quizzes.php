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

    if ($action === 'add_quiz') {
        $title = clean_input($_POST['title'] ?? '');
        $description = clean_input($_POST['description'] ?? '');
        $course_id = (int)($_POST['course_id'] ?? 0);
        $time_limit = (int)($_POST['time_limit'] ?? 30);
        $passing_score = (int)($_POST['passing_score'] ?? 60);

        if (empty($title)) {
            $error = 'Quiz title is required.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO quizzes (title, description, course_id, time_limit, passing_score, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$title, $description, $course_id ?: null, $time_limit, $passing_score]);
                $message = 'Quiz created successfully.';
            } catch (PDOException $e) {
                $error = 'Failed to create quiz.';
            }
        }
    } elseif ($action === 'add_question') {
        $quiz_id = (int)($_POST['quiz_id'] ?? 0);
        $question_text = clean_input($_POST['question_text'] ?? '');
        $option_a = clean_input($_POST['option_a'] ?? '');
        $option_b = clean_input($_POST['option_b'] ?? '');
        $option_c = clean_input($_POST['option_c'] ?? '');
        $option_d = clean_input($_POST['option_d'] ?? '');
        $correct_answer = $_POST['correct_answer'] ?? '';
        $points = (int)($_POST['points'] ?? 1);

        if ($quiz_id && !empty($question_text) && !empty($option_a) && !empty($option_b) && in_array($correct_answer, ['A', 'B', 'C', 'D'])) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO quiz_questions (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_answer, points) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$quiz_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer, $points]);

                // Update total questions count
                $stmt = $pdo->prepare("UPDATE quizzes SET total_questions = (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = ?) WHERE id = ?");
                $stmt->execute([$quiz_id, $quiz_id]);

                $message = 'Question added successfully.';
            } catch (PDOException $e) {
                $error = 'Failed to add question.';
            }
        } else {
            $error = 'Please fill all required fields correctly.';
        }
    }
}

// Handle actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $quiz_id = (int)($_GET['id'] ?? 0);

    if ($action === 'activate' && $quiz_id) {
        try {
            $stmt = $pdo->prepare("UPDATE quizzes SET is_active = 1 WHERE id = ?");
            $stmt->execute([$quiz_id]);
            $message = 'Quiz activated successfully.';
        } catch (PDOException $e) {
            $error = 'Failed to activate quiz.';
        }
    } elseif ($action === 'deactivate' && $quiz_id) {
        try {
            $stmt = $pdo->prepare("UPDATE quizzes SET is_active = 0 WHERE id = ?");
            $stmt->execute([$quiz_id]);
            $message = 'Quiz deactivated successfully.';
        } catch (PDOException $e) {
            $error = 'Failed to deactivate quiz.';
        }
    } elseif ($action === 'delete' && $quiz_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = ?");
            $stmt->execute([$quiz_id]);
            $message = 'Quiz deleted successfully.';
        } catch (PDOException $e) {
            $error = 'Failed to delete quiz.';
        }
    } elseif ($action === 'delete_question') {
        $question_id = (int)($_GET['question_id'] ?? 0);
        if ($question_id) {
            try {
                // Get quiz_id before deleting
                $stmt = $pdo->prepare("SELECT quiz_id FROM quiz_questions WHERE id = ?");
                $stmt->execute([$question_id]);
                $quiz_id = $stmt->fetchColumn();

                // Delete question
                $stmt = $pdo->prepare("DELETE FROM quiz_questions WHERE id = ?");
                $stmt->execute([$question_id]);

                // Update total questions count
                $stmt = $pdo->prepare("UPDATE quizzes SET total_questions = (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = ?) WHERE id = ?");
                $stmt->execute([$quiz_id, $quiz_id]);

                $message = 'Question deleted successfully.';
            } catch (PDOException $e) {
                $error = 'Failed to delete question.';
            }
        }
    }
}

// Get quizzes with statistics
try {
    $stmt = $pdo->query("
        SELECT q.*, c.course_name,
        COUNT(DISTINCT qa.student_id) as total_attempts,
        AVG(qa.score) as avg_score
        FROM quizzes q
        LEFT JOIN courses c ON q.course_id = c.id
        LEFT JOIN quiz_attempts qa ON q.id = qa.quiz_id AND qa.status = 'completed'
        GROUP BY q.id
        ORDER BY q.created_at DESC
    ");
    $quizzes = $stmt->fetchAll();

    // Get courses for dropdown
    $stmt = $pdo->query("SELECT id, course_code, course_name FROM courses WHERE is_active = 1 ORDER BY course_name");
    $courses = $stmt->fetchAll();

} catch (PDOException $e) {
    $quizzes = [];
    $courses = [];
}

$page_title = 'Quizzes Management';
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

        .quiz-card {
            transition: transform 0.3s ease;
            border-left: 4px solid #e74c3c;
        }

        .quiz-card:hover {
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

        .question-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 3px solid #007bff;
        }

        .option-item {
            padding: 0.5rem;
            margin: 0.25rem 0;
            border-radius: 4px;
        }

        .option-correct {
            background: #d4edda;
            color: #155724;
            font-weight: bold;
        }

        .option-incorrect {
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
        <li><a href="courses.php"><i class="fas fa-book"></i>Courses</a></li>
        <li><a href="quizzes.php" class="active"><i class="fas fa-clipboard-list"></i>Quizzes</a></li>
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
            <h2><i class="fas fa-clipboard-list me-2"></i>Quizzes Management</h2>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addQuizModal">
                <i class="fas fa-plus me-2"></i>Create New Quiz
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

        <!-- Quizzes Grid -->
        <?php if (empty($quizzes)): ?>
            <div class="text-center py-5">
                <i class="fas fa-clipboard-list text-muted" style="font-size: 4rem;"></i>
                <h5 class="text-muted mt-3">No quizzes found</h5>
                <p class="text-muted">Start by creating your first quiz</p>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addQuizModal">
                    <i class="fas fa-plus me-2"></i>Create First Quiz
                </button>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($quizzes as $quiz): ?>
                    <div class="col-lg-6 col-xl-4 mb-4">
                        <div class="card quiz-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0"><?= htmlspecialchars($quiz['title']) ?></h6>
                                <?php if ($quiz['is_active']): ?>
                                    <span class="status-badge status-active">Active</span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">Inactive</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <?php if ($quiz['course_name']): ?>
                                    <span class="badge bg-primary mb-2"><?= htmlspecialchars($quiz['course_name']) ?></span>
                                <?php endif; ?>

                                <p class="card-text text-muted"><?= htmlspecialchars(substr($quiz['description'] ?? '', 0, 100)) ?>...</p>

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
                                            <div class="h6 mb-0 text-success"><?= $quiz['total_attempts'] ?></div>
                                            <small class="text-muted">Attempts</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-target me-1"></i>Passing Score: <?= $quiz['passing_score'] ?>%
                                    </small>
                                </div>

                                <?php if ($quiz['avg_score']): ?>
                                    <div class="progress mb-2" style="height: 20px;">
                                        <div class="progress-bar bg-info" style="width: <?= $quiz['avg_score'] ?>%">
                                            Avg: <?= number_format($quiz['avg_score'], 1) ?>%
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="btn-group w-100 mb-2">
                                    <button class="btn btn-outline-info btn-sm" onclick="viewQuestions(<?= $quiz['id'] ?>)" title="View Questions">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-outline-success btn-sm" onclick="addQuestion(<?= $quiz['id'] ?>)" title="Add Question">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <?php if ($quiz['is_active']): ?>
                                        <a href="?action=deactivate&id=<?= $quiz['id'] ?>"
                                           class="btn btn-outline-warning btn-sm"
                                           onclick="return confirm('Deactivate this quiz?')"
                                           title="Deactivate">
                                            <i class="fas fa-pause"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="?action=activate&id=<?= $quiz['id'] ?>"
                                           class="btn btn-outline-success btn-sm"
                                           title="Activate">
                                            <i class="fas fa-play"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="?action=delete&id=<?= $quiz['id'] ?>"
                                       class="btn btn-outline-danger btn-sm"
                                       onclick="return confirm('Are you sure you want to delete this quiz?')"
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

<!-- Add Quiz Modal -->
<div class="modal fade" id="addQuizModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Create New Quiz</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_quiz">

                    <div class="mb-3">
                        <label for="title" class="form-label">Quiz Title *</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="course_id" class="form-label">Course</label>
                            <select class="form-select" id="course_id" name="course_id">
                                <option value="">Select Course (Optional)</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="time_limit" class="form-label">Time Limit (minutes)</label>
                            <input type="number" class="form-control" id="time_limit" name="time_limit" value="30" min="5" max="180">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="passing_score" class="form-label">Passing Score (%)</label>
                        <input type="number" class="form-control" id="passing_score" name="passing_score" value="60" min="0" max="100">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Create Quiz
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Question Modal -->
<div class="modal fade" id="addQuestionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Question</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_question">
                    <input type="hidden" name="quiz_id" id="question_quiz_id">

                    <div class="mb-3">
                        <label for="question_text" class="form-label">Question Text *</label>
                        <textarea class="form-control" id="question_text" name="question_text" rows="3" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Answer Options *</label>

                        <div class="mb-2">
                            <div class="input-group">
                                <span class="input-group-text">A.</span>
                                <input type="text" class="form-control" name="option_a" required>
                            </div>
                        </div>

                        <div class="mb-2">
                            <div class="input-group">
                                <span class="input-group-text">B.</span>
                                <input type="text" class="form-control" name="option_b" required>
                            </div>
                        </div>

                        <div class="mb-2">
                            <div class="input-group">
                                <span class="input-group-text">C.</span>
                                <input type="text" class="form-control" name="option_c">
                            </div>
                        </div>

                        <div class="mb-2">
                            <div class="input-group">
                                <span class="input-group-text">D.</span>
                                <input type="text" class="form-control" name="option_d">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="correct_answer" class="form-label">Correct Answer *</label>
                            <select class="form-select" id="correct_answer" name="correct_answer" required>
                                <option value="">Select Correct Answer</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="points" class="form-label">Points</label>
                            <input type="number" class="form-control" id="points" name="points" value="1" min="1" max="10">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-2"></i>Add Question
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Questions Modal -->
<div class="modal fade" id="viewQuestionsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-list me-2"></i>Quiz Questions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="questionsModalBody">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function addQuestion(quizId) {
        document.getElementById('question_quiz_id').value = quizId;
        new bootstrap.Modal(document.getElementById('addQuestionModal')).show();
    }

    function viewQuestions(quizId) {
        fetch(`../ajax/get-quiz-questions.php?id=${quizId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('questionsModalBody').innerHTML = data.html;
                    new bootstrap.Modal(document.getElementById('viewQuestionsModal')).show();
                } else {
                    alert('Failed to load questions');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading questions');
            });
    }

    function deleteQuestion(questionId) {
        if (confirm('Are you sure you want to delete this question?')) {
            window.location.href = `?action=delete_question&question_id=${questionId}`;
        }
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