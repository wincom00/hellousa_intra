<?php
    include "include/inc_base.php";
	$cookie_name = "MEMLOGIN_ADMIN_HELL";
    
	
	SetCookie("MEMLOGIN_ADMIN_HELLO", '', time()-3600, '/', $c_domain);
 
?>
<script>
    top.location.href = "login.php";
</script>