<?php
function gsa_layout_head($title)
{
    $safeTitle = htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8');
    $userName = '';
    $userRole = '';

    if (!empty($GLOBALS['user_dbinfo']) && is_array($GLOBALS['user_dbinfo'])) {
        $userName = isset($GLOBALS['user_dbinfo']['kor_name']) ? (string)$GLOBALS['user_dbinfo']['kor_name'] : '';
        if (function_exists('gsa_user_role')) {
            $userRole = gsa_user_role($GLOBALS['user_dbinfo']);
        }
    }

    $safeUserName = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
    $safeUserRole = htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8');

    echo "<!doctype html>\r\n";
    echo "<html lang=\"ko\">\r\n";
    echo "<head>\r\n";
    echo "    <meta charset=\"utf-8\">\r\n";
    echo "    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no\">\r\n";
    echo "    <title>{$safeTitle}</title>\r\n";
    echo "    <link rel=\"preconnect\" href=\"https://cdn.jsdelivr.net\" crossorigin>\r\n";
    echo "    <link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/pretendard/dist/web/static/pretendard.css\">\r\n";
    echo "    <link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css\">\r\n";
    echo "    <link rel=\"stylesheet\" href=\"assets/app.css\">\r\n";
    echo "</head>\r\n";
    echo "<body class=\"gsa-body\">\r\n";
    echo "    <header class=\"gsa-appbar sticky-top bg-white border-bottom shadow-sm\">\r\n";
    echo "        <div class=\"container-fluid p-2 d-flex align-items-center gap-2\">\r\n";
    echo "            <button type=\"button\" class=\"btn btn-light btn-sm gsa-nav-btn\" onclick=\"history.back();\">뒤로</button>\r\n";
    echo "            <div class=\"flex-grow-1 min-w-0\">\r\n";
    echo "                <div class=\"fw-semibold text-truncate\">{$safeTitle}</div>\r\n";
    if ($safeUserName !== '') {
        echo "                <div class=\"small text-muted text-truncate\">{$safeUserName}";
        if ($safeUserRole !== '') {
            echo " · {$safeUserRole}";
        }
        echo "</div>\r\n";
    }
    echo "            </div>\r\n";
    echo "            <a href=\"../admin/logout.php\" class=\"btn btn-outline-primary btn-sm gsa-nav-btn\">로그아웃</a>\r\n";
    echo "        </div>\r\n";
    echo "    </header>\r\n";
    echo "    <main class=\"container-fluid p-2\">\r\n";
}

function gsa_layout_foot()
{
    echo "    </main>\r\n";
    echo "    <div id=\"sticky-bottom\"></div>\r\n";
    echo "    <script src=\"https://code.jquery.com/jquery-3.7.1.min.js\"></script>\r\n";
    echo "    <script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js\"></script>\r\n";
    echo "    <script src=\"assets/app.js\"></script>\r\n";
    echo "</body>\r\n";
    echo "</html>\r\n";
}
