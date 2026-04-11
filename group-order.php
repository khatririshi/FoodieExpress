<?php
require_once 'includes/config.php';

setFlash('info', 'Group ordering has been removed from the current FoodieExpress experience.');
header('Location: ' . SITE_URL . '/index.php');
exit;
?>
