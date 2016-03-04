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
    private $xml;

    public function __construct($gid, SteamCommunity $steamCommunity = null)
    {
        $this->gid = $gid;
        $this->steamCommunity = is_null($steamCommunity) ? new SteamCommunity() : $steamCommunity;
    }

    public function getGroupXml($page = 1)
    {
        if ($this->xml == null) {
            $url = self::BASE_URL . $this->gid . '/memberslistxml/?xml=1&p=' . $page;
            $response = Helper::cURL($url);
            $this->xml = new \SimpleXMLElement($response);
        }

        return $this->xml;
    }

    /**
     * @return int Number of pages in memberslistxml.
     */
    public function getNumXmlPages()
    {
        $xml = $this->getGroupXml();
        return (int)$xml->totalPages;
    }

    /**
     * @return array An array of SteamID64s.
     */
    public function getMembersList()
    {
        $members = [];
        $numPages = $this->getNumXmlPages();
        for ($i = 1; $i <= $numPages; $i++) {
            $members = array_merge($members, $this->getMembersListForPage($i));
        }
        return $members;
    }

    /**
     * @param $page
     * @return array An array of SteamID64s.
     */
    public function getMembersListForPage($page)
    {
        $members = [];
        $xml = $this->getGroupXml($page);
        foreach ($xml->members->steamID64 as $steamID64) {
            $members[] = (string)$steamID64;
        }
        return $members;
    }

    /**
     * @return int
     */
    public function getMemberCount()
    {
        $xml = $this->getGroupXml();
        return (int)$xml->memberCount;
    }

    /**
     * @param int $page
     * @return HistoryItem[]
     */
    public function getHistory($page = 1)
    {
        $history = [];

        $xpath = $this->getHistoryXPath($page);
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

    public function getNumHistoryPages()
    {
        $xpath = $this->getHistoryXPath();
        $pagingText = $xpath->query('//div[contains(@class, "group_summary")]/div[contains(@class, "group_paging")]/p');
        if (preg_match('/(?<=of )(.*)(?= History)/', $pagingText->item(0)->nodeValue, $matches)) {
            return (int)ceil($matches[1] / 50);
        }
        return 1;
    }

    private function getHistoryXPath($page = 1)
    {
        $url = self::BASE_URL . $this->gid . '/history?p=' . $page;
        $html = $this->steamCommunity->cURL($url);
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML($html);
        return new \DOMXPath($doc);
    }
}
