<?php

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler; //インスペクション対策

/**
 * URLが「clis」となっている図書館システムのクライアント
 */
class ClisClient
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
        $form = $this->crawler->filter($form_selector)->form();
        $this->crawler = $this->client->submit($form, $login_params);
    }

    //public

    /**
     * 貸出状況を取得する
     * @return array 貸出状況(No, type, title, id, place, lent_at, expiration, extend)
     */
    public function getBorrowing()
    {
        $this->clickLink("貸出状況照会へ");
        $list = $this->crawler->filter("table.FULL tbody tr")->each(function (Crawler $tr) {
            $item = $tr->filter("td")->each(function (Crawler $td) {
                return $this->zenkaku_trim($td->text());
            });
            return array_combine([
                "No", "type", "title", "id", "place", "lent_at", "expiration", "extend"
            ], $item);
        });
        $this->back();
        return $list;
    }

    /**
     * 予約状況を取得する
     * @return array 予約状況(No, type, title, place, reserved_at, status, expiration, stock, order)
     */
    public function getReservation()
    {
        $this->clickLink("予約状況照会へ");
        $list = $this->crawler->filter("table.FULL tbody tr")->each(function (Crawler $tr) {
            $item = $tr->filter("td")->each(function (Crawler $td) {
                return $this->zenkaku_trim($td->text());
            });
            return array_combine([
                "No", "type", "title", "place", "reserved_at", "status", "expiration", "stock", "order"
            ], $item);
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
        $this->clickLink("メニューへ戻る");
    }

    private function zenkaku_trim($str)
    {
        return trim(preg_replace("/^[\s　]+|[\s　]+$/u", " ", $str));
    }
}
