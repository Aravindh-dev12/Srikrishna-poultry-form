<?php
// Retired legacy ingestion endpoint. WebSocket telemetry is stored through the
// authenticated, schema-controlled api_store.php endpoint.
header('Content-Type: application/json');
http_response_code(410);
echo json_encode([
    'success' => false,
    'error' => 'This legacy endpoint is retired. Use api_store.php.'
]);
?>
