<?php
require_once __DIR__ . '/include/bootstrap.php';
require_once __DIR__ . '/include/layout.php';

$gsaUser = gsa_require_login();

function gsa_memo_escape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function gsa_memo_plain_to_html($value)
{
    $value = trim((string)$value);
    if (preg_match('/<\/?[a-z][\s\S]*>/i', $value)) {
        return $value;
    }

    return str_replace(array("\r\n", "\r", "\n"), '<br>', $value);
}

function gsa_memo_html_to_plain($value)
{
    return trim((string)$value);
}

function gsa_memo_fetch_one($sql, $types = '', $params = array())
{
    global $dbConn;

    $stmt = $dbConn->prepare($sql);
    if (!$stmt) {
        return null;
    }
    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        $stmt->close();
        return null;
    }
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row;
}

function gsa_memo_fetch_all($sql, $types = '', $params = array())
{
    global $dbConn;

    $stmt = $dbConn->prepare($sql);
    if (!$stmt) {
        return array();
    }
    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    if (!$stmt->execute()) {
        $stmt->close();
        return array();
    }

    $rows = array();
    $result = $stmt->get_result();
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    $stmt->close();

    return $rows;
}

function gsa_memo_stmt($sql, $types = '', $params = array())
{
    global $dbConn;

    $stmt = $dbConn->prepare($sql);
    if (!$stmt) {
        return false;
    }
    if ($types !== '' && !empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $ok = $stmt->execute();
    $stmt->close();

    return $ok;
}

if (empty($_SESSION['gsa_memo_csrf'])) {
    $_SESSION['gsa_memo_csrf'] = bin2hex(random_bytes(16));
}

$message = '';
$editRow = null;
$editNo = isset($_GET['no']) ? (int)$_GET['no'] : 0;
if ($editNo > 0) {
    $editRow = gsa_memo_fetch_one(
        "SELECT * FROM memo_board WHERE seq_no = ? LIMIT 1",
        'i',
        array($editNo)
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mode']) && $_POST['mode'] === 'save') {
    $csrfToken = isset($_POST['csrf_token']) ? trim((string)$_POST['csrf_token']) : '';
    if ($csrfToken === '' || !hash_equals($_SESSION['gsa_memo_csrf'], $csrfToken)) {
        echo "<script>alert('Invalid request.'); history.back();</script>";
        exit;
    }

    $no = isset($_POST['no']) ? (int)$_POST['no'] : 0;
    $memoDate = isset($_POST['memo_date']) ? trim((string)$_POST['memo_date']) : '';
    $content1 = isset($_POST['content1']) ? gsa_memo_plain_to_html($_POST['content1']) : '';
    $content2 = isset($_POST['content2']) ? gsa_memo_plain_to_html($_POST['content2']) : '';
    $register = isset($gsaUser['userid']) ? (string)$gsaUser['userid'] : '';
    $name = isset($gsaUser['kor_name']) && trim((string)$gsaUser['kor_name']) !== ''
        ? trim((string)$gsaUser['kor_name'])
        : $register;

    if ($memoDate === '') {
        echo "<script>alert('Please select a date.'); history.back();</script>";
        exit;
    }

    if ($no > 0) {
        $ok = gsa_memo_stmt(
            "UPDATE memo_board SET date = ?, content1 = ?, content2 = ? WHERE seq_no = ?",
            'sssi',
            array($memoDate, $content1, $content2, $no)
        );
    } else {
        $sameDateRow = gsa_memo_fetch_one(
            "SELECT seq_no, content1, content2 FROM memo_board WHERE date = ? LIMIT 1",
            's',
            array($memoDate)
        );

        if ($sameDateRow) {
            $newContent1 = trim((string)$sameDateRow['content1']);
            $newContent2 = trim((string)$sameDateRow['content2']);
            if ($content1 !== '') {
                $newContent1 .= ($newContent1 !== '' ? '<br>' : '') . $content1;
            }
            if ($content2 !== '') {
                $newContent2 .= ($newContent2 !== '' ? '<br>' : '') . $content2;
            }
            $ok = gsa_memo_stmt(
                "UPDATE memo_board SET content1 = ?, content2 = ? WHERE seq_no = ?",
                'ssi',
                array($newContent1, $newContent2, (int)$sameDateRow['seq_no'])
            );
        } else {
            $ok = gsa_memo_stmt(
                "INSERT INTO memo_board VALUES ('', ?, ?, ?, ?, ?, NOW())",
                'sssss',
                array($register, $name, $memoDate, $content1, $content2)
            );
        }
    }

    if (!$ok) {
        echo "<script>alert('Save failed.'); history.back();</script>";
        exit;
    }

    header('Location: memo.php?saved=1');
    exit;
}

$searchDate = isset($_GET['sdate']) ? trim((string)$_GET['sdate']) : '';
$whereSql = '';
$params = array();
$types = '';
if ($searchDate !== '') {
    $whereSql = 'WHERE date = ?';
    $types = 's';
    $params[] = $searchDate;
}

$rows = gsa_memo_fetch_all(
    "SELECT * FROM memo_board {$whereSql} ORDER BY date DESC, seq_no DESC LIMIT 30",
    $types,
    $params
);

$formDate = $editRow && isset($editRow['date']) ? (string)$editRow['date'] : date('Y-m-d');
$formContent1 = $editRow && isset($editRow['content1']) ? gsa_memo_html_to_plain($editRow['content1']) : '';
$formContent2 = $editRow && isset($editRow['content2']) ? gsa_memo_html_to_plain($editRow['content2']) : '';
$saved = isset($_GET['saved']) && $_GET['saved'] === '1';

gsa_layout_head('Memo');
?>
<form method="post" action="memo.php" class="gsa-card-stack pb-4">
    <input type="hidden" name="mode" value="save">
    <input type="hidden" name="csrf_token" value="<?php echo gsa_memo_escape($_SESSION['gsa_memo_csrf']); ?>">
    <input type="hidden" name="no" value="<?php echo $editRow ? (int)$editRow['seq_no'] : 0; ?>">

    <?php if ($saved) { ?>
    <div class="alert alert-success mb-0">Saved.</div>
    <?php } ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <div class="small text-muted">Memo entry</div>
                    <div class="fw-semibold"><?php echo $editRow ? 'Edit memo' : 'New memo'; ?></div>
                </div>
                <a href="my.php" class="btn btn-outline-secondary btn-sm">My settle</a>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold mb-1" for="memo_date">Date</label>
                <input type="date" class="form-control" id="memo_date" name="memo_date" value="<?php echo gsa_memo_escape($formDate); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold mb-1" for="content1">Memo 1</label>
                <textarea class="form-control gsa-textarea-lg" id="content1" name="content1"><?php echo gsa_memo_escape($formContent1); ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-semibold mb-1" for="content2">Memo 2</label>
                <textarea class="form-control gsa-textarea-lg" id="content2" name="content2"><?php echo gsa_memo_escape($formContent2); ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-lg w-100">Save memo</button>
        </div>
    </div>
</form>

<section id="memoList" class="gsa-card-stack pb-5">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="get" action="memo.php" class="d-flex gap-2">
                <input type="date" class="form-control" name="sdate" value="<?php echo gsa_memo_escape($searchDate); ?>">
                <button type="submit" class="btn btn-outline-primary">Search</button>
            </form>
        </div>
    </div>

    <?php if (empty($rows)) { ?>
    <div class="gsa-empty-state">No memos found.</div>
    <?php } else { ?>
        <?php foreach ($rows as $row) { ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div class="fw-semibold"><?php echo gsa_memo_escape(isset($row['date']) ? $row['date'] : ''); ?></div>
                        <div class="small text-muted"><?php echo gsa_memo_escape(isset($row['name']) ? $row['name'] : ''); ?> (<?php echo gsa_memo_escape(isset($row['register']) ? $row['register'] : ''); ?>)</div>
                    </div>
                    <a href="memo.php?no=<?php echo (int)$row['seq_no']; ?>" class="btn btn-outline-secondary btn-sm">Edit</a>
                </div>
                <div class="gsa-memo-box gsa-memo-view mt-3">
                    <div class="gsa-memo-view-title">Memo 1</div>
                    <div class="text-break gsa-memo-html"><?php echo isset($row['content1']) ? $row['content1'] : ''; ?></div>
                </div>
                <div class="gsa-memo-box gsa-memo-view mt-2">
                    <div class="gsa-memo-view-title">Memo 2</div>
                    <div class="text-break gsa-memo-html"><?php echo isset($row['content2']) ? $row['content2'] : ''; ?></div>
                </div>
            </div>
        </div>
        <?php } ?>
    <?php } ?>
</section>
<script src="../admin/ckeditor/ckeditor.js"></script>
<script>
if (window.CKEDITOR) {
    var gsaMemoEditorConfig = {
        allowedContent: true,
        enterMode: CKEDITOR.ENTER_BR,
        height: 260,
        removeButtons: '',
        toolbar: [
            { name: 'mode', items: ['Source'] },
            { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'RemoveFormat'] },
            { name: 'paragraph', items: ['NumberedList', 'BulletedList'] },
            { name: 'links', items: ['Link', 'Unlink'] },
            { name: 'insert', items: ['Table', 'HorizontalRule'] },
            { name: 'styles', items: ['Format'] }
        ]
    };

    CKEDITOR.replace('content1', gsaMemoEditorConfig);
    CKEDITOR.replace('content2', gsaMemoEditorConfig);
}
</script>
<?php
gsa_layout_foot();
