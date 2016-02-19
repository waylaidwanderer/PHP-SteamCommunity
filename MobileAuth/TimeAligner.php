<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2016-02-08
 * Time: 12:18 AM
 */

namespace waylaidwanderer\SteamCommunity\MobileAuth;


use waylaidwanderer\SteamCommunity\Helper;

class TimeAligner
{
    public static function GetSteamTime()
    {
        return time() + self::GetTimeDifference();
    }

    public static function GetTimeDifference()
    {
        try {
            $response = Helper::cURL('http://api.steampowered.com/ITwoFactorService/QueryTime/v0001', null, ['steamid' => 0]);
            $json = json_decode($response, true);
            if (isset($json['response']) && isset($json['response']['server_time'])) {
                return (int)$json['response']['server_time'] - time();
            }
        } catch (\Exception $ex) {

        }
        return 0;
    }
}
