<?php
require_once __DIR__ . '/include/bootstrap.php';
require_once __DIR__ . '/include/layout.php';

$gsaUser = gsa_require_login();

gsa_layout_head('전체스케줄표');
?>
<style>
.gsa-schedule-frame-wrap {
    position: fixed;
    left: 0;
    right: 0;
    top: 58px;
    bottom: 0;
    overflow: hidden;
    background: #fff;
}
.gsa-schedule-frame-wrap iframe {
    width: 100%;
    height: 100%;
    border: 0;
    display: block;
}
@media (max-width: 576px) {
    .gsa-schedule-frame-wrap {
        top: 56px;
    }
}
</style>

<div class="gsa-schedule-frame-wrap">
    <iframe src="../admin/sc_local.php" title="전체스케줄표"></iframe>
</div>

<?php
gsa_layout_foot();
