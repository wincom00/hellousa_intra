<?php
include "include/header.php";
require_once __DIR__ . "/include/arap_common.php";

if (empty($_COOKIE['MEMLOGIN_ADMIN_HELLO'])) {
    echo "<meta http-equiv='refresh' content='0; url=./login.php'>";
    exit;
}

if ($division == "") {
    $division = '11';
}
if ($pdx == "") {
    $pdx = '1';
}
if ($sub == "") {
    $sub = '30';
}

if (!hasMenuAccess($division, $pdx, $sub)) {
    $goUrl_1 = "index.php";
    Misc::jvAlert("권한이 있는 메뉴가 아닙니다. 확인 후 사용해 주세요.","");
    echo "<meta http-equiv='refresh' content='0; url=$goUrl_1'>";
    exit;
}

$message = '';
$regId = isset($user_dbinfo['userid']) ? $user_dbinfo['userid'] : '';

if ($mode == 'save_category') {
    $catId = isset($_POST['cat_id']) ? (int)$_POST['cat_id'] : 0;
    $catType = isset($_POST['cat_type']) ? trim((string)$_POST['cat_type']) : 'OUT';
    $catName = isset($_POST['cat_name']) ? trim((string)$_POST['cat_name']) : '';
    $sortOrder = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
    $useYn = isset($_POST['use_yn']) ? trim((string)$_POST['use_yn']) : 'Y';

    if ($catId > 0) {
        $stmt = $dbConn->prepare("UPDATE arap_category SET cat_type = ?, cat_name = ?, sort_order = ?, use_yn = ? WHERE cat_id = ?");
        $stmt->bind_param('ssisi', $catType, $catName, $sortOrder, $useYn, $catId);
    } else {
        $stmt = $dbConn->prepare("INSERT INTO arap_category (cat_type, cat_name, sort_order, use_yn) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssis', $catType, $catName, $sortOrder, $useYn);
    }
    $stmt->execute();
    $stmt->close();
    $message = '대분류가 저장되었습니다.';
}

if ($mode == 'save_subcategory') {
    $subId = isset($_POST['sub_id']) ? (int)$_POST['sub_id'] : 0;
    $catId = isset($_POST['parent_cat_id']) ? (int)$_POST['parent_cat_id'] : 0;
    $subName = isset($_POST['sub_name']) ? trim((string)$_POST['sub_name']) : '';
    $sortOrder = isset($_POST['sub_sort_order']) ? (int)$_POST['sub_sort_order'] : 0;
    $useYn = isset($_POST['sub_use_yn']) ? trim((string)$_POST['sub_use_yn']) : 'Y';

    if ($subId > 0) {
        $stmt = $dbConn->prepare("UPDATE arap_subcategory SET cat_id = ?, sub_name = ?, sort_order = ?, use_yn = ? WHERE sub_id = ?");
        $stmt->bind_param('isisi', $catId, $subName, $sortOrder, $useYn, $subId);
    } else {
        $stmt = $dbConn->prepare("INSERT INTO arap_subcategory (cat_id, sub_name, sort_order, use_yn) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('isis', $catId, $subName, $sortOrder, $useYn);
    }
    $stmt->execute();
    $stmt->close();
    $message = '소분류가 저장되었습니다.';
}

if ($mode == 'save_method') {
    $methodId = isset($_POST['method_id']) ? (int)$_POST['method_id'] : 0;
    $methodType = isset($_POST['method_type']) ? trim((string)$_POST['method_type']) : 'OUT';
    $methodName = isset($_POST['method_name']) ? trim((string)$_POST['method_name']) : '';
    $sortOrder = isset($_POST['method_sort_order']) ? (int)$_POST['method_sort_order'] : 0;
    $useYn = isset($_POST['method_use_yn']) ? trim((string)$_POST['method_use_yn']) : 'Y';

    if ($methodId > 0) {
        $stmt = $dbConn->prepare("UPDATE arap_method SET method_type = ?, method_name = ?, sort_order = ?, use_yn = ? WHERE method_id = ?");
        $stmt->bind_param('ssisi', $methodType, $methodName, $sortOrder, $useYn, $methodId);
    } else {
        $stmt = $dbConn->prepare("INSERT INTO arap_method (method_type, method_name, sort_order, use_yn) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssis', $methodType, $methodName, $sortOrder, $useYn);
    }
    $stmt->execute();
    $stmt->close();
    $message = '거래수단이 저장되었습니다.';
}

if ($mode == 'save_bank') {
    $bankId = isset($_POST['bank_id']) ? (int)$_POST['bank_id'] : 0;
    $bankName = isset($_POST['bank_name']) ? trim((string)$_POST['bank_name']) : '';
    $sortOrder = isset($_POST['bank_sort_order']) ? (int)$_POST['bank_sort_order'] : 0;
    $useYn = isset($_POST['bank_use_yn']) ? trim((string)$_POST['bank_use_yn']) : 'Y';

    if ($bankName === '') {
        $message = '은행명을 입력해 주세요.';
    } else {
        if ($bankId > 0) {
            $stmt = $dbConn->prepare("UPDATE arap_bank SET bank_name = ?, sort_order = ?, use_yn = ? WHERE bank_id = ?");
            $stmt->bind_param('sisi', $bankName, $sortOrder, $useYn, $bankId);
        } else {
            $stmt = $dbConn->prepare("INSERT INTO arap_bank (bank_name, sort_order, use_yn) VALUES (?, ?, ?)");
            $stmt->bind_param('sis', $bankName, $sortOrder, $useYn);
        }
        $stmt->execute();
        $stmt->close();
        $message = '은행이 저장되었습니다.';
    }
}

$categories = arapFetchCategories();
$subcategories = arapFetchSubcategories();
$methods = arapFetchMethods();
$banks = arapFetchBanks();
?>
<div id="contentwrapper">
    <div class="main_content">
        <div id="jCrumbs" class="breadCrumb module">
            <ul>
                <li><a href="/"><i class="glyphicon glyphicon-home"></i></a></li>
                <li><a href="#">수입·지출</a></li>
                <li>분류관리</li>
            </ul>
        </div>

        <?php if ($message !== '') { ?>
            <div class="alert alert-info"><?= arapH($message) ?></div>
        <?php } ?>

        <div class="row">
            <div class="col-sm-3">
                <div class="panel panel-default">
                    <div class="panel-heading"><strong>대분류</strong></div>
                    <div class="panel-body">
                        <form method="post" id="categoryForm">
                            <input type="hidden" name="mode" value="save_category">
                            <input type="hidden" name="division" value="<?= arapH($division) ?>">
                            <input type="hidden" name="pdx" value="<?= arapH($pdx) ?>">
                            <input type="hidden" name="sub" value="<?= arapH($sub) ?>">
                            <input type="hidden" name="cat_id" id="cat_id" value="0">
                            <div class="form-group">
                                <label>구분</label>
                                <select name="cat_type" id="cat_type" class="form-control">
                                    <option value="IN">수입</option>
                                    <option value="OUT">지출</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>대분류명</label>
                                <input type="text" name="cat_name" id="cat_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>정렬순서</label>
                                <input type="number" name="sort_order" id="sort_order" class="form-control" value="0">
                            </div>
                            <div class="form-group">
                                <label>사용여부</label>
                                <select name="use_yn" id="use_yn" class="form-control">
                                    <option value="Y">사용</option>
                                    <option value="N">미사용</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">저장</button>
                            <button type="button" class="btn btn-default btn-sm js-reset-category">초기화</button>
                        </form>
                        <hr>
                        <table class="table table-bordered table-condensed table-striped">
                            <thead>
                                <tr>
                                    <th>구분</th>
                                    <th>대분류명</th>
                                    <th>정렬</th>
                                    <th>사용</th>
                                    <th>관리</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category) { ?>
                                    <tr>
                                        <td><?= $category['cat_type'] === 'IN' ? '수입' : '지출' ?></td>
                                        <td><?= arapH($category['cat_name']) ?></td>
                                        <td><?= (int)$category['sort_order'] ?></td>
                                        <td><?= arapH($category['use_yn']) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-xs btn-primary js-edit-category"
                                                data-cat-id="<?= (int)$category['cat_id'] ?>"
                                                data-cat-type="<?= arapH($category['cat_type']) ?>"
                                                data-cat-name="<?= arapH($category['cat_name']) ?>"
                                                data-sort-order="<?= (int)$category['sort_order'] ?>"
                                                data-use-yn="<?= arapH($category['use_yn']) ?>">
                                                수정
                                            </button>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="panel panel-default">
                    <div class="panel-heading"><strong>소분류</strong></div>
                    <div class="panel-body">
                        <form method="post" id="subcategoryForm">
                            <input type="hidden" name="mode" value="save_subcategory">
                            <input type="hidden" name="division" value="<?= arapH($division) ?>">
                            <input type="hidden" name="pdx" value="<?= arapH($pdx) ?>">
                            <input type="hidden" name="sub" value="<?= arapH($sub) ?>">
                            <input type="hidden" name="sub_id" id="sub_form_id" value="0">
                            <div class="form-group">
                                <label>대분류</label>
                                <select name="parent_cat_id" id="parent_cat_id" class="form-control" required>
                                    <option value="">선택</option>
                                    <?php foreach ($categories as $category) { ?>
                                        <option value="<?= (int)$category['cat_id'] ?>">
                                            [<?= $category['cat_type'] === 'IN' ? '수입' : '지출' ?>] <?= arapH($category['cat_name']) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>소분류명</label>
                                <input type="text" name="sub_name" id="sub_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>정렬순서</label>
                                <input type="number" name="sub_sort_order" id="sub_sort_order" class="form-control" value="0">
                            </div>
                            <div class="form-group">
                                <label>사용여부</label>
                                <select name="sub_use_yn" id="sub_use_yn" class="form-control">
                                    <option value="Y">사용</option>
                                    <option value="N">미사용</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">저장</button>
                            <button type="button" class="btn btn-default btn-sm js-reset-subcategory">초기화</button>
                        </form>
                        <hr>
                        <table class="table table-bordered table-condensed table-striped">
                            <thead>
                                <tr>
                                    <th>대분류</th>
                                    <th>소분류명</th>
                                    <th>정렬</th>
                                    <th>사용</th>
                                    <th>관리</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subcategories as $subcategory) { ?>
                                    <tr>
                                        <td><?= arapH($subcategory['cat_name']) ?></td>
                                        <td><?= arapH($subcategory['sub_name']) ?></td>
                                        <td><?= (int)$subcategory['sort_order'] ?></td>
                                        <td><?= arapH($subcategory['use_yn']) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-xs btn-primary js-edit-subcategory"
                                                data-sub-id="<?= (int)$subcategory['sub_id'] ?>"
                                                data-cat-id="<?= (int)$subcategory['cat_id'] ?>"
                                                data-sub-name="<?= arapH($subcategory['sub_name']) ?>"
                                                data-sort-order="<?= (int)$subcategory['sort_order'] ?>"
                                                data-use-yn="<?= arapH($subcategory['use_yn']) ?>">
                                                수정
                                            </button>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="panel panel-default">
                    <div class="panel-heading"><strong>거래수단</strong></div>
                    <div class="panel-body">
                        <form method="post" id="methodForm">
                            <input type="hidden" name="mode" value="save_method">
                            <input type="hidden" name="division" value="<?= arapH($division) ?>">
                            <input type="hidden" name="pdx" value="<?= arapH($pdx) ?>">
                            <input type="hidden" name="sub" value="<?= arapH($sub) ?>">
                            <input type="hidden" name="method_id" id="method_id" value="0">
                            <div class="form-group">
                                <label>구분</label>
                                <select name="method_type" id="method_type" class="form-control">
                                    <option value="IN">수입</option>
                                    <option value="OUT">지출</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>거래수단명</label>
                                <input type="text" name="method_name" id="method_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>정렬순서</label>
                                <input type="number" name="method_sort_order" id="method_sort_order" class="form-control" value="0">
                            </div>
                            <div class="form-group">
                                <label>사용여부</label>
                                <select name="method_use_yn" id="method_use_yn" class="form-control">
                                    <option value="Y">사용</option>
                                    <option value="N">미사용</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">저장</button>
                            <button type="button" class="btn btn-default btn-sm js-reset-method">초기화</button>
                        </form>
                        <hr>
                        <table class="table table-bordered table-condensed table-striped">
                            <thead>
                                <tr>
                                    <th>구분</th>
                                    <th>거래수단명</th>
                                    <th>정렬</th>
                                    <th>사용</th>
                                    <th>관리</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($methods as $method) { ?>
                                    <tr>
                                        <td><?= $method['method_type'] === 'IN' ? '수입' : '지출' ?></td>
                                        <td><?= arapH($method['method_name']) ?></td>
                                        <td><?= (int)$method['sort_order'] ?></td>
                                        <td><?= arapH($method['use_yn']) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-xs btn-primary js-edit-method"
                                                data-method-id="<?= (int)$method['method_id'] ?>"
                                                data-method-type="<?= arapH($method['method_type']) ?>"
                                                data-method-name="<?= arapH($method['method_name']) ?>"
                                                data-sort-order="<?= (int)$method['sort_order'] ?>"
                                                data-use-yn="<?= arapH($method['use_yn']) ?>">
                                                수정
                                            </button>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="panel panel-default">
                    <div class="panel-heading"><strong>은행</strong></div>
                    <div class="panel-body">
                        <form method="post" id="bankForm">
                            <input type="hidden" name="mode" value="save_bank">
                            <input type="hidden" name="division" value="<?= arapH($division) ?>">
                            <input type="hidden" name="pdx" value="<?= arapH($pdx) ?>">
                            <input type="hidden" name="sub" value="<?= arapH($sub) ?>">
                            <input type="hidden" name="bank_id" id="bank_id" value="0">
                            <div class="form-group">
                                <label>은행명</label>
                                <input type="text" name="bank_name" id="bank_name" class="form-control" maxlength="50" required>
                            </div>
                            <div class="form-group">
                                <label>정렬순서</label>
                                <input type="number" name="bank_sort_order" id="bank_sort_order" class="form-control" value="0">
                            </div>
                            <div class="form-group">
                                <label>사용여부</label>
                                <select name="bank_use_yn" id="bank_use_yn" class="form-control">
                                    <option value="Y">사용</option>
                                    <option value="N">미사용</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">저장</button>
                            <button type="button" class="btn btn-default btn-sm js-reset-bank">초기화</button>
                        </form>
                        <hr>
                        <table class="table table-bordered table-condensed table-striped">
                            <thead>
                                <tr>
                                    <th>은행명</th>
                                    <th>정렬</th>
                                    <th>사용</th>
                                    <th>관리</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($banks as $bank) { ?>
                                    <tr>
                                        <td><?= arapH($bank['bank_name']) ?></td>
                                        <td><?= (int)$bank['sort_order'] ?></td>
                                        <td><?= arapH($bank['use_yn']) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-xs btn-primary js-edit-bank"
                                                data-bank-id="<?= (int)$bank['bank_id'] ?>"
                                                data-bank-name="<?= arapH($bank['bank_name']) ?>"
                                                data-sort-order="<?= (int)$bank['sort_order'] ?>"
                                                data-use-yn="<?= arapH($bank['use_yn']) ?>">
                                                수정
                                            </button>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "include/side_m.php"; ?>

<script>
function resetCategoryForm() {
    $('#cat_id').val('0');
    $('#cat_type').val('OUT');
    $('#cat_name').val('');
    $('#sort_order').val('0');
    $('#use_yn').val('Y');
}

function resetSubcategoryForm() {
    $('#sub_form_id').val('0');
    $('#parent_cat_id').val('');
    $('#sub_name').val('');
    $('#sub_sort_order').val('0');
    $('#sub_use_yn').val('Y');
}

function resetMethodForm() {
    $('#method_id').val('0');
    $('#method_type').val('OUT');
    $('#method_name').val('');
    $('#method_sort_order').val('0');
    $('#method_use_yn').val('Y');
}

function resetBankForm() {
    $('#bank_id').val('0');
    $('#bank_name').val('');
    $('#bank_sort_order').val('0');
    $('#bank_use_yn').val('Y');
}

$(function() {
    $('.js-edit-category').on('click', function() {
        $('#cat_id').val($(this).data('cat-id'));
        $('#cat_type').val($(this).data('cat-type'));
        $('#cat_name').val($(this).data('cat-name'));
        $('#sort_order').val($(this).data('sort-order'));
        $('#use_yn').val($(this).data('use-yn'));
    });

    $('.js-edit-subcategory').on('click', function() {
        $('#sub_form_id').val($(this).data('sub-id'));
        $('#parent_cat_id').val(String($(this).data('cat-id')));
        $('#sub_name').val($(this).data('sub-name'));
        $('#sub_sort_order').val($(this).data('sort-order'));
        $('#sub_use_yn').val($(this).data('use-yn'));
    });

    $('.js-edit-method').on('click', function() {
        $('#method_id').val($(this).data('method-id'));
        $('#method_type').val($(this).data('method-type'));
        $('#method_name').val($(this).data('method-name'));
        $('#method_sort_order').val($(this).data('sort-order'));
        $('#method_use_yn').val($(this).data('use-yn'));
    });

    $('.js-edit-bank').on('click', function() {
        $('#bank_id').val($(this).data('bank-id'));
        $('#bank_name').val($(this).data('bank-name'));
        $('#bank_sort_order').val($(this).data('sort-order'));
        $('#bank_use_yn').val($(this).data('use-yn'));
    });

    $('.js-reset-category').on('click', resetCategoryForm);
    $('.js-reset-subcategory').on('click', resetSubcategoryForm);
    $('.js-reset-method').on('click', resetMethodForm);
    $('.js-reset-bank').on('click', resetBankForm);
});
</script>
