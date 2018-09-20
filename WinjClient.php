<?php

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler; //インスペクション対策

/**
 * URLが「winj」となっている図書館システムのクライアント
 */
class WinjClient
{
    private $client;
    private $crawler;

    /**
     * ClisClient constructor.
     * @param string $login_url ログインURL
     * @param array $login_params ログイン情報 [name => value]
     * @param string $form_selector ログインフォームのセレクタ
     */
    public function __construct($login_url, $login_params, $form_selector = "form")
    {
        $this->client = new Client();
        $this->crawler = $this->client->request('GET', $login_url);
        $title = $this->crawler->filter('title')->text();

        //トップページに飛ばされる？のでログインページに移動する
        if (strpos($title, "マイページ") === false
            and strpos($title, "認証") === false) {
            $this->clickLink("ログインする");
            $title = $this->crawler->filter('title')->text();
        }

        //ログインする
        if (strpos($title, "マイページ") === false) { //ログイン済みならスキップする
            /* submitボタンもパラメータに含めたくてハマった
                 cf. https://teratail.com/questions/58130 */
            $form = $this->crawler->filter($form_selector)->selectButton("ログイン")->form();
            $this->crawler = $this->client->submit($form, $login_params);
        }
    }

    /**
     * 貸出状況を取得する
     * @param string $expiration_pattern 返却期限の表記。":"等の記号を含む。
     * @return array 貸出状況(title, expiration)
     */
    public function getBorrowing($expiration_pattern = "返却期限:")
    {
        $this->clickLink("借りている資料");
        $list = $this->crawler->filter("ol.list-book li div.lyt-image")->each(function (Crawler $li) use ($expiration_pattern) {
            $item["title"] = $this->zenkaku_trim($li->filter("span.title")->first()->text());
            $info = $li->filter("div.info")->first()->text();
            if (preg_match("/{$expiration_pattern}([\d\/]+)/", $info, $matches)) {
                $item["expiration"] = $matches[1];
            }
            return $item;
        });
        $this->back();
        return $list;
    }

    /**
     * 予約状況を取得する
     *
     * @param string $expiration_pattern
     * @return array 予約状況(title, place, reserved_at, status, expiration, order, num_reserved, expected_period)
     */
    public function getReservation($expiration_pattern = "取置期限日:")
    {
        $this->clickLink("予約した資料");
        $list = $this->crawler->filter("ol.list-book li div.lyt-image")->each(function (Crawler $li) use ($expiration_pattern) {
            $item["title"] = $this->zenkaku_trim($li->filter("span.title")->first()->text());
            $item["title"] = preg_replace("/[\s\n]+/", "　", $item["title"]);
            $info = $li->filter("div.info")->first()->text();
            if (preg_match("/{$expiration_pattern}([\d\/]+)/", $info, $matches)) {
                $item["expiration"] = $matches[1];
            }
            if (preg_match("/受取館:([^\s]+)/", $info, $matches)) {
                $item["place"] = $matches[1];
            }
            if (preg_match("/予約日:([\d\/]+)/", $info, $matches)) {
                $item["reserved_at"] = $matches[1];
            }
            if (preg_match("/貸出可能/", $info, $matches)) {
                $item["status"] = "受取可";
            } elseif (preg_match("/回送/", $info, $matches)) {
                $item["status"] = "回送中";
            }elseif (preg_match("/\((\d+)位\)/", $info, $matches)) {
                $item["order"] = $matches[1];
                $item["status"] = "予約中";
            } else {
                $item["status"] = "状態不明";
            }
            if (preg_match("/予約数： (\d+)/", $info, $matches)) {
                $item["num_reserved"] = $matches[1];

                //リトルの法則（？）で受け取りまでの待ち日数を予測する。おまけ
                if (isset($item["reserved_at"]) && isset($item["order"])) {
                    $reserved_at = new DateTime($item["reserved_at"]);
                    $today = new DateTime();
                    $passed_days = (float)$today->diff($reserved_at)->days;
                    $num_added = $item["num_reserved"] - $item["order"];
                    if ($passed_days >= 1 && $num_added >= 1) {
                        $W = $item["order"] / ($num_added / $passed_days); // W = L / λ たぶん

                        //所蔵数1と仮定したときの予想待ち日数を最長とする
                        $item["expected_period"] = min((int)$W, $item["order"] * 14);
                    }
                }
            }
            return $item;
        });
        $this->back();
        return $list;
    }

    //private

    private function clickLink($value)
    {
        $link = $this->crawler->selectLink($value)->link();
        $this->crawler = $this->client->click($link);
    }

    private function back()
    {
        $this->clickLink("マイページ");
    }

    private function zenkaku_trim($str)
    {
        return trim(preg_replace("/^[\s　]+|[\s　]+$/u", " ", $str));
    }
}
