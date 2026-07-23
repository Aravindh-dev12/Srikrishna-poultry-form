<?php
// CLI helper: php gen_hashes.php "password-one" "password-two"
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
foreach (array_slice($argv, 1) as $password) {
    echo password_hash($password, PASSWORD_DEFAULT) . PHP_EOL;
}
?>
