<?php

namespace waylaidwanderer\SteamCommunity\User;

use waylaidwanderer\SteamCommunity\Helper;
use waylaidwanderer\SteamCommunity\SteamCommunity;

class Friend
{
    public function getFriendList($steamId = false, $relationship = 'friend')
    {
        if (!$steamId) {
            $steamId = SteamCommunity::getInstance()->get('steamId');
        }

        $url = SteamCommunity::getInstance()->getClassFromCache('WebApi')->getApiUrl() . 'ISteamUser/GetFriendList/v1/?key=' . SteamCommunity::getInstance()->get('apiKey') . '&steamid=' . Helper::toCommunityID($steamId) . '&relationship=' . $relationship;
        $response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($url);
        if (!$response = Helper::processJson($response)) {
            return false;
        }

        $friends = array();
        foreach ($response['friendslist']['friends'] as $friend) {
            $friends[$friend['steamid']] = $friend;
        }

        return $friends;
    }

    public function removeFriend($steamId)
    {
        $params = [
            'steamid' => Helper::toCommunityID($steamId),
            'sessionID' => SteamCommunity::getInstance()->get('sessionId')
        ];

        $url = 'https://steamcommunity.com/actions/RemoveFriendAjax';
        $response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($url, null, $params);
        $response = Helper::processJson($response);

        if ($response === false) {
            return false;
        }

        return true;
    }
}