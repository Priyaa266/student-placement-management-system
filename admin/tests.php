<?php
/**
 * Test Management & Question Bank Editor
 * Student Placement Management System
 */
require_once __DIR__ . '/../config/database.php';
require_role('admin');

// 1. Handle Add New Aptitude Test (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_test'])) {
    $title = trim($_POST['title'] ?? '');
    $category = $_POST['category'] ?? 'Comprehensive';
    $duration = intval($_POST['duration_minutes'] ?? 20);
    $total_marks = intval($_POST['total_marks'] ?? 20);
    $pass_marks = intval($_POST['pass_marks'] ?? 10);
    $drive_id = !empty($_POST['drive_id']) ? intval($_POST['drive_id']) : null;

    if (empty($title)) {
        set_flash('danger', 'Please enter a test title.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO aptitude_tests (drive_id, title, category, duration_minutes, total_marks, pass_marks) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$drive_id, $title, $category, $duration, $total_marks, $pass_marks]);
            set_flash('success', "Aptitude Assessment '{$title}' created successfully.");
            header('Location: ' . base_url('admin/tests.php'));
            exit;
        } catch (Exception $e) {
            set_flash('danger', 'Error creating test: ' . $e->getMessage());
        }
    }
}

// 2. Handle Add MCQ Question (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
    $test_id = intval($_POST['test_id'] ?? 0);
    $question_text = trim($_POST['question_text'] ?? '');
    $category = $_POST['q_category'] ?? 'Quantitative Aptitude';
    $opt_a = trim($_POST['option_a'] ?? '');
    $opt_b = trim($_POST['option_b'] ?? '');
    $opt_c = trim($_POST['option_c'] ?? '');
    $opt_d = trim($_POST['option_d'] ?? '');
    $correct = strtoupper(trim($_POST['correct_option'] ?? 'A'));
    $marks = intval($_POST['marks'] ?? 1);
    $explanation = trim($_POST['explanation'] ?? '');

    if ($test_id <= 0 || empty($question_text) || empty($opt_a) || empty($opt_b)) {
        set_flash('danger', 'Please provide the question text and options.');
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO aptitude_questions 
                (test_id, question_text, category, option_a, option_b, option_c, option_d, correct_option, marks, explanation) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$test_id, $question_text, $category, $opt_a, $opt_b, $opt_c, $opt_d, $correct, $marks, $explanation]);
            set_flash('success', 'Question added to assessment question bank successfully!');
            header('Location: ' . base_url('admin/tests.php'));
            exit;
        } catch (Exception $e) {
            set_flash('danger', 'Error saving question: ' . $e->getMessage());
        }
    }
}

