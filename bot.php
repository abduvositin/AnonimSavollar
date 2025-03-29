<?php
ob_start();
date_Default_timezone_set("Asia/Tashkent");

require_once "sql.php";

const API_KEY = "7243996968:AAHrjWqq6nL8ONy73cZ26qYq8KXPk2sYRAA";
$abduvositin = 6815977965;
$owners = [$abduvositin];

$bot = bot("getme")->result->username;
$bot_id = bot("getme")->result->id;


function bot($method, $datas = []){
    $url = "https://api.telegram.org/bot" . API_KEY . "/" . $method;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $datas);
    $res = curl_exec($ch);
    if (curl_error($ch)) {
        var_dump(curl_error($ch));
    } else {
        return json_decode($res);
    }
}


function sendMessage($id, $txt, $rm=null){
    return bot("sendMessage", [
        "chat_id" => $id,
        "text" => $txt,
        "parse_mode" => "html",
        "disable_web_page_preview" => true,
        "reply_markup" => $rm,
    ]);
}

function replyMessage($id, $mid, $txt, $rm = null) {
    return bot("sendMessage", [
        "chat_id" => $id,
        "text" => $txt,
        "parse_mode" => "html",
        "disable_web_page_preview" => true,
        "reply_to_message_id" => $mid,
        "reply_markup" => $rm,
    ]);
}


function sendPhoto($id, $flid, $caption, $rm=null){
    return bot("sendPhoto", [
        "chat_id" => $id,
        "photo" => $flid,
        "caption" => $caption,
        "parse_mode" => "html",
        "reply_markup" => $rm,
    ]);
}

function sendVideo($id, $flid, $caption, $rm=null){
    return bot("sendVideo", [
        "chat_id" => $id,
        "video" => $flid,
        "caption" => $caption,
        "parse_mode" => "html",
        "reply_markup" => $rm,
    ]);
}

function sendAudio($id, $flid, $caption, $rm=null){
    return bot("sendAudio", [
        "chat_id" => $id,
        "audio" => $flid,
        "caption" => $caption,
        "parse_mode" => "html",
        "reply_markup" => $rm,
    ]);
}

function editMessage($id, $mid, $txt, $rm = null){
    return bot("editMessageText", [
        "chat_id" => $id,
        "message_id" => $mid,
        "text" => $txt,
        "parse_mode" => "html",
        "reply_markup" => $rm,
    ]);
}

function deleteMessage(){
    global $cid, $mid, $mid2;
    return bot("deleteMessage", [
        "chat_id" => "$cid",
        "message_id" => "$mid2$mid",
    ]);
}

function answerCallback($qid, $text, $show = false){
    return bot("answerCallbackQuery", [
        "callback_query_id" => $qid,
        "text" => $text,
        "show_alert" => $show,
    ]);
}

function step($id, $value){
    global $connect;
    mysqli_query($connect, "UPDATE users SET step = '$value' WHERE user_id=$id");
}

function stepFile($cid, $step_value) {
    $file_path = "step/{$cid}.txt"; 
    file_put_contents($file_path, $step_value);

}

function admin($id){
    global $connect, $abduvositin;
    $result = mysqli_query($connect, "SELECT * FROM admins WHERE user_id = '$id'");
    $row = mysqli_fetch_assoc($result);
    if ($row or $id == $abduvositin) {
        return true;
    } else {
        return false;
    }
}

function generateRandomString() {
    global $connect;
    $length = 8; 
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }

    $result = mysqli_query($connect, "SELECT * FROM users WHERE user_key = '$randomString'");
    
    while (mysqli_num_rows($result) > 0) {
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        $result = mysqli_query($connect, "SELECT * FROM users WHERE user_key = '$randomString'");
    }

    return $randomString;
}


$update = json_decode(file_get_contents("php://input"));

$message = $update->message;
$callbackQuery = $update->callback_query;
$chatJoinRequest = $update->chat_join_request;

if ($message) {
    $cid = $message->chat->id;
    $name = $message->chat->first_name;
    $type = $message->chat->type;
    $lang = $message->from->language_code; 
    $text = $message->text;
    $mid = $message->message_id;
    $photo = $message->photo;
    $video = $message->video;
    $voice = $message->voice;
    $sticker = $message->sticker;
    $audio = $message->audio;
    $caption = $message->caption ?? "";
}

if ($callbackQuery) {
    $data = $callbackQuery->data;
    $qid = $callbackQuery->id;
    $cid = $callbackQuery->message->chat->id;
    $mid2 = $callbackQuery->message->message_id;}
    
if ($chatJoinRequest) {
    $join_chat_id = $chatJoinRequest->chat->id;
    $join_user_id = $chatJoinRequest->from->id;
    $connect->query("INSERT INTO requests (id, chat_id) VALUES ('$join_user_id', '$join_chat_id')");
}

mkdir("step");


