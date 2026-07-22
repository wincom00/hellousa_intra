<?php
require_once __DIR__ . '/include/bootstrap.php';

$gsaUser = gsa_require_login();
$gsaRole = gsa_user_role($gsaUser);

if ($gsaRole === 'guide') {
    header('Location: my.php');
    exit;
}

header('Location: list.php');
exit;
