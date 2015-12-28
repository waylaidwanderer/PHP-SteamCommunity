<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2015-12-28
 * Time: 3:43 AM
 */

namespace waylaidwanderer\SteamCommunity;


class Helper
{
    /**
     * @link https://gist.github.com/rannmann/49c1321b3239e00f442c
     * @param $id
     * @return string
     */
    public static function toCommunityID($id) {
        if (preg_match('/^STEAM_/', $id)) {
            $parts = explode(':', $id);
            return bcadd(bcadd(bcmul($parts[2], '2'), '76561197960265728'), $parts[1]);
        } elseif (is_numeric($id) && strlen($id) < 16) {
            return bcadd($id, '76561197960265728');
        } else {
            return $id; // We have no idea what this is, so just return it.
        }
    }
}