$result = $connect->query("SELECT * FROM settings WHERE id = '1'"); 
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $bot_status = $row["bot_status"];
    $admin_user = $row["admin_user"];
}

$users_dts = mysqli_query($connect, "SELECT * FROM users WHERE user_id = $cid");
while ($ausers = mysqli_fetch_assoc($users_dts)) {
    $step = $ausers["step"];
    $user_key = $ausers["user_key"];
    $user_lang = $ausers["user_lang"];
    $user_time = $ausers["time"];

}

$language = json_decode(file_get_contents("lang/$user_lang.json"), true);
$language = str_replace(["%bot%","%user_key%"], [$bot, $user_key], $language);


if ($data) {
    if ($bot_status == "off" and !admin($cid) == 1) {
        answerCallback($qid,$language["bot_off"], 1);
        exit();
    }
}

$menu = json_encode([
    "inline_keyboard" => [
        [["text" => $language["share"], "url" => "https://t.me/share/url?url=https://t.me/$bot?start=$user_key"]],
    ],
]);

$close = json_encode([
    "inline_keyboard" => [
        [["text" => $language["close"],"callback_data" => "close_chat"]],
    ],
]);

$change_key = json_encode([
    "inline_keyboard" => [
        [["text" => $language["change_key_random"],"callback_data" => "change-key-random"]],
        [["text" => $language["close"],"callback_data" => "close_chat"]],
    ],
]);

$change_lang = json_encode([
    "inline_keyboard" => [
        [["text" => "🇷🇺 Русский","callback_data" => "changeLang-ru"]],
        [["text" => "🇺🇿 Uzbek","callback_data" => "changeLang-uz"]],
        [["text" => "🇺🇸 English","callback_data" => "changeLang-eng"]],

    ],
]);

$panel = json_encode([
    "resize_keyboard" => true,
    "keyboard" => [
        [["text" => "📊 Statistika"],["text" => "👨🏻‍💻 Admin sozlamalari"]],
        [["text" => "⚙️ Xabar Sozlamalar"],["text" => "✉️ Xabar Yuborish"]],
        [["text" => "🤖 Bot holati"],["text" => "◀️ Orqaga"]],
    ],
]);

$back_panel = json_encode([
    "resize_keyboard" => true,
    "keyboard" => [[["text" => "🗄 Boshqarish"]]],
]);

if (isset($message) && $type == "private") {
    $registered_date = date("d.m.Y H:i");
    $result = mysqli_query($connect, "SELECT 1 FROM users WHERE user_id = '$cid' LIMIT 1");
    $randomKey = generateRandomString();

    if (!in_array($lang, ['uz', 'ru', 'eng'])) {
        $lang = 'eng'; 
    }

    $langFile = "lang/$lang.json";
    if (file_exists($langFile)) {
        $temp_lang = json_decode(file_get_contents($langFile), true);
    } else {
        $temp_lang = json_decode(file_get_contents("lang/eng.json"), true); 
    }

    $temp_lang = str_replace(["%bot%", "%user_key%"], [$bot, $randomKey], $temp_lang);

    if (mysqli_num_rows($result) == 0) {
        mysqli_query($connect, "INSERT INTO users(user_id,user_key,user_lang, time, step) VALUES ('$cid', '$randomKey','$lang','$registered_date', 'none')");
        
        sendMessage($cid, $temp_lang["start_msg"], json_encode([
            "inline_keyboard" => [
                [["text" => $temp_lang["share"], "url" => "https://t.me/share/url?url=https://t.me/$bot?start=$randomKey"]],
            ],
        ]));
    }
}

if ($text && $type=="private") {
    if ($bot_status == "off" and !admin($cid) == 1) {
        sendMessage($cid,$language["bot_off"],json_encode(["remove_keyboard" => true]));
        exit();
    }
}

if ($text == "/start" && $type == "private") {
    $result = mysqli_query($connect, "SELECT user_key FROM users WHERE user_id = '$cid'");
    $user = mysqli_fetch_assoc($result);
    $user_key = $user['user_key'];

    sendMessage($cid,$language["start_msg"],$menu);
    exit();
}

