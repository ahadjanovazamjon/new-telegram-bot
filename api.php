<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';

$action = $_GET['action'] ?? '';
$db = getDB();

if ($action === 'menu') {
    $stmt = $db->query("SELECT * FROM products");
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($action === 'rooms') {
    $stmt = $db->query("SELECT * FROM rooms");
    echo json_encode($stmt->fetchAll());
    exit;
}

if ($action === 'order' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $telegram_id = $input['telegram_id'] ?? 0;
    $full_name = $input['full_name'] ?? '';
    $phone = $input['phone'] ?? '';
    $type = $input['type'] ?? 'dine_in';
    $items = $input['items'] ?? [];

    if (empty($full_name) || empty($phone) || empty($items)) {
        http_response_code(400);
        echo json_encode(['error' => 'Maʼlumotlar toʻliq emas']);
        exit;
    }

    $stmt = $db->prepare("SELECT id FROM users WHERE telegram_id = ?");
    $stmt->execute([$telegram_id]);
    $user = $stmt->fetch();

    if (!$user) {
        $stmt = $db->prepare("INSERT INTO users (telegram_id, full_name, phone) VALUES (?, ?, ?)");
        $stmt->execute([$telegram_id, $full_name, $phone]);
        $user_id = $db->lastInsertId();
    } else {
        $user_id = $user['id'];
    }

    $total_price = 0;
    $order_items_data = [];

    foreach ($items as $item) {
        $pStmt = $db->prepare("SELECT price FROM products WHERE id = ?");
        $pStmt->execute([$item['product_id']]);
        $prod = $pStmt->fetch();
        if ($prod) {
            $subtotal = $prod['price'] * $item['quantity'];
            $total_price += $subtotal;
            $order_items_data[] = [
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $prod['price']
            ];
        }
    }

    $stmt = $db->prepare("INSERT INTO orders (user_id, type, total_price) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $type, $total_price]);
    $order_id = $db->lastInsertId();

    foreach ($order_items_data as $oi) {
        $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->execute([$order_id, $oi['product_id'], $oi['quantity'], $oi['price']]);
    }

    sendAdminNotification($order_id, $full_name, $phone, $total_price, $type);

    echo json_encode(['status' => 'ok', 'order_id' => $order_id]);
    exit;
}

function sendAdminNotification($order_id, $name, $phone, $total, $order_type) {
    $text = "🔔 *Yangi buyurtma!* #{$order_id}\n\n"
          . "👤 *Mijoz:* {$name}\n"
          . "📞 *Tel:* {$phone}\n"
          . "🚖 *Turi:* " . ($order_type === 'delivery' ? 'Yetkazib berish' : 'Choyxonada') . "\n"
          . "💰 *Jami summa:* " . number_format($total, 0, '', ' ') . " so'm";

    $keyboard = [
        'inline_keyboard' => [[
            ['text' => '✅ Qabul qilish', 'callback_data' => "accept_{$order_id}"],
            ['text' => '❌ Rad etish', 'callback_data' => "reject_{$order_id}"]
        ]]
    ];

    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $post_data = [
        'chat_id' => ADMIN_ID,
        'text' => $text,
        'parse_mode' => 'Markdown',
        'reply_markup' => json_encode($keyboard)
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_exec($ch);
    curl_close($ch);
}
?>