// Fetch all aptitude tests with question counts
$apt_tests = $pdo->query("SELECT at.*, COUNT(aq.id) as question_count, c.name as company_name 
                          FROM aptitude_tests at 
                          LEFT JOIN aptitude_questions aq ON at.id = aq.test_id 
                          LEFT JOIN placement_drives d ON at.drive_id = d.id 
                          LEFT JOIN companies c ON d.company_id = c.id 
                          GROUP BY at.id 
                          ORDER BY at.created_at DESC")->fetchAll();

// Fetch coding tests
$coding_tests = $pdo->query("SELECT ct.*, COUNT(cq.id) as question_count, c.name as company_name 
                             FROM coding_tests ct 
                             LEFT JOIN coding_questions cq ON ct.id = cq.test_id 
                             LEFT JOIN placement_drives d ON ct.drive_id = d.id 
                             LEFT JOIN companies c ON d.company_id = c.id 
                             GROUP BY ct.id 
                             ORDER BY ct.created_at DESC")->fetchAll();

// Fetch drives for dropdown
$drives = $pdo->query("SELECT d.id, d.job_role, c.name as company_name FROM placement_drives d JOIN companies c ON d.company_id = c.id ORDER BY c.name ASC")->fetchAll();

$page_title = 'Manage Placement Tests & Questions';
$flash = get_flash();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="app-wrapper">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    
    <div class="app-main">
        <?php include __DIR__ . '/../includes/navbar.php'; ?>

        <div class="app-content">

            <?php if ($flash): ?>
                <div class="alert alert-<?= e($flash['type']) ?>">
                    <?= e($flash['message']) ?>
                </div>
            <?php endif; ?>

            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
                <div>
                    <h2 style="font-size: 22px; font-weight: 800; color: var(--text-main);">Campus Assessment Management</h2>
                    <p style="color: var(--text-muted); font-size: 13.5px;">
                        Manage aptitude test question banks, proctoring time limits, and coding problem specifications.
                    </p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="<?= base_url('admin/results.php') ?>" class="btn btn-outline">Student Scores Log →</a>
                </div>
            </div>

            <!-- Existing Aptitude Tests -->
            <div class="card" style="margin-bottom: 28px;">
                <div class="card-header">
                    <div class="card-title">📝 Aptitude Tests (<?= count($apt_tests) ?>)</div>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Test Title</th>
                                <th>Category</th>
                                <th>Affiliated Drive</th>
                                <th>Duration</th>
                                <th>Total Marks</th>
                                <th>Pass Cutoff</th>
                                <th>Questions Loaded</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($apt_tests as $at): ?>
                                <tr>
                                    <td><strong><?= e($at['title']) ?></strong></td>
                                    <td><span class="status-badge badge-primary"><?= e($at['category']) ?></span></td>
                                    <td><?= e($at['company_name'] ?: 'Campus Practice Pool') ?></td>
                                    <td>⏱️ <?= $at['duration_minutes'] ?> mins</td>
                                    <td><?= $at['total_marks'] ?> pts</td>
                                    <td><strong><?= $at['pass_marks'] ?> pts</strong></td>
                                    <td>
                                        <span class="status-badge badge-success"><?= $at['question_count'] ?> MCQs</span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-outline btn-sm" onclick="selectTestForQuestion(<?= $at['id'] ?>)">
                                            + Add Question
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Existing Coding Tests -->
            <div class="card" style="margin-bottom: 32px;">
                <div class="card-header">
                    <div class="card-title">💻 Technical Coding Assessments (<?= count($coding_tests) ?>)</div>
                </div>

                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Coding Assessment</th>
                                <th>Drive</th>
                                <th>Duration</th>
                                <th>Total Marks</th>
                                <th>Pass Cutoff</th>
                                <th>Problems</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($coding_tests as $ct): ?>
                                <tr>
                                    <td><strong><?= e($ct['title']) ?></strong></td>
                                    <td><?= e($ct['company_name'] ?: 'Institutional Benchmark') ?></td>
                                    <td>⏱️ <?= $ct['duration_minutes'] ?> mins</td>
                                    <td><?= $ct['total_marks'] ?> pts</td>
                                    <td><?= $ct['pass_marks'] ?> pts</td>
                                    <td><span class="status-badge badge-purple"><?= $ct['question_count'] ?> Problems</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Two-Column Layout: Add Question vs Create Test -->
            <div class="grid-cols-2">

                <!-- Left: Add MCQ Question to Test -->
                <div class="card" id="add-question-box">
                    <div class="card-header">
                        <div class="card-title">❓ Add MCQ Question to Assessment</div>
                    </div>

                    <form method="POST" action="<?= base_url('admin/tests.php') ?>">
                        <input type="hidden" name="add_question" value="1">

                        <div class="form-group">
                            <label class="form-label">Target Aptitude Test <span class="req">*</span></label>
                            <select name="test_id" id="target-test-select" class="form-control" required>
                                <option value="">-- Select Test --</option>
                                <?php foreach ($apt_tests as $at): ?>
                                    <option value="<?= $at['id'] ?>"><?= e($at['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="grid-cols-2">
                            <div class="form-group">
                                <label class="form-label">Category</label>
                                <select name="q_category" class="form-control">
                                    <option value="Quantitative Aptitude">Quantitative Aptitude</option>
                                    <option value="Logical Reasoning">Logical Reasoning</option>
                                    <option value="Verbal Ability">Verbal Ability</option>
                                    <option value="Data Interpretation">Data Interpretation</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Marks Awarded</label>
                                <input type="number" name="marks" class="form-control" value="1" min="1" max="10" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Question Text <span class="req">*</span></label>
                            <textarea name="question_text" class="form-control" rows="3" placeholder="Enter problem statement..." required></textarea>
                        </div>

                        <div class="grid-cols-2">
                            <div class="form-group">
                                <label class="form-label">Option A <span class="req">*</span></label>
                                <input type="text" name="option_a" class="form-control" placeholder="Choice A" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Option B <span class="req">*</span></label>
                                <input type="text" name="option_b" class="form-control" placeholder="Choice B" required>
                            </div>
                        </div>

                        <div class="grid-cols-2">
                            <div class="form-group">
                                <label class="form-label">Option C <span class="req">*</span></label>
                                <input type="text" name="option_c" class="form-control" placeholder="Choice C" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Option D <span class="req">*</span></label>
                                <input type="text" name="option_d" class="form-control" placeholder="Choice D" required>
                            </div>
                        </div>

                        <div class="grid-cols-2">
                            <div class="form-group">
                                <label class="form-label">Correct Option <span class="req">*</span></label>
                                <select name="correct_option" class="form-control" required>
                                    <option value="A">Option A</option>
                                    <option value="B">Option B</option>
                                    <option value="C">Option C</option>
                                    <option value="D">Option D</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Answer Explanation</label>
                                <input type="text" name="explanation" class="form-control" placeholder="Optional step-by-step rationale...">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            Save MCQ Question to Database
                        </button>
                    </form>
                </div>

                <!-- Right: Create New Aptitude Test Container -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">📝 Create New Aptitude Assessment</div>
                    </div>

                    <form method="POST" action="<?= base_url('admin/tests.php') ?>">
                        <input type="hidden" name="create_test" value="1">

                        <div class="form-group">
                            <label class="form-label">Assessment Title <span class="req">*</span></label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Amazon Analytical Screening Round" required>
                        </div>

                        <div class="grid-cols-2">
                            <div class="form-group">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-control">
                                    <option value="Comprehensive">Comprehensive</option>
                                    <option value="Quantitative Aptitude">Quantitative Aptitude</option>
                                    <option value="Logical Reasoning">Logical Reasoning</option>
                                    <option value="Verbal Ability">Verbal Ability</option>
                                    <option value="Data Interpretation">Data Interpretation</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Affiliated Drive</label>
                                <select name="drive_id" class="form-control">
                                    <option value="">General Campus Practice</option>
                                    <?php foreach ($drives as $dr): ?>
                                        <option value="<?= $dr['id'] ?>"><?= e($dr['company_name']) ?> - <?= e($dr['job_role']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="grid-cols-3">
                            <div class="form-group">
                                <label class="form-label">Duration (Mins) <span class="req">*</span></label>
                                <input type="number" name="duration_minutes" class="form-control" value="20" min="5" max="180" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Total Marks <span class="req">*</span></label>
                                <input type="number" name="total_marks" class="form-control" value="20" min="5" max="100" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Pass Marks <span class="req">*</span></label>
                                <input type="number" name="pass_marks" class="form-control" value="10" min="1" max="100" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-block">
                            Create Assessment Configuration
                        </button>
                    </form>
                </div>

            </div>

        </div>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</div>

<script>
function selectTestForQuestion(testId) {
    const sel = document.getElementById('target-test-select');
    if (sel) {
        sel.value = testId;
    }
    document.getElementById('add-question-box').scrollIntoView({behavior: 'smooth'});
}
</script>