if (mb_stripos($text, "/start ") !== false) {
    $text = str_replace("/start ", "", $text);

    $result = mysqli_query($connect, "SELECT * FROM users WHERE user_key = '$text'");
    $result2 = mysqli_query($connect, "SELECT * FROM users WHERE user_id = '$cid'");

    if (mysqli_num_rows($result2) > 0) {
        if (mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            $user_id = $user['user_id']; 

            if ($user_id == $cid) {
                sendMessage($cid, $language["msg_me"]);
            } else {
                sendMessage($cid, $language["msg_rule"], $close);
                step($cid, "chatopen");
                stepFile($cid, "$user_id");
            }
        } else {
            sendMessage($cid, $language["no_user"]);
        }
    } else {
        $registered_date = date("d.m.Y H:i");
        $randomKey = generateRandomString();
        
        if (!in_array($lang, ['uz', 'ru', 'eng'])) {
            $lang = 'eng';
        }
        
        $langFile = "lang/$lang.json";
        if (file_exists($langFile)) {
            $temp_lang = json_decode(file_get_contents($langFile), true);
        } else {
            $temp_lang = json_decode(file_get_contents("lang/eng.json"), true);
        }

        $temp_lang = str_replace(["%bot%", "%user_key%"], [$bot, $randomKey], $temp_lang);

        mysqli_query($connect, "INSERT INTO users(`user_id`, `user_key`, `user_lang`, `time`, `step`) VALUES ('$cid', '$randomKey', '$lang', '$registered_date', 'none')");

    if (mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);
            $user_id = $user['user_id']; 

        if ($user_id == $cid) {
                sendMessage($cid, $temp_lang["msg_me"]);
            } else {
                sendMessage($cid, $temp_lang["msg_rule"], json_encode(["inline_keyboard" => [[["text" => $temp_lang["close"],"callback_data" => "close_chat"]],],
            ]));
                step($cid, "chatopen");
                stepFile($cid, "$user_id");
            }
        } else {
            sendMessage($cid, $temp_lang["no_user"]);
        }
    }
}

if($data=="close_chat"){
    deleteMessage();
    sendMessage($cid,$language["start_msg"],$menu);
    step($cid, "none");
    unlink("step/$cid.txt");
    exit();
}

if ($step == "chatopen") {
    $user_id = file_get_contents("step/$cid.txt");
    $user = mysqli_query($connect, "SELECT * FROM users WHERE user_id = '$user_id'");
    $u_key = null;

    while ($a = mysqli_fetch_assoc($user)) {
        $u_key = $a["user_key"];
        $u_lang = $a["user_lang"];
    }
   $lang_u = json_decode(file_get_contents("lang/$u_lang.json"), true);


    if (!$u_key) {
        sendMessage($cid, $language["no_user"], $menu);
        stepFile($cid, "none");
        step($cid, "none");
        exit;
    }

    $keyboards = [
        "sendAnswer" => json_encode([
            "inline_keyboard" => [
                [["text" => $lang_u["send_sms"], "callback_data" => "sendAnswer_" . $user_key]],
                [["text" => $lang_u["skip"], "callback_data" => "skip"]],
            ],
        ]),
        "sendAgain" => json_encode([
            "inline_keyboard" => [
                [["text" => $language["more_sms"], "callback_data" => "sendAgain_" . $u_key]],
                [["text" => $language["main_menu"], "callback_data" => "close_chat"]],
            ],
        ]),
    ];

    $successMessages = [
        'text' => $language["text_sms"],
        'photo' => $language["photo_sms"],
        'video' => $language["video_sms"],
        'audio' => $language["audio_sms"],
        'new' => $lang_u["new_sms"]
    ];

    if ($text && $type == "private") {
        sendMessage($user_id, "${successMessages['new']}\n\n$text", $keyboards['sendAnswer']);
        sendMessage($cid, $successMessages['text'], $keyboards['sendAgain']);
    } elseif (isset($photo)) {
        sendPhoto($user_id, $photo[0]->file_id, "${successMessages['new']}\n\n$caption", $keyboards['sendAnswer']);
        sendMessage($cid, $successMessages['photo'], $keyboards['sendAgain']);
    } elseif (isset($video)) {
        sendVideo($user_id, $video->file_id, "${successMessages['new']}\n\n$caption", $keyboards['sendAnswer']);
        sendMessage($cid, $successMessages['video'], $keyboards['sendAgain']);
    } elseif (isset($audio)) {
        sendAudio($user_id, $audio->file_id, $successMessages['new'], $keyboards['sendAnswer']);
        sendMessage($cid, $successMessages['audio'], $keyboards['sendAgain']);
    } else {
        sendMessage($cid, $language["no_support"]);
    }

    bot("deleteMessage", [
        "chat_id" => $cid,
        "message_id" => $mid - 1,
    ]);

    unlink("step/$cid.txt");
    step($cid, "none");
}

if (stripos($data, "sendAgain_") !== false) {
    $sender_key = explode("_", $data)[1];
    deleteMessage();
    $user = mysqli_query($connect, "SELECT * FROM users WHERE user_key = '$sender_key'");
    while ($a = mysqli_fetch_assoc($user)) {
        $u_id = $a["user_id"];
}
sendMessage($cid, $language["msg_rule"], $close);
stepFile($cid, "$u_id");
step($cid, "chatopen");
}

if (stripos($data, "sendAnswer_") !== false) {
    $sender_key = explode("_", $data)[1];
    $user = mysqli_query($connect, "SELECT * FROM users WHERE user_key = '$sender_key'");
    while ($a = mysqli_fetch_assoc($user)) {
        $u_id = $a["user_id"];
}
replyMessage($cid,$mid2, $language["msg_rule"], $close);
stepFile($cid, "$u_id");
step($cid, "chatopen");
}

