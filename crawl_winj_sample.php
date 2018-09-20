<?php
require_once 'vendor/autoload.php';
require_once 'WinjClient.php';

/* 実行結果イメージ

【貸出状況】(1件)
＊＊＊＊書名＊＊＊＊ : 2018年9月24日まで

【予約状況】(5件)
＊＊＊＊書名＊＊＊＊＊＊＊＊＊＊＊＊ : 受取可 : 2018年9月23日まで
＊＊＊＊書名＊＊＊＊＊＊＊＊＊＊ : 受取可 : 2018年9月24日まで
＊＊＊＊書名＊＊＊＊＊＊ : 予約中 : 4位（あと56日くらい）
＊＊＊＊書名＊＊＊＊＊＊＊＊＊＊＊＊＊＊ : 予約中 : 22位（あと154日くらい）
＊＊＊＊書名＊＊＊＊＊＊＊＊＊＊ : 予約中 : 15位（あと210日くらい）

 */

//login
$login_url = "https://www.lib-shibuya.tokyo.jp/winj/opac/login.do?lang=ja&dispatch=/opac/mylibrary.do&every=1";
$form_selector = "form[name='LoginForm']";
$login_params = [
    "txt_usercd" => "********",
    "txt_password" => "********"
];
$client = new WinjClient($login_url, $login_params, $form_selector);

//貸出状況
$list = $client->getBorrowing("返却予定日:"); //千代田区なら"返却期限:"
if (count($list) >= 1) {
    echo "\n【貸出状況】(" . count($list) . "件)\n";
}
foreach ($list as $item) {
    echo $item["title"] . " : " . $item["expiration"] . "まで\n";
}

//予約状況
$list = $client->getReservation();
if (count($list) >= 1) {
    echo "\n【予約状況】(" . count($list) . "件)\n";
}
foreach ($list as $item) {
    echo $item["title"] . " : " . $item["status"];
    if (isset($item["expiration"])) {
        echo " : " . $item["expiration"] . "まで";
    }
    if (isset($item["order"])) {
        echo " : " . $item["order"] . "位";
        if (isset($item["expected_period"])) {
            echo "（あと" . $item["expected_period"] . "日くらい）";
        }
    }
    echo "\n";
}