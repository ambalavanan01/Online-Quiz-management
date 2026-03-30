<?php
// manage_questions.php
require_once 'includes/auth.php';
requireTeacher();

global $db;
$quiz_id = $_GET['quiz_id'] ?? null;
if (!$quiz_id) {
    header("Location: manage_quizzes.php");
    exit;
}

$quiz = $db->findById('quizzes', $quiz_id);
if (!$quiz) {
    header("Location: manage_quizzes.php");
    exit;
}

if (!isAdmin() && $quiz['created_by'] !== $_SESSION['user_id']) {
    die("Unauthorized access to this quiz.");
}

// Ensure uploads dir
$upload_dir = __DIR__ . '/uploads/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_question') {
        $type = $_POST['question_type'] ?? 'mcq';
        $text = trim($_POST['text']);
        
        $options = [];
        $correct = null;
        $correct_text = null;
        
        if ($type === 'mcq') {
            $options = [
                trim($_POST['opt1'] ?? ''),
                trim($_POST['opt2'] ?? ''),
                trim($_POST['opt3'] ?? ''),
                trim($_POST['opt4'] ?? '')
            ];
            $correct = (int)($_POST['correct_option'] ?? 0);
        } elseif ($type === 'tf') {
            $options = ['True', 'False'];
            $correct = (int)($_POST['correct_tf'] ?? 0);
        } elseif ($type === 'short_answer') {
            $correct_text = trim($_POST['correct_text'] ?? '');
        }

        $media_path = null;
        $media_type = null;

        if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
            $filename = uniqid() . '.' . $ext;
            $dest = $upload_dir . $filename;
            
            $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'mp3', 'wav', 'mp4', 'webm'];
            if (in_array($ext, $allowed_exts)) {
                if (move_uploaded_file($_FILES['media']['tmp_name'], $dest)) {
                    $media_path = 'uploads/' . $filename;
                    
                    if (in_array($ext, ['mp3', 'wav'])) {
                        $media_type = 'audio';
                    } elseif (in_array($ext, ['mp4', 'webm'])) {
                        $media_type = 'video';
                    } else {
                        $media_type = 'image';
                    }
                }
            }
        }

        $db->insert('questions', [
            'quiz_id' => $quiz_id,
            'type' => $type,
            'text' => $text,
            'media_path' => $media_path,
            'media_type' => $media_type,
            'options' => $options,
            'correct_option' => $correct,
            'correct_text' => $correct_text
        ]);
        header("Location: manage_questions.php?quiz_id=" . $quiz_id);
        exit;
    } elseif ($_POST['action'] === 'delete_question') {
        $q = $db->findById('questions', $_POST['question_id']);
        if ($q && !empty($q['media_path']) && file_exists(__DIR__ . '/' . $q['media_path'])) {
            unlink(__DIR__ . '/' . $q['media_path']);
        }
        $db->delete('questions', $_POST['question_id']);
        header("Location: manage_questions.php?quiz_id=" . $quiz_id);
        exit;
    }
}

$questions = $db->findWhere('questions', 'quiz_id', $quiz_id);
require_once 'includes/header.php';
?>

<div class="card">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2 class="card-title">Manage Questions: <?php echo htmlspecialchars($quiz['title']); ?></h2>
        <a href="manage_quizzes.php" class="btn btn-secondary" style="background:#e2e8f0; color:#1e293b;">Back to Quizzes</a>
    </div>
    <div style="display:flex; gap:10px; margin-top:10px; font-size: 0.9rem; color: var(--text-secondary);">
        <span>Difficulty: <strong><?php echo htmlspecialchars($quiz['difficulty']); ?></strong></span> | 
        <span>Penalty: <strong>-<?php echo $quiz['negative_marking']; ?> pts</strong></span>
    </div>
</div>