if ($data == "skip") {
    bot("deleteMessage", [
        "chat_id" => $cid,
        "message_id" => "$mid$mid2",
    ]);
}

if ($text == "/change" && $type == "private") {
    sendMessage($cid,$language["soon"]);
    step($cid,"none");
    exit();
}

if($data=="change-key-random"){
    $randomKey = generateRandomString();
    mysqli_query($connect, "UPDATE users SET user_key = '$randomKey' WHERE user_id=$cid");
    editMessage($cid,$mid2,$language["change_key_succ"]);
    step($cid,"none");
}

if($step=="change_ukey"){
    if ($text && $type == "private") {
        $result = mysqli_query($connect, "SELECT 1 FROM users WHERE user_key = '$text' LIMIT 1");
        if (mysqli_num_rows($result) > 0) {
            sendMessage($cid,$language["change_key_alus"] );
        } else {
            mysqli_query($connect, "UPDATE users SET user_key = '$text' WHERE user_id = '$cid'");
            sendMessage($cid, $language["change_key_succ"]);
        }
    }
    step($cid,"none");
}

if ($text == "/lang" && $type == "private") {
    sendMessage($cid,"Выберите язык:\nTilni tanlang:\nChoose language:",$change_lang);
    exit();
}

if (mb_stripos($data, "changeLang-") !== false) {
    $lang = explode("-", $data)[1];
    deleteMessage();
    mysqli_query($connect, "UPDATE users SET user_lang = '$lang' WHERE user_id = '$cid'");
    if ($lang == 'uz') {
     sendMessage($cid, "<b>Til O'zbekcha qilib o'zgartirildi! 🎉</b>\n\n<i>Iltimos, qayta /start tugmasini bosing.</i>");
    } elseif ($lang == 'eng') {
     sendMessage($cid, "<b>The language has been changed to English! 🎉</b>\n\n<i>Please press the <b>/start</b> button again.</i>");
    }elseif ($lang == 'ru') {
     sendMessage($cid, "<b>Язык изменен на русский! 🎉</b>\n\n<i>Пожалуйста, нажмите кнопку <b>/start</b> еще раз.</i>");
    }
}


if ($text== "/error") {
    sendMessage($cid, $language["error_tt_msg"]);
    
}


if (mb_stripos($text, "/error ") !== false) {
    $error_message = str_replace("/error ", "", $text);
    
    sendMessage($abduvositin, "Xatolik xabari:\n $error_message");
    sendMessage($cid, $language["error_msg"]);
    
}



if ($_GET["update"] == "send") {
    $rm = json_decode(file_get_contents("lang/smskey.json"), true);
    $result = mysqli_query($connect, "SELECT * FROM `send`");
    $row = mysqli_fetch_assoc($result);
    $time = date("H:i");

    if ($row["status"] == "resume") {
        $row1 = $row["time1"];
        $row2 = $row["time2"];
        $start_id = $row["start_id"];
        $stop_id = $row["stop_id"];
        $admin_id = $row["admin_id"];
        $mied = $row["message_id"];
        $edit_mess_id = $row["edit_mess_id"];
        $sends_count = $row["sends_count"] ?? 0;
        $receive_count = $row["receive_count"] ?? 0;
        $statistics = $row["statistics"];
        $repl_markup = base64_decode($row["reply_markup"]);
        $time1 = date("H:i", strtotime("+1 minutes"));
        $time2 = date("H:i", strtotime("+2 minutes"));
        $limit = 700;

        if ($repl_markup == "null") {
            $repl_markup = json_encode(["inline_keyboard" => [[["text" => $rm["text"], "url" => $rm["url"]]]]]);
        }
        if ($time == $row1 or $time == $row2) {
            $sql = "SELECT * FROM `users` LIMIT $start_id, $limit";
            $res = mysqli_query($connect, $sql);
            $not_received_count = 0;

            while ($a = mysqli_fetch_assoc($res)) {
                $id = $a["user_id"];
                
                $receive_check = bot("CopyMessage", [
                        "chat_id" => $id,
                        "from_chat_id" => $admin_id,
                        "message_id" => $mied,
                        "reply_markup" => $repl_markup,
                    ]);
                $sends_count++;
                if ($receive_check->ok) {
                    $receive_count++;
                } 

                if ($id == $stop_id) {
                    bot('deleteMessage', [
                        'chat_id' => $admin_id,
                        'message_id' => $edit_mess_id,
                    ]);

                    bot("sendMessage", [
                        "chat_id" => $admin_id,
                        "text" => "<b>✅ Habar yuborish yakunlandi</b>\n\n<b>✅ Yuborildi:</b> <code>$sends_count/$statistics</code>",
                        "parse_mode" => "html",
                        "reply_markup" => $panel,
                    ]);
                    mysqli_query($connect, "DELETE FROM `send`");
                    break;
                }
            }

            mysqli_query($connect, "UPDATE `send` SET `time1` = '$time1'");
            mysqli_query($connect, "UPDATE `send` SET `time2` = '$time2'");
            $get_id = $start_id + $limit;
            mysqli_query($connect, "UPDATE `send` SET `start_id` = '$get_id'");
            mysqli_query($connect,"UPDATE `send` SET `sends_count` = '$sends_count'");
            mysqli_query($connect,"UPDATE `send` SET `receive_count` = '$receive_count'");
            $edit = bot("editMessageText", [
                "chat_id" => $admin_id,
                "message_id" => $edit_mess_id,
                "text" => "<b>✅ Yuborildi:</b> <code>$sends_count/$statistics</code>
<b>📥 Qabul qilindi:</b> <code>$receive_count</code>
<b>🔰 Status</b>: <code>resume</code>",
                "parse_mode" => "html",
                "reply_markup" => json_encode([
                    "inline_keyboard" => [
                        [["text" => "To'xtatish ⏸️","callback_data" => "sendstatus=stopped"]],
                        [["text" => "🗑 O'chirish","callback_data" => "bekorqilish_send"]],
                    ],
                ]),
            ]);

            if ($edit->ok) {
                $edit_mess_id = $edit->result->message_id;
                mysqli_query(
                    $connect,
                    "UPDATE `send` SET `edit_mess_id` = '$edit_mess_id'"
                );
            }
        }
        echo json_encode(["status" => true, "cron" => "Sending message"]);
    }
}


