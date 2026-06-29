<?php
header('Content-Type: application/json');
echo json_encode(array('status' => 'ok', 'method' => $_SERVER['REQUEST_METHOD'], 'input' => file_get_contents('php://input')));
