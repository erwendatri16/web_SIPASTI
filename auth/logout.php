<?php
// auth/logout.php
session_start();
session_destroy();

header('Location: login.php?logout=success');
exit;
?>