<?php
require_once __DIR__ . '/config.php';

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    exit;
}

if (isset($update['message'])) {
    $message = $update['message'];
    $chat_id = $message['chat']['id'];
    $text = $message['text'] ?? '';

    if (strpos($text, '/start') === 0) {
        $first_name = $message['from']['first_name'] ?? 'Mijoz';
        $reply_markup = [
            'inline_keyboard' => [[
                [
                    'text' => "Choyxona Menyusi va Xonalarni ko'rish 🫖",
                    'web_app' => ['url' => WEBAPP_URL]
                ]
            ]]
        ];

        sendMessage($chat_id, "Salom, {$first_name}! 🫖 The Choyxona botiga xush kelibsiz.\nMenyu va kabinalarni ko'rish uchun pastdagi tugmani bosing:", $reply_markup);
    }
}

if (isset($update['callback_query'])) {
    $cb = $update['callback_query'];
    $cb_id = $cb['id'];
    $chat_id = $cb['message']['chat']['id'];
    $msg_id = $cb['message']['message_id'];
    $data = $cb['data'];

    if (strpos($data, 'accept_') === 0) {
        $order_id = str_replace('accept_', '', $data);
        $new_text = $cb['message']['text'] . "\n\nstatus: ✅ *Qabul qilindi*";
        editMessageText($chat_id, $msg_id, $new_text);
    } elseif (strpos($data, 'reject_') === 0) {
        $order_id = str_replace('reject_', '', $data);
        $new_text = $cb['message']['text'] . "\n\nstatus: ❌ *Rad etildi*";
        editMessageText($chat_id, $msg_id, $new_text);
    }

    answerCallbackQuery($cb_id);
}

function sendMessage($chat_id, $text, $reply_markup = null) {
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    if ($reply_markup) {
        $data['reply_markup'] = json_encode($reply_markup);
    }
    makeTelegramReq('sendMessage', $data);
}

function editMessageText($chat_id, $message_id, $text) {
    $data = [
        'chat_id' => $chat_id,
        'message_id' => $message_id,
        'text' => $text,
        'parse_mode' => 'Markdown'
    ];
    makeTelegramReq('editMessageText', $data);
}

function answerCallbackQuery($cb_id) {
    makeTelegramReq('answerCallbackQuery', ['callback_query_id' => $cb_id]);
}

function makeTelegramReq($method, $data) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/" . $method;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_exec($ch);
    curl_close($ch);
}
?>
