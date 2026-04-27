<?php
session_start();
session_destroy();
header("Location: /lost_found_project/lost_found/index.php");
exit();
?>