if (($text == "/panel" && $type == "private") && admin($cid) == 1) {
    sendMessage($cid,"<b>Admin paneliga xush kelibsiz!</b>", $panel);
}


if ($text == "🗄 Boshqarish") {
    if (admin($cid) == 1) {
        sendMessage($cid, "<b>Admin paneliga xush kelibsiz!</b>", $panel);
        unlink("step/$cid.type");
        unlink("step/$cid.txt");
        step($cid, "none");
        exit();
    } else {
        sendMessage($cid, "<b>⚠️ Kechirasiz, bu bo'limga kirish faqat administratorlar uchun ochiq!</b>",null);
    }
}

if (($text == "◀️ Orqaga" && $type == "private" ) and admin($cid) == 1){
    unlink("step/$cid.type");
    unlink("step/$cid.txt");
    step($cid, "none");
    sendMessage($cid, "<b>❗️ Admin paneldan chiqdingiz /start ni yuboring \n\n🚀 Qaytish uchun /panel ni yuboring</b>",json_encode(["remove_keyboard" => true]));
    step($cid,"none");
}

if (($text == "📊 Statistika" && $type == "private" ) and admin($cid) == 1){
    
    $res = mysqli_query($connect, "SELECT * FROM `users`");
    $stat = mysqli_num_rows($res);
    $ping = sys_getloadavg();
    $today_date = date("d.m.Y");
    $month_date = date("m.Y");
    $joined_today = $connect->query("SELECT * FROM `users` WHERE `time` LIKE '%$today_date%';")->num_rows;
    $joinedThisMonth = $connect->query("SELECT * FROM `users` WHERE `time` LIKE '%$month_date%';")->num_rows;


    
    sendMessage($cid, "💡 <b>O'rtacha yuklanish:</b> <code>$ping[0]</code>

• <b>Barcha odamlar:</b> $stat ta 
• <b>Bugun qo'shilganlar:</b> $joined_today ta
• <b>Shu oy qo'shilganlar:</b> $joinedThisMonth ta

<b>⏰ Soat:</b> " .date("H:i:s") ." | <b>📆 Sana:</b> " .date("d.m.Y") . "",json_encode([
                "inline_keyboard" => [
                    [["text" => "🔄 Yangilash", "callback_data" => "upstat"]],
                ],
            ])
        );
    exit();
    
}

if ($data == "upstat" and admin($cid) == 1){
    
    $res = mysqli_query($connect, "SELECT * FROM `users`");
    $stat = mysqli_num_rows($res);
    $ping = sys_getloadavg();
    $today_date = date("d.m.Y");
    $month_date = date("m.Y");
    $joined_today = $connect->query("SELECT * FROM `users` WHERE `time` LIKE '%$today_date%';")->num_rows;
    $joinedThisMonth = $connect->query("SELECT * FROM `users` WHERE `time` LIKE '%$month_date%';")->num_rows;
    
    editMessage($cid, $mid2, "💡 <b>O'rtacha yuklanish:</b> <code>$ping[0]</code>

• <b>Barcha odamlar:</b> $stat ta 
• <b>Bugun qo'shilganlar:</b> $joined_today ta
• <b>Shu oy qo'shilganlar:</b> $joinedThisMonth ta

<b>⏰ Soat:</b> " .date("H:i:s") ." | <b>📆 Sana:</b> " .date("d.m.Y") . "",json_encode([
                "inline_keyboard" => [
                    [["text" => "🔄 Yangilash", "callback_data" => "upstat"]],
                ],
            ])
        );
    exit();
}

