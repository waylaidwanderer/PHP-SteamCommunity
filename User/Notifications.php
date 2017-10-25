<?php

namespace waylaidwanderer\SteamCommunity\User;

use waylaidwanderer\SteamCommunity\SteamCommunity;
use waylaidwanderer\SteamCommunity\Helper;

class Notifications
{
    protected $notificationTypes = array(
        1 => 'tradeOffers',
        2 => 'gameNotifications',
        3 => 'moderatorMessages',
        4 => 'comments',
        5 => 'inventory',
        6 => 'invites',
        7 => 'unknown1',
        8 => 'gifts',
        9 => 'offlineMessages',
        10 => 'helpRequests',
        11 => 'unknown2'
    );

    public function getNotifications()
    {
        $response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL('https://steamcommunity.com/actions/GetNotificationCounts');
        if (!$response = Helper::processJson($response)) {
            return false;
        }

        if (!array_key_exists('notifications', $response)) {
            return false;
        }

        $notifications = reset($response);
        return $this->parseNotifications($notifications);
    }

    public function parseNotifications($notifications)
    {
        $parsed = array();
        foreach ($notifications as $typeId => $count) {
            $parsed[$this->notificationTypes[$typeId]] = $count;
        }

        return $parsed;
    }
}