<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2016-02-18
 * Time: 4:48 PM
 */

namespace waylaidwanderer\SteamCommunity;


use waylaidwanderer\SteamCommunity\User\Group;

class User
{
    const BASE_URL = "http://steamcommunity.com/profiles/";
    private $steamId;

    public function __construct($steamId)
    {
        $this->steamId = $steamId;
    }

    public function getProfileXml()
    {
        $url = self::BASE_URL . $this->steamId . '/?xml=1';
        $xml = Helper::cURL($url);

        return new \SimpleXMLElement($xml);
    }

    public function getGroups()
    {
        $groups = [];
        $profile = $this->getProfileXml();
        foreach ($profile->groups->group as $groupXml) {
            $groups[] = new Group($groupXml);
        }
        return $groups;
    }
}