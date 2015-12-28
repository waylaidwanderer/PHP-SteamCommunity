<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2015-12-27
 * Time: 4:41 PM
 */

namespace waylaidwanderer\SteamCommunity;


class TradeOffers
{
    const BASE_URL = 'http://steamcommunity.com/my/tradeoffers/';

    protected $steamCommunity;

    public function __construct(SteamCommunity $steamCommunity)
    {
        $this->steamCommunity = $steamCommunity;
    }

    public function getIncomingOffers()
    {
        $url = self::BASE_URL;
    }

    public function getIncomingOfferHistory()
    {
        $url = self::BASE_URL . '?history=1';
    }

    public function getSentOffers()
    {
        $url = self::BASE_URL . 'sent/';
    }

    public function getSentOfferHistory()
    {
        $url = self::BASE_URL . 'sent/?history=1';
    }

    private function parseTradeOffers()
    {

    }
}