<?php
// Admin Action Handler - Interlinked Marketplace
require_once dirname(__FILE__) . '/../includes/session.php';
require_once dirname(__FILE__) . '/../config/database.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['action'])) {
    redirect('index.php');
}

$action = isset($_POST['action']) ? $_POST['action'] : $_GET['action'] ?? '';
$returnTo = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';

// Log action
$uid = $_SESSION['user_id'];
$desc = "Admin action: $action";

switch ($action) {
    case 'approve_product':
        $id = intval(isset($_GET['id']) ? $_GET['id'] : 0);
        mysqli_query($con, "UPDATE products SET status = 'approved' WHERE product_id = $id");
        // Notify seller
        $seller = mysqli_fetch_assoc(mysqli_query($con, "SELECT seller_id, product_name FROM products WHERE product_id = $id"));
        mysqli_query($con, "INSERT INTO notifications (user_id, type, title, body, link) VALUES ({$seller['seller_id']}, 'info', 'Product Approved', 'Your product \"{$seller['product_name']}\" has been approved!', 'dashboard.php')");
        mysqli_query($con, "INSERT INTO activity_log (user_id, action, description) VALUES ($uid, 'approve_product', 'Approved product #$id')");
        break;

    case 'reject_product':
        $id = intval(isset($_GET['id']) ? $_GET['id'] : 0);
        $note = 'Product did not meet marketplace guidelines.';
        mysqli_query($con, "UPDATE products SET status = 'rejected' WHERE product_id = $id");
        $seller = mysqli_fetch_assoc(mysqli_query($con, "SELECT seller_id, product_name FROM products WHERE product_id = $id"));
        mysqli_query($con, "INSERT INTO notifications (user_id, type, title, body, link) VALUES ({$seller['seller_id']}, 'info', 'Product Rejected', 'Your product \"{$seller['product_name']}\" was rejected.', 'dashboard.php')");
        mysqli_query($con, "INSERT INTO activity_log (user_id, action, description) VALUES ($uid, 'reject_product', 'Rejected product #$id')");
        break;

    case 'toggle_featured':
        $id = intval(isset($_GET['id']) ? $_GET['id'] : 0);
        mysqli_query($con, "UPDATE products SET is_featured = NOT is_featured WHERE product_id = $id");
        mysqli_query($con, "INSERT INTO activity_log (user_id, action, description) VALUES ($uid, 'toggle_featured', 'Toggled featured status for product #$id')");
        break;

    case 'process_verification':
        $docId = intval(isset($_POST['doc_id']) ? $_POST['doc_id'] : 0);
        $decision = isset($_POST['decision']) ? $_POST['decision'] : '';
        $adminNote = trim(isset($_POST['admin_note']) ? $_POST['admin_note'] : '');

        if (!verifyCsrf(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
            die('CSRF invalid');
        }

        if ($docId > 0 && in_array($decision, ['approved', 'rejected'])) {
            $status = $decision;
            $now = date('Y-m-d H:i:s');

            if ($status === 'approved') {
                // Get user_id from doc
                $doc = mysqli_fetch_assoc(mysqli_query($con, "SELECT user_id FROM document_verification WHERE doc_id = $docId"));
                mysqli_query($con, "UPDATE users SET is_verified = 1 WHERE user_id = {$doc['user_id']}");
            }

            $stmt = $con->prepare("UPDATE document_verification SET status = ?, admin_note = ?, reviewed_by = ?, reviewed_at = ? WHERE doc_id = ?");
            $stmt->bind_param("ssisi", $status, $adminNote, $uid, $now, $docId);
            $stmt->execute();

            // Notify user
            $doc = mysqli_fetch_assoc(mysqli_query($con, "SELECT user_id FROM document_verification WHERE doc_id = $docId"));
            $notifTitle = $status === 'approved' ? 'Verification Approved!' : 'Verification Rejected';
            $notifBody = $status === 'approved' ? 'Your account has been verified. You now have the Trusted Seller badge!' : 'Your verification was rejected. Reason: ' . ($adminNote ?: 'Not specified');
            mysqli_query($con, "INSERT INTO notifications (user_id, type, title, body, link) VALUES ({$doc['user_id']}, 'verification', '$notifTitle', '$notifBody', 'verification.php')");

            mysqli_query($con, "INSERT INTO activity_log (user_id, action, description) VALUES ($uid, 'verification_$status', 'Document #$docId $status')");
        }
        redirect('admin/verification_queue.php');
        break;
}

redirect($returnTo);
