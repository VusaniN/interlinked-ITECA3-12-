<?php
/**
 * Interlinked Marketplace — Wishlist API
 * Toggle product in/out of user's wishlist.
 */
header('Content-Type: application/json');

require_once dirname(__FILE__) . '/../includes/session.php';
require_once dirname(__FILE__) . '/../config/database.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'login_required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$productId = intval($input['product_id'] ?? 0);
$uid       = $_SESSION['user_id'];

if (!$productId) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid product ID']);
    exit;
}

// Verify CSRF
if (!verifyCsrf($input['csrf_token'] ?? '')) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid security token']);
    exit;
}

// Check product exists
$pStmt = $con->prepare("SELECT product_id FROM products WHERE product_id = ? AND status = 'approved' AND is_active = 1");
$pStmt->bind_param("i", $productId);
$pStmt->execute();
if ($pStmt->get_result()->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Product not found']);
    exit;
}

// Check if already in wishlist
$cStmt = $con->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?");
$cStmt->bind_param("ii", $uid, $productId);
$cStmt->execute();
if ($cStmt->get_result()->num_rows > 0) {
    // Remove from wishlist
    $dStmt = $con->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    $dStmt->bind_param("ii", $uid, $productId);
    $dStmt->execute();
    echo json_encode(['status' => 'removed']);
} else {
    // Add to wishlist
    $iStmt = $con->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
    $iStmt->bind_param("ii", $uid, $productId);
    $iStmt->execute();
    echo json_encode(['status' => 'added']);
}
