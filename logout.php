<?php

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
kandan_logout();
header('Location: index.php');
exit;
