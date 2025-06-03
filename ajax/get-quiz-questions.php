<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if request is AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    die('Direct access not allowed');
}

$quiz_id = (int)($_GET['id'] ?? 0);

if (!$quiz_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid quiz ID']);
    exit();
}

try {
    // Get quiz info
    $stmt = $pdo->prepare("SELECT title FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();

    if (!$quiz) {
        echo json_encode(['success' => false, 'message' => 'Quiz not found']);
        exit();
    }

    // Get questions
    $stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE quiz_id = ? ORDER BY id");
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll();

    $html = '<h6 class="mb-3">Quiz: ' . htmlspecialchars($quiz['title']) . '</h6>';

    if (empty($questions)) {
        $html .= '<div class="text-center py-4">
                    <i class="fas fa-question-circle text-muted" style="font-size: 3rem;"></i>
                    <h6 class="text-muted mt-3">No questions added yet</h6>
                    <p class="text-muted">Start by adding your first question</p>
                  </div>';
    } else {
        foreach ($questions as $index => $question) {
            $html .= '<div class="question-item">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6>Question ' . ($index + 1) . ' (' . $question['points'] . ' point' . ($question['points'] > 1 ? 's' : '') . ')</h6>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteQuestion(' . $question['id'] . ')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <p class="mb-3">' . htmlspecialchars($question['question_text']) . '</p>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="option-item ' . ($question['correct_answer'] == 'A' ? 'option-correct' : 'option-incorrect') . '">
                                    <strong>A.</strong> ' . htmlspecialchars($question['option_a']) . '
                                    ' . ($question['correct_answer'] == 'A' ? '<i class="fas fa-check float-end"></i>' : '') . '
                                </div>
                                <div class="option-item ' . ($question['correct_answer'] == 'B' ? 'option-correct' : 'option-incorrect') . '">
                                    <strong>B.</strong> ' . htmlspecialchars($question['option_b']) . '
                                    ' . ($question['correct_answer'] == 'B' ? '<i class="fas fa-check float-end"></i>' : '') . '
                                </div>
                            </div>
                            <div class="col-md-6">';

            if (!empty($question['option_c'])) {
                $html .= '<div class="option-item ' . ($question['correct_answer'] == 'C' ? 'option-correct' : 'option-incorrect') . '">
                            <strong>C.</strong> ' . htmlspecialchars($question['option_c']) . '
                            ' . ($question['correct_answer'] == 'C' ? '<i class="fas fa-check float-end"></i>' : '') . '
                          </div>';
            }

            if (!empty($question['option_d'])) {
                $html .= '<div class="option-item ' . ($question['correct_answer'] == 'D' ? 'option-correct' : 'option-incorrect') . '">
                            <strong>D.</strong> ' . htmlspecialchars($question['option_d']) . '
                            ' . ($question['correct_answer'] == 'D' ? '<i class="fas fa-check float-end"></i>' : '') . '
                          </div>';
            }

            $html .= '    </div>
                        </div>
                      </div>';
        }
    }

    echo json_encode([
        'success' => true,
        'html' => $html
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>