if ($text == "👨🏻‍💻 Admin sozlamalari") {
    if (admin($cid) == 1) {
        sendMessage($cid,"<b>👨🏻‍⚖️ Adminlar sozlamalari bo'limi!
⤵️ Kerakli menyuni tanlang:</b>", json_encode([
    "resize_keyboard" => true,
    "keyboard" => [
        [["text" => "➕ Administrator qo'shish"],["text" => "🗑️ Administrator o‘chirish"]],
        [["text" => "📋 Administrator ro'yxati"]],
        [["text" => "🗄 Boshqarish"]],
    ],
]));
}
}

if ($text == "📋 Administrator ro'yxati" and admin($cid) == 1) {
    $res = mysqli_query($connect, "SELECT * FROM admins");
    if ($res->num_rows > 0) {
        while ($a = mysqli_fetch_assoc($res)) {
            $user = $a["user_id"];
            $get = bot("getchat", ["chat_id" => $user])->result->first_name;
            $name = strip_tags($get);
            $key[] = ["text" => "$name", "url" => "tg://user?id=$user"];
        }
        $keyboard2 = array_chunk($key, 1);
        $kb = json_encode(["inline_keyboard" => $keyboard2,]);
        sendMessage($cid, "<b>👉 Barcha adminlar ro'yxati:</b>", $kb);
    } else {
        sendMessage($cid, "<b>Administratorlar mavjud emas</b>", null);
    }
}

if ($text == "➕ Administrator qo'shish") {
    if (in_array($cid, $owners)) {
        sendMessage($cid, "<b>Kerakli foydalanuvchi ID raqamini yuboring:</b>", $back_panel);
        step($cid, "add-admin");
    } else {
        sendMessage($cid,"<b>Ushbu bo'limdan foydalanish siz uchun taqiqlangan!</b>",null);
    }
}