<div class="card">
    <h3 class="card-title">Add New Question</h3>
    <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add_question">
        
        <div class="form-group">
            <label class="form-label">Question Type</label>
            <select name="question_type" id="question_type" class="form-control" onchange="toggleQuestionType()">
                <option value="mcq">Multiple Choice</option>
                <option value="tf">True / False</option>
                <option value="short_answer">Short Answer / Fill in the blank</option>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">Question Text</label>
            <textarea name="text" class="form-control" rows="3" required></textarea>
        </div>
        
        <div class="form-group">
            <label class="form-label">Upload Media (Optional: Image, Audio, or Video)</label>
            <input type="file" name="media" class="form-control" accept="image/*,audio/mpeg,audio/wav,video/mp4,video/webm">
            <small style="color:var(--text-secondary);">Formats supported: JPG, PNG, MP3, WAV, MP4, WebM</small>
        </div>

        <div id="mcq_section">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label">Option 1</label>
                    <input type="text" name="opt1" class="form-control" id="opt1">
                </div>
                <div class="form-group">
                    <label class="form-label">Option 2</label>
                    <input type="text" name="opt2" class="form-control" id="opt2">
                </div>
                <div class="form-group">
                    <label class="form-label">Option 3</label>
                    <input type="text" name="opt3" class="form-control" id="opt3">
                </div>
                <div class="form-group">
                    <label class="form-label">Option 4</label>
                    <input type="text" name="opt4" class="form-control" id="opt4">
                </div>
            </div>

            <div class="form-group" style="margin-top: 15px;">
                <label class="form-label">Correct Option</label>
                <select name="correct_option" class="form-control">
                    <option value="0">Option 1</option>
                    <option value="1">Option 2</option>
                    <option value="2">Option 3</option>
                    <option value="3">Option 4</option>
                </select>
            </div>
        </div>

        <div id="tf_section" style="display:none; margin-top: 15px;">
            <div class="form-group">
                <label class="form-label">Correct Answer</label>
                <select name="correct_tf" class="form-control">
                    <option value="0">True</option>
                    <option value="1">False</option>
                </select>
            </div>
        </div>

        <div id="sa_section" style="display:none; margin-top: 15px;">
            <div class="form-group">
                <label class="form-label">Expected Answer text (Case insensitive)</label>
                <input type="text" name="correct_text" class="form-control" id="correct_text" placeholder="e.g. Paris">
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top: 15px;">Save Question</button>

    </form>
</div>

<script>
function toggleQuestionType() {
    const type = document.getElementById('question_type').value;
    const mcq = document.getElementById('mcq_section');
    const tf = document.getElementById('tf_section');
    const sa = document.getElementById('sa_section');
    
    mcq.style.display = 'none';
    tf.style.display = 'none';
    sa.style.display = 'none';
    
    // reset required attributes
    document.getElementById('opt1').required = false;
    document.getElementById('opt2').required = false;
    document.getElementById('opt3').required = false;
    document.getElementById('opt4').required = false;
    document.getElementById('correct_text').required = false;

    if (type === 'mcq') {
        mcq.style.display = 'block';
        document.getElementById('opt1').required = true;
        document.getElementById('opt2').required = true;
        document.getElementById('opt3').required = true;
        document.getElementById('opt4').required = true;
    } else if (type === 'tf') {
        tf.style.display = 'block';
    } else if (type === 'short_answer') {
        sa.style.display = 'block';
        document.getElementById('correct_text').required = true;
    }
}
// Init
toggleQuestionType();
</script>

<div class="card">
    <h3 class="card-title">Existing Questions</h3>
    <?php foreach ($questions as $index => $q): ?>
        <div style="border:1px solid var(--border-color); border-radius:6px; padding:15px; margin-bottom:15px;">
            <div style="display:flex; justify-content:space-between;">
                <strong>Q<?php echo $index + 1; ?>: <?php echo nl2br(htmlspecialchars($q['text'])); ?></strong>
                <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Delete question?');">
                    <input type="hidden" name="action" value="delete_question">
                    <input type="hidden" name="question_id" value="<?php echo $q['id']; ?>">
                    <button type="submit" class="btn btn-danger" style="padding:4px 8px; font-size:0.8rem;">Delete</button>
                </form>
            </div>
            
            <?php if (!empty($q['media_path'])): ?>
                <div style="margin-top:10px;">
                    <?php if ($q['media_type'] === 'image'): ?>
                        <img src="<?php echo htmlspecialchars($q['media_path']); ?>" alt="Question Media" style="max-height:150px; border-radius:4px;">
                    <?php elseif ($q['media_type'] === 'audio'): ?>
                        <audio controls style="width: 100%; max-width: 400px;">
                            <source src="<?php echo htmlspecialchars($q['media_path']); ?>">
                        </audio>
                    <?php elseif ($q['media_type'] === 'video'): ?>
                        <video controls style="max-height:200px; max-width: 100%; border-radius:4px;">
                            <source src="<?php echo htmlspecialchars($q['media_path']); ?>">
                        </video>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!isset($q['type']) || $q['type'] === 'mcq' || $q['type'] === 'tf'): ?>
                <ul style="margin-top:10px; padding-left:20px;">
                    <?php if(isset($q['options']) && is_array($q['options'])): ?>
                        <?php foreach ($q['options'] as $i => $opt): ?>
                            <li style="<?php echo (isset($q['correct_option']) && $q['correct_option'] == $i) ? 'color:var(--success-color); font-weight:bold;' : ''; ?>">
                                <?php echo htmlspecialchars($opt); ?>
                                <?php echo (isset($q['correct_option']) && $q['correct_option'] == $i) ? ' (Correct)' : ''; ?>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            <?php elseif ($q['type'] === 'short_answer'): ?>
                <div style="margin-top:10px;">
                    <span style="color:var(--text-secondary);">Expected Match:</span> 
                    <strong style="color:var(--success-color);"><?php echo htmlspecialchars($q['correct_text'] ?? ''); ?></strong>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <?php if (empty($questions)): ?>
        <p>No questions added yet.</p>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
