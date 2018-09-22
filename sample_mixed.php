<html>
<head>
    <style>
        body {
            background: #333;
        }

        a, pre {
            color: #ccc;
        }

        strong {
            color: #fff;
            font-weight: normal;
        }
    </style>

</head>
<body>
<pre>
<?php
require_once 'vendor/autoload.php';
require_once 'ClisClient.php';
require_once 'WinjClient.php';

function sortArrayByKey(&$array, $sortKey, $sortType = SORT_ASC)
{
    $sort_array = [];
    foreach ($array as $key => $row) {
        if (!isset($row[$sortKey]) or is_null($row[$sortKey])) {
            $sort_array[$key] = $key * 0.01; //元の順番を保つ小細工
        } else {
            $sort_array[$key] = $row[$sortKey];
        }
    }
    array_multisort($sort_array, $sortType, $array);
    unset($sort_array);
}

function fixDateFormat($str)
{
    if (preg_match("/(\d+)年(\d+)月(\d+)日/", $str, $m)) {
        $str = $m[1] . "/" . sprintf("%02d", $m[2]) . "/" . sprintf("%02d", $m[3]);
    }
    return $str;
}

//世田谷
$client = new ClisClient("https://libweb.city.setagaya.tokyo.jp/idcheck.shtml", [
    "UID" => "********",
    "PASS" => "********",
], "form.loginform");
$seta_bor = $client->getBorrowing();
$seta_res = $client->getReservation();
sortArrayByKey($seta_res, "order");

//千代田
$client = new WinjClient("https://opc.library.chiyoda.tokyo.jp/winj/opac/login.do?dispatch=/opac/mylibrary.do&every=1", [
    "txt_usercd" => "********",
    "txt_password" => "********"
], "form[name='LoginForm']");
$chiyo_bor = $client->getBorrowing("返却期限:");
$chiyo_res = $client->getReservation();
sortArrayByKey($chiyo_res, "order");

//渋谷
$client = new WinjClient("https://www.lib-shibuya.tokyo.jp/winj/opac/login.do?lang=ja&dispatch=/opac/mylibrary.do&every=1", [
    "txt_usercd" => "********",
    "txt_password" => "********"
], "form[name='LoginForm']");
$shibu_bor = $client->getBorrowing("返却予定日:");
$shibu_res = $client->getReservation();
sortArrayByKey($shibu_res, "order");

if (count($seta_bor) >= 1) {
    echo "世田谷\n";
    foreach ($seta_bor as $item) {
        echo "　" . fixDateFormat($item["expiration"]) . "まで : " . $item["title"] . " : 延長" . $item["extend"] . "\n";
    }
}
if (count($chiyo_bor) >= 1) {
    echo "千代田\n";
    foreach ($chiyo_bor as $item) {
        echo "　" . $item["expiration"] . "まで : " . $item["title"] . "\n";
    }
}
if (count($shibu_bor) >= 1) {
    echo "渋谷\n";
    foreach ($shibu_bor as $item) {
        echo "　" . $item["expiration"] . "まで : " . $item["title"] . "\n";
    }
}

echo "\n";

if (count($seta_res) >= 1) {
    echo "世田谷\n";
    foreach ($seta_res as $item) {
        if ($item["status"] === "受取可") {
            echo "<strong>";
        }
        echo "　" . $item["status"];
        if ((int)$item["order"] >= 1) {
            echo " : " . sprintf("%3d", $item["order"]) . "位";
        }
        if (strlen($item["expiration"]) > 0) {
            echo " : " . fixDateFormat($item["expiration"]) . "まで";
        }
        echo " : " . $item["title"];
        if ($item["status"] === "受取可") {
            echo "</strong>";
        }
        echo "\n";
    }
}
if (count($chiyo_res) >= 1) {
    echo "千代田\n";
    foreach ($chiyo_res as $item) {
        if ($item["status"] === "受取可") {
            echo "<strong>";
        }
        echo "　" . $item["status"];
        if (isset($item["order"])) {
            echo " : " . sprintf("%3d", $item["order"]) . "位";
        }
        if (isset($item["expiration"])) {
            echo " : " . $item["expiration"] . "まで";
        }
        echo " : " . $item["title"];
        if ($item["status"] === "受取可") {
            echo "</strong>";
        }
        echo "\n";
    }
}

if (count($shibu_res) >= 1) {
    echo "渋谷\n";
    foreach ($shibu_res as $item) {
        if ($item["status"] === "受取可") {
            echo "<strong>";
        }
        echo "　" . $item["status"];
        if (isset($item["order"])) {
            echo " : " . sprintf("%3d", $item["order"]) . "位";
        }
        if (isset($item["expiration"])) {
            echo " : " . $item["expiration"] . "まで";
        }
        echo " : " . $item["title"];
        if ($item["status"] === "受取可") {
            echo "</strong>";
        }
        echo "\n";
    }
}

?>
</pre>
</body>
</html>