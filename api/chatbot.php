<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$message = strtolower(trim($input['message'] ?? ''));

if (empty($message)) {
    echo json_encode(['response' => "I didn't catch that. Could you please repeat?"]);
    exit;
}

$intents = [
    'sell' => [
        'keywords' => ['sell', 'listing', 'create', 'upload', 'post', 'list'],
        'response' => "To start selling, go to your **Dashboard** and click **Create Listing**. Upload clear photos, set a price, and add a description. Our team reviews every listing before it goes live."
    ],
    'buy' => [
        'keywords' => ['buy', 'purchase', 'order', 'checkout', 'cart'],
        'response' => "Found something you like? Click on the listing to view full details. You can add it to your cart or buy directly. We support secure **QR payments** for a seamless checkout."
    ],
    'payment' => [
        'keywords' => ['payment', 'qr', 'money', 'paid', 'refund', 'pay', 'scan'],
        'response' => "Interlinked uses an integrated **QR payment system**. Scan the code with your banking app, pay the amount, and the funds are held securely until the transaction is verified."
    ],
    'verification' => [
        'keywords' => ['verify', 'verification', 'id', 'trust', 'badge', 'approved'],
        'response' => "Verification builds trust! Upload your ID documents in the **Verification** section of your profile. Verified sellers get a badge and more visibility."
    ],
    'delivery' => [
        'keywords' => ['delivery', 'shipping', 'courier', 'receive', 'collect', 'ship'],
        'response' => "Shipping is arranged directly between buyer and seller. We recommend using a reputable courier or meeting in a safe, public place for pickup."
    ],
    'account' => [
        'keywords' => ['account', 'profile', 'password', 'email', 'settings', 'login', 'register', 'sign'],
        'response' => "You can manage your profile, password, and email in **Account Settings**. Keep your contact info updated so buyers and sellers can reach you."
    ],
    'wishlist' => [
        'keywords' => ['wishlist', 'wish', 'save', 'favorite', 'favourite'],
        'response' => "You can save items to your **Wishlist** by clicking the heart icon on any product. Access your wishlist anytime from the menu in the top navigation bar."
    ],
    'hello' => [
        'keywords' => ['hello', 'hi', 'hey', 'greetings', 'morning', 'afternoon', 'help'],
        'response' => "Hello! I'm the **Interlinked Assistant**. I can help you with buying, selling, payments, verification, and account management. What would you like to know?"
    ]
];

$foundResponse = null;

foreach ($intents as $intent => $data) {
    foreach ($data['keywords'] as $keyword) {
        if (str_contains($message, $keyword)) {
            $foundResponse = $data['response'];
            break 2;
        }
    }
}

if (!$foundResponse) {
    $foundResponse = "I'm not sure about that. I can help you with **listings**, **payments**, **verification**, and **account management**. Try asking something like 'How do I sell an item?' or 'How do QR payments work?'";
}

echo json_encode(['response' => $foundResponse]);
