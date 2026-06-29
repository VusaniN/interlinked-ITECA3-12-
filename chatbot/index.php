<?php
header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    $input = array();
}
$message = isset($input['message']) ? strtolower(trim($input['message'])) : '';

if (empty($message)) {
    echo json_encode(array('response' => "I didn't catch that. Could you please repeat?"));
    exit;
}

$intents = array(
    'sell' => array(
        'keywords' => array('sell', 'listing', 'create', 'upload', 'post', 'list'),
        'response' => "To start selling, go to your **Dashboard** and click **Create Listing**. Upload clear photos, set a price, and add a description. Our team reviews every listing before it goes live."
    ),
    'buy' => array(
        'keywords' => array('buy', 'purchase', 'order', 'checkout', 'cart'),
        'response' => "Found something you like? Click on the listing to view full details. You can add it to your cart or buy directly. We support secure **QR payments** for a seamless checkout."
    ),
    'payment' => array(
        'keywords' => array('payment', 'qr', 'money', 'paid', 'refund', 'pay', 'scan'),
        'response' => "Interlinked uses an integrated **QR payment system**. Scan the code with your banking app, pay the amount, and the funds are held securely until the transaction is verified."
    ),
    'verification' => array(
        'keywords' => array('verify', 'verification', 'id', 'trust', 'badge', 'approved'),
        'response' => "Verification builds trust between buyers and sellers! Upload your ID documents in the **Verification** section of your profile. Verified users get a badge and more trust on the platform."
    ),
    'delivery' => array(
        'keywords' => array('delivery', 'shipping', 'courier', 'receive', 'collect', 'ship'),
        'response' => "Shipping is arranged directly between buyer and seller. We recommend using a reputable courier or meeting in a safe, public place for pickup."
    ),
    'account' => array(
        'keywords' => array('account', 'profile', 'password', 'email', 'settings', 'login', 'register', 'sign'),
        'response' => "You can manage your profile, password, and email in **Account Settings**. Keep your contact info updated so buyers and sellers can reach you."
    ),
    'wishlist' => array(
        'keywords' => array('wishlist', 'wish', 'save', 'favorite', 'favourite'),
        'response' => "You can save items to your **Wishlist** by clicking the heart icon on any product. Access your wishlist anytime from the menu in the top navigation bar."
    ),
    'hello' => array(
        'keywords' => array('hello', 'hi', 'hey', 'greetings', 'morning', 'afternoon', 'help'),
        'response' => "Hello! I'm the **Interlinked Assistant**. I can help you with buying, selling, payments, verification, and account management. What would you like to know?"
    )
);

$foundResponse = null;

foreach ($intents as $intent => $data) {
    foreach ($data['keywords'] as $keyword) {
        if (strpos($message, $keyword) !== false) {
            $foundResponse = $data['response'];
            break 2;
        }
    }
}

if (!$foundResponse) {
    $foundResponse = "I'm not sure about that. I can help you with **listings**, **payments**, **verification**, and **account management**. Try asking something like 'How do I sell an item?' or 'How do QR payments work?'";
}

echo json_encode(array('response' => $foundResponse));
