<?php
require_once 'vendor/autoload.php';
require_once 'ClisClient.php';

/* 実行結果イメージ

【貸出状況】(1件)
＊＊＊＊書名＊＊＊＊ : 2018年9月24日まで : 延長不可

【予約状況】(5件)
＊＊＊＊書名＊＊＊＊＊＊＊＊＊＊＊＊ : 受取可 : 2018年9月23日まで
＊＊＊＊書名＊＊＊＊＊＊＊＊＊＊ : 受取可 : 2018年9月24日まで
＊＊＊＊書名＊＊＊＊＊＊ : 予約中 : 4位（あと56日くらい）
＊＊＊＊書名＊＊＊＊＊＊＊＊＊＊＊＊＊＊ : 予約中 : 22位（あと154日くらい）
＊＊＊＊書名＊＊＊＊＊＊＊＊＊＊ : 予約中 : 15位（あと210日くらい）

 */

//login
$login_url = "https://libweb.city.setagaya.tokyo.jp/idcheck.shtml";
$form_selector = "form.loginform";
$login_params = [
    "UID" => "********",
    "PASS" => "********",
];
$client = new ClisClient($login_url, $login_params, $form_selector);

//貸出状況
$list = $client->getBorrowing();
if (count($list) >= 1) {
    echo "\n【貸出状況】(" . count($list) . "件)\n";
}
foreach ($list as $item) {
    echo $item["title"] . " : " . $item["expiration"] . "まで : 延長" . $item["extend"] . "\n";
}

//予約状況
$list = $client->getReservation();
if (count($list) >= 1) {
    echo "\n【予約状況】(" . count($list) . "件)\n";
}
foreach ($list as $item) {
    echo $item["title"] . " : " . $item["status"];
    if (strlen($item["expiration"]) > 0) {
        echo " : " . $item["expiration"] . "まで";
    }
    if ((int)$item["order"] >= 1) {
        echo " : " . $item["order"] . "位";

        //受取可能までの待ち日数を予測する
        if ((int)$item["stock"] >= 1) {
            $period = (float)$item["order"] / (float)$item["stock"] * 14; //1冊が2週間で回ると仮定する
            echo "（あと" . (int)$period . "日くらい）";
        }
    }
    echo "\n";
}