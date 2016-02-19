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
    private $profile;

    public function __construct($steamId)
    {
        $this->steamId = $steamId;
    }

    public function getProfileXml()
    {
        if ($this->profile == null) {
            $url = self::BASE_URL . $this->steamId . '/?xml=1';
            $xml = Helper::cURL($url);
            $this->profile = new \SimpleXMLElement($xml);
        }

        return $this->profile;
    }

    public function getPersonaName()
    {
        $profile = $this->getProfileXml();
        return (string)$profile->steamID;
    }

    /**
     * @return Group[]
     */
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
