<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache');
echo json_encode(['serverTime' => round(microtime(true) * 1000)]);