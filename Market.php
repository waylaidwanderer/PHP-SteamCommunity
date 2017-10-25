<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2015-10-06
 * Time: 1:57 PM
 */

namespace waylaidwanderer\SteamCommunity;

use waylaidwanderer\SteamCommunity\SteamCommunity;
use waylaidwanderer\SteamCommunity\Market\Listing;
use waylaidwanderer\SteamCommunity\Market\Listing\ItemType;
use waylaidwanderer\SteamCommunity\Market\PriceHistory;
use waylaidwanderer\SteamCommunity\Market\PriceOverview;

class Market
{
    public function getListings($appId, array $criteria)
    {
        $searchCriteria = array_merge(array(
            'query' => '',
            'start' => 0,
            'count' => 10,
            'descriptions' => 0,
            'q' => '',
            'search_descrptions' => 0,
            'sort_column' => 'popular',
            'sort_dir' => 'desc',
            'appid' => $appId
        ), $criteria);

        $url = 'https://steamcommunity.com/market/search/render/?' . http_build_query($searchCriteria);
        $response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($url, 'http://steamcommunity.com/market/search?appid=' . $appId);

        $json = json_decode($response, true);
        if (empty($json['success'])) {
            throw new SteamException("Could not retrieve listings ({$appId}) from Steam (1).");
        }

        $results = array(
            'start' => $json['start'],
            'pagesize' => $json['pagesize'],
            'total_count' => $json['total_count'],
            'listings' => array()
        );

        libxml_use_internal_errors(true);

        $doc = new \DOMDocument();
        $doc->loadHTML(mb_convert_encoding($json['results_html'], 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($doc);
        $searchItemElements = $xpath->query('//a[@id[starts-with(.,"resultlink_")]]');

        foreach ($searchItemElements as $searchItemElement) {
            $listing = new Listing();
            $listing->setAppId($appId);

            $image = $xpath->query('.//img[contains(@class, "market_listing_item_img")]', $searchItemElement)->item(0);
            if (!empty($image)) {
                $listing->setImage(substr($image->getAttribute('src'), 0, strrpos($image->getAttribute('src'), '/')));
            }

            $listing->setQuantity((int) $xpath->query('.//span[contains(@class, "market_listing_num_listings_qty")]', $searchItemElement)->item(0)->nodeValue);
            $listing->setNormalPrice(preg_replace('/[^0-9,.]/', '', $xpath->query('.//span[contains(@class, "normal_price")]/span[contains(@class, "normal_price")]', $searchItemElement)->item(0)->nodeValue));
            $listing->setSalePrice(preg_replace('/[^0-9,.]/', '', $xpath->query('.//span[contains(@class, "sale_price")]', $searchItemElement)->item(0)->nodeValue));

            $titleElement = $xpath->query('.//div[contains(@class, "market_listing_item_name_block")]', $searchItemElement)->item(0);

            $marketName = $xpath->query('.//span[contains(@class, "market_listing_item_name")]', $titleElement)->item(0);
            $listing->setMarketName($marketName->nodeValue);

            preg_match('/color:.*(#.*);/', $marketName->getAttribute('style'), $colorParams);
            if (!empty($colorParams)) {
                $listing->setColor($colorParams[1]);
            }

            if ($appId == 753) {
                preg_match('/listings\/.*\/([0-9]+)-.*/', $searchItemElement->getAttribute('href'), $titleParams);
                if (!empty($titleParams)) {
                    $listing->setSecondaryAppId((int) $titleParams[1]);
                }

                $marketDescription = $xpath->query('.//span[contains(@class, "market_listing_game_name")]', $titleElement)->item(0)->nodeValue;

                if (strpos($marketDescription, 'Steam Gems') !== false) {
                    $listing->setItemType(ItemType::SteamGems);
                } else if (strpos($marketDescription, 'Trading Card') !== false) {
                    $listing->setItemType(ItemType::TradingCard);
                } else if ($marketDescription == 'Booster Pack') {
                    $listing->setItemType(ItemType::BoosterPack);
                } else if (strpos($marketDescription, 'Emoticon') !== false) {
                    $listing->setItemType(ItemType::Emoticon);
                } else if (strpos($marketDescription, 'Profile Background') !== false) {
                    $listing->setItemType(ItemType::ProfileBackground);
                } else {
                    $listing->setItemType(ItemType::ItemReward);
                }
            }

            $results['listings'][] = $listing;
            unset($listing);
        }

        return $results;
    }

    public function getRecentCompleted()
    {
        try {
            $url = "https://steamcommunity.com/market/recentcompleted";
            $response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($url, 'http://steamcommunity.com/market');
            $json = json_decode($response, true);
            return $json;
        } catch (\Exception $ex) {
            throw new SteamException("Failed to retrieve /recentcompleted from market.");
        }
    }

    public function getPriceOverview($appId, $marketHashName)
    {
        $marketHashName = str_replace('%2F', '%252F', rawurlencode($marketHashName));
        $url = "https://steamcommunity.com/market/priceoverview/?currency=1&appid={$appId}&market_hash_name={$marketHashName}";
        try {
            $response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($url);
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
        $url = "https://steamcommunity.com/market/pricehistory/?appid={$appId}&market_hash_name={$marketHashName}";
        try {
            $response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($url);
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

    public function getWalletBalance()
    {
        if (SteamCommunity::getInstance()->isLoggedIn()) {
            $url = 'https://steamcommunity.com/market/';
            $response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($url);

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
