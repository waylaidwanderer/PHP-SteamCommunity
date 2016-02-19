<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2016-02-18
 * Time: 11:30 AM
 */

namespace waylaidwanderer\SteamCommunity;


use waylaidwanderer\SteamCommunity\Group\History\HistoryItem;

class Group
{
    const BASE_URL = "http://steamcommunity.com/gid/";
    private $steamCommunity;
    private $gid;

    public function __construct($gid, SteamCommunity $steamCommunity = null)
    {
        $this->gid = $gid;
        $this->steamCommunity = is_null($steamCommunity) ? new SteamCommunity() : $steamCommunity;
    }

    /**
     * @param int $page
     * @return HistoryItem[]
     */
    public function getHistory($page = 1)
    {
        $history = [];

        $url = self::BASE_URL . $this->gid . '/history?p=' . $page;
        $html = $this->steamCommunity->cURL($url);
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML($html);
        $xpath = new \DOMXPath($doc);

        /** @var \DOMElement[] $historyItems */
        $historyItems = $xpath->query('//div[contains(@class, "group_summary")]/div[contains(@class, "historyItem")]');
        foreach ($historyItems as $historyItem) {
            $type = $xpath->query('.//span[contains(@class, "historyShort")]', $historyItem)->item(0)->nodeValue;
            $date = $xpath->query('.//span[contains(@class, "historyDate")]', $historyItem)->item(0)->nodeValue;
            $users = $xpath->query('.//a', $historyItem);
            if ($users->length == 2) {
                /** @var \DOMElement $user */
                $user = $users->item(1);
                $userAccountId = $user->getAttribute('data-miniprofile');
                /** @var \DOMElement $targetUser */
                $targetUser = $users->item(0);
                $targetAccountId = $targetUser->getAttribute('data-miniprofile');
                $history[] = new HistoryItem($type, $date, Helper::toCommunityID($userAccountId), Helper::toCommunityID($targetAccountId));
            } else if ($users->length == 1) {
                $user = $users->item(0);
                $userAccountId = $user->getAttribute('data-miniprofile');
                $history[] = new HistoryItem($type, $date, Helper::toCommunityID($userAccountId));
            }
        }

        return $history;
    }
}