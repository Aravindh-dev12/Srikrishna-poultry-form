<?php

declare(strict_types=1);

date_default_timezone_set(getenv('KANDAN_TIMEZONE') ?: 'Asia/Kolkata');

$KANDAN_CONFIG = [
    'plant_id' => getenv('KANDAN_PLANT_ID') ?: 'kandan',
    'plant_name' => getenv('KANDAN_PLANT_NAME') ?: 'Kandan Solar Plant',
    // Keep the existing SCADA WebSocket endpoint. The plant is selected by unit_id.
    'ws_url' => getenv