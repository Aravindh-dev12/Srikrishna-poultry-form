<?php
require 'check_auth.php';
header('Location: overview.php?plant=' . urlencode($currentPlant));
exit;
?>