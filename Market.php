<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2015-10-06
 * Time: 1:57 PM
 */

namespace waylaidwanderer\SteamCommunity;


class Market
{
    protected $steamCommunity;

    public function __construct(SteamCommunity $steamCommunity)
    {
        $this->steamCommunity = $steamCommunity;
    }

    public function getRecentCompleted()
    {
        try {
            $url = "http://steamcommunity.com/market/recentcompleted";
            $response = $this->steamCommunity->cURL($url, 'http://steamcommunity.com/market');
            $json = json_decode($response, true);
            return $json;
        } catch (\Exception $ex) {
            throw new SteamException("Failed to retrieve /recentcompleted from market.");
        }
    }
}