if ($step == "add-admin" and in_array($cid, $owners)) {
    $result = mysqli_query($connect,"SELECT * FROM users WHERE user_id = '$text'");
    $row = mysqli_fetch_assoc($result);
    if (!$row) {
        sendMessage($cid,"<b>Ushbu foydalanuvchi botdan foydalanmaydi!</b>

Boshqa ID raqamni kiriting:", null);
    } else if (!in_array($text, $admin)) {
        $connect->query("INSERT INTO admins (user_id) VALUES ($text)");
        sendMessage($cid,"<code>$text</code> <b>adminlar ro'yxatiga qo'shildi!</b>",$admin_manager);
        step($cid, "none");
    } else {
        sendMessage($cid,"<b>Ushbu foydalanuvchi adminlari ro'yxatida mavjud!</b>

Boshqa ID raqamni kiriting:", null);
    }
}

if ($text == "🗑️ Administrator o‘chirish") {
    if (in_array($cid, $owners)) {
        $result = $connect->query("SELECT * FROM admins");
        if ($result->num_rows > 0) {
            $i = 1;
            $response = "";
            while ($row = $result->fetch_assoc()) {
                $get = bot("getchat", ["chat_id" => $row["user_id"]])->result->first_name;
                $response .=
                    "<b>$i)</b> <a href='tg://user?id=" .
                    $row["user_id"] .
                    "'>$get</a>\n";
                $uz[] = ["text" => $i,"callback_data" => "remove-admin=" . $row["user_id"],];
                $i++;
            }
            $keyboard2 = array_chunk($uz, 3);
            $kb = json_encode(["inline_keyboard" => $keyboard2]);
            sendMessage($cid,"<b>👉 O'chirmoqchi bo'lgan administratorni tanlang:</b>\n\n$response",$kb);
        } else {
            sendMessage($cid, "<b>Administratorlar mavjud emas</b>", null);
        }
    } else {
        sendMessage($cid,"<b>Ushbu bo'limdan foydalanish siz uchun taqiqlangan!</b>",null);
    }
}

if (mb_stripos($data, "remove-admin=") !== false and in_array($cid, $owners)) {
    $user_od = explode("=", $data)[1];
    $result = mysqli_query($connect,"SELECT * FROM admins WHERE user_id = $user_od");
    $row = mysqli_fetch_assoc($result);
    if ($row) {
        $connect->query("DELETE FROM admins WHERE user_id = $user_od");
        deleteMessage();
        sendMessage($cid,"<code>$user_od</code> <b>adminlar ro'yxatidan olib tashlandi!</b>",$admin_manager);
    } else {
        answerCallback($qid,"Ushbu foydanuvchi administratorlar ro'yxatida mavjud emas!",1);
    }
}

if ($text == "🤖 Bot holati" and admin($cid) == 1) {
    $result = $connect->query("SELECT bot_status FROM settings LIMIT 1");
    if (!$result) {
        sendMessage($cid, "Xato yuz berdi: " . $connect->error);
        exit;
    }
    
    $row = $result->fetch_assoc();
    $holat = $row["bot_status"] ?? "off"; 
    $xolat = ($holat == "on") ? "O'chirish" : "Yoqish";

    sendMessage($cid, "*️⃣ Hozirgi holati: $holat",
        json_encode([
            "inline_keyboard" => [
                [["text" => $xolat, "callback_data" => "bot"]],
            ],
        ])
    );
}

if ($data == "bot") {
    $result = $connect->query("SELECT bot_status FROM settings LIMIT 1");
    $row = $result->fetch_assoc();
    $holat = $row["bot_status"];

    if ($holat == "on") {
        $connect->query("UPDATE settings SET bot_status = 'off'");
        $xolat = "Yoqish";
    } else {
        $connect->query("UPDATE settings SET bot_status = 'on'");
        $xolat = "O'chirish";
    }

    $result = $connect->query("SELECT bot_status FROM settings LIMIT 1");
    $row = $result->fetch_assoc();
    $holat = $row["bot_status"];

    editMessage($cid,$mid2,"*️⃣ Hozirgi holati: $holat",json_encode([
            "inline_keyboard" => [
                [["text" => $xolat, "callback_data" => "bot"]],
            ],
        ])
    );
}

if($text=="⚙️ Xabar Sozlamalar" and admin($cid) == 1) {
    sendMessage($cid, "⤵️ Kerakli menyuni tanlang::", json_encode([
            "inline_keyboard" => [
                [["text" => "📝 Habarni pasidagi tugma","callback_data" => "changeKey"]],
            ],
        ])
    );
}

if($data=="changeKey" and admin($cid) == 1){
deleteMessage();
$rmarkup = json_decode(file_get_contents("lang/smskey.json"), true);
$repl_markup= json_encode(["inline_keyboard" => [[["text" => $rmarkup["text"], "url" =>$rmarkup["url"]]]]]);
sendMessage($cid, "⏳ <b>Hozirgi holatni ko'rishingiz mumkin 👇:</b>", $repl_markup);
sendMessage($cid, "✍️ <b>O'zgartirish kiritish uchun quyidagi shaklda ma'lumotlarni yuboring:</b>\n\n🔄 <b>Masalan:</b>\n<code>Yangi matn\nhttps://</code>\n\n<i>‼️ Faqat bitta tugma qosha olasiz</i>", $back_panel);
step($cid,"new_keysms");
}

if ($step == "new_keysms" and admin($cid) == 1) {
    if (isset($text)) {
        $parts = explode("\n", $text); 
        if (count($parts) == 2) {
            $new_text = trim($parts[0]); 
            $new_url = trim($parts[1]); 
            if (filter_var($new_url, FILTER_VALIDATE_URL) && (strpos($new_url, "https://") === 0 || strpos($new_url, "http://") === 0)) {
                $rmarkup = json_decode(file_get_contents("lang/smskey.json"), true); 
                $rmarkup['text'] = $new_text; 
                $rmarkup['url'] = $new_url;
                file_put_contents("lang/smskey.json", json_encode($rmarkup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); 
                sendMessage($cid, "✅ <b>Matn va URL muvaffaqiyatli yangilandi:</b>\n\n📋 <b>Yangi matn:</b> <code>$new_text</code>\n🔗 <b>Yangi URL:</b> $new_url");
                step($cid,"none");
            } else {
                sendMessage($cid, "❌ <b>Xato URL:</b> URL <code>https://</code> yoki <code>http://</code> bilan boshlanishi kerak!");
            }
        } else {
            sendMessage($cid, "⚠️ <b>Xato format:</b> Iltimos, matnni quyidagi shaklda yuboring:\n\n<code>Matn\nhttps://</code>");
        }
    }
}

if ($text == "✉️ Xabar Yuborish" and admin($cid) == 1) {
    $result = mysqli_query($connect, "SELECT * FROM `send`");
    $row = mysqli_fetch_assoc($result);
    $status = $row["status"];
    $sends_count = $row["sends_count"];
    $statistics = $row["statistics"];
    $receive_count = $row["receive_count"];
    if (!$row) {
        sendMessage($cid,"<b>📬 Foydalanuvchilarga yuboriladigan xabarni kiriting:</b>",$back_panel);
        step($cid, "send");
    } else {
        if ($status == "resume") {
            $kb = json_encode([
                "inline_keyboard" => [
                    [["text" => "To'xtatish ⏸","callback_data" => "sendstatus=stopped",]],
                    [["text" => "🗑 O'chirish","callback_data" => "bekorqilish_send", ]],
                ],
            ]);
        } else if ($status == "stopped") {
            $kb = json_encode([
                "inline_keyboard" => [
                    [[ "text" => "Davom ettirish ▶️","callback_data" => "sendstatus=resume"]],
                    [["text" => "🗑 O'chirish","callback_data" => "bekorqilish_send" ]],
                ],
            ]);
        }
        sendMessage($cid,"<b>✅ Yuborildi:</b> <code>$sends_count/$statistics</code>
<b>📥 Qabul qilindi:</b> <code>$receive_count</code>
<b>🔰 Status</b>: <code>$status</code>", $kb);
    }
}

if ($step == "send" and admin($cid) == 1) {
    $res = mysqli_query($connect, "SELECT * FROM `users` ORDER BY `id` DESC LIMIT 1;");
    $row = mysqli_fetch_assoc($res);
    
    if (!$row) {
        sendMessage($cid,"❌ Xato: `users` jadvalidan ma'lumot olinmadi!",$panel);
        step($cid,"none");
        exit();
    }

    $stop_id = $row["user_id"]; 
    $time1 = date("H:i", strtotime("+1 minutes"));
    $time2 = date("H:i", strtotime("+2 minutes"));
    $tugma = json_encode($update->message->reply_markup);
    $reply_markup = base64_encode($tugma);
    $stat = $connect->query("SELECT * FROM users")->num_rows;

    $edit_mess_id = sendMessage(
        $cid,
        "<b>✅ Yuborildi:</b> <code>0/$stat</code>
<b>📥 Qabul qilindi:</b> <code>0</code>
<b>🔰 Status</b>: <code>resume</code>",
        json_encode([
            "inline_keyboard" => [
                [["text" => "To'xtatish ⏸", "callback_data" => "sendstatus=stopped"]],
                [["text" => "🗑 O'chirish", "callback_data" => "bekorqilish_send"]],
            ],
        ])
    )->result->message_id;

    mysqli_query(
        $connect,
        "INSERT INTO `send` (`time1`,`time2`,`start_id`,`stop_id`,`admin_id`,`message_id`,`reply_markup`,`step`,`edit_mess_id`,`status`,`statistics`,`sends_count`,`receive_count`)
        VALUES ('$time1','$time2','0','$stop_id','$cid','$mid','$reply_markup','send','$edit_mess_id','resume','$stat',0,0)"
    );
    sendMessage($cid, "<b>🔄️ Qabul qilindi, bir necha daqiqadan keyin yuborish boshlanadi!</b>", $panel);
    step($cid, "none");
}

if ($data == "bekorqilish_send" and admin($cid) == 1) {
    mysqli_query($connect, "DELETE FROM `send`");
    deleteMessage();
    answerCallback($qid, "Xabar yuborish bekor qilindi!");
    sendMessage($cid, "<b>Admin paneliga xush kelibsiz!</b>", $panel);
    step($cid, "none");
    exit();
}

if (mb_stripos($data, "sendstatus=") !== false and admin($cid) == 1) {
    $up_stat = explode("=", $data)[1];
    $result = mysqli_query($connect, "SELECT * FROM `send`");
    $row = mysqli_fetch_assoc($result);
    if ($row["status"] == $up_stat) {
        answerCallback($qid, "Xabar yuborish xolati $up_stat ga o'zgartirolmaysiz.", 1);
    } else {
        if ($up_stat == "resume") {
            $time1 = date("H:i", time() + 60);
            $time2 = date("H:i", time() + 120);
            mysqli_query(
                $connect,
                "UPDATE `send` SET time1 = '$time1', `time2` = '$time2'"
            );
        }
        if ($up_stat == "resume") {
            $kb = json_encode([
                "inline_keyboard" => [
                    [["text" => "To'xtatish ⏸","callback_data" => "sendstatus=stopped"]],
                    [["text" => "🗑 O'chirish","callback_data" => "bekorqilish_send"]],
                ],
            ]);
        } elseif ($up_stat == "stopped") {
            $kb = json_encode([
                "inline_keyboard" => [
                    [["text" => "Davom ettirish ▶️", "callback_data" => "sendstatus=resume"]],
                    [["text" => "🗑 O'chirish","callback_data" => "bekorqilish_send"]],
                ],
            ]);
        }
        $edit_mess_id = editMessage($cid, $mid2, "<b>✅ Yuborildi:</b> <code>" .
                $row["sends_count"] .
                "/" .
                $row["statistics"] .
                "</code>
<b>📥 Qabul qilindi:</b> <code>" .
                $row["receive_count"] .
                "</code>
<b>🔰 Status</b>: <code>$up_stat</code>",
            $kb
        )->result->message_id;
        mysqli_query($connect, "UPDATE `send` SET edit_mess_id = '$edit_mess_id', `status` = '$up_stat'");
    }
}

    
















