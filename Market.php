<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2015-10-06
 * Time: 1:57 PM
 */

namespace waylaidwanderer\SteamCommunity;


use waylaidwanderer\SteamCommunity\Market\Listings;
use waylaidwanderer\SteamCommunity\Market\PriceHistory;
use waylaidwanderer\SteamCommunity\Market\PriceOverview;

class Market
{
    private $steamCommunity;

    public function __construct(SteamCommunity $steamCommunity = null)
    {
        $this->steamCommunity = is_null($steamCommunity) ? new SteamCommunity() : $steamCommunity;
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

    public function getPriceOverview($appId, $marketHashName)
    {
        $marketHashName = str_replace('%2F', '%252F', rawurlencode($marketHashName));
        $url = "http://steamcommunity.com/market/priceoverview/?currency=1&appid={$appId}&market_hash_name={$marketHashName}";
        try {
            $response = $this->steamCommunity->cURL($url);
            $json = json_decode($response, true);
            if (isset($json['success']) && $json['success']) {
                return new PriceOverview($json);
            } else {
                throw new SteamException("Could not retrieve price overview for item {$marketHashName} ({$appId}) from Steam (1).");
            }
        } catch (\Exception $ex) {
            throw new SteamException("Could not retrieve price overview for item {$marketHashName} ({$appId}) from Steam (2).");
        }
    }

    public function getPriceHistory($appId, $marketHashName)
    {
        $marketHashName = str_replace('%2F', '%252F', rawurlencode($marketHashName));
        $url = "http://steamcommunity.com/market/pricehistory/?appid={$appId}&market_hash_name={$marketHashName}";
        try {
            $response = $this->steamCommunity->cURL($url);
            $json = json_decode($response, true);
            if (isset($json['success']) && $json['success']) {
                return new PriceHistory($json);
            } else {
                throw new SteamException("Could not retrieve price history for item {$marketHashName} ({$appId}) from Steam (1).");
            }
        } catch (\Exception $ex) {
            throw new SteamException("Could not retrieve price history for item {$marketHashName} ({$appId}) from Steam (2).");
        }
    }

    public function getListings($appId, $marketHashName)
    {
        $marketHashName = str_replace('%2F', '%252F', rawurlencode($marketHashName));
        $url = "http://steamcommunity.com/market/listings/{$appId}/{$marketHashName}/render?currency=1";
        try {
            $response = $this->steamCommunity->cURL($url);
            $json = json_decode($response, true);
            if (isset($json['success']) && $json['success']) {
                return new Listings($json);
            } else {
                throw new SteamException("Could not retrieve listings for item {$marketHashName} ({$appId}) from Steam (1).");
            }
        } catch (\Exception $ex) {
            throw new SteamException("Could not retrieve listings for item {$marketHashName} ({$appId}) from Steam (2).");
        }
    }

    public function getWalletBalance()
    {
        if ($this->steamCommunity->isLoggedIn()) {
            $url = 'http://steamcommunity.com/market/';
            $response = $this->steamCommunity->cURL($url);

            $pattern = '/<span id=\"marketWalletBalanceAmount\">(.*)<\/span>/i';
            preg_match($pattern, $response, $matches);
            if (!isset($matches[1])) {
                throw new SteamException('Unexpected response from Steam.');
            }
            $balance = $matches[1];
            if (substr($balance, -1) == '.') {
                $balance = substr($balance, 0, -1);
            }
            return Helper::getAmount($balance);
        } else {
            return 0;
        }
    }
}
