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

    /**
     * @link https://gist.github.com/rannmann/49c1321b3239e00f442c
     * @param $id
     * @return string
     */
    public static function toAccountID($id) {
        if (preg_match('/^STEAM_/', $id)) {
            $split = explode(':', $id);
            return $split[2] * 2 + $split[1];
        } elseif (preg_match('/^765/', $id) && strlen($id) > 15) {
            return bcsub($id, '76561197960265728');
        } else {
            return $id; // We have no idea what this is, so just return it.
        }
    }

    /**
     * @link https://github.com/mcuadros/currency-detector/blob/master/src/CurrencyDetector/Detector.php#L62
     * @param $money
     * @return float
     */
    public static function getAmount($money)
    {
        $cleanString = preg_replace('/([^0-9\.,])/i', '', $money);
        $onlyNumbersString = preg_replace('/([^0-9])/i', '', $money);

        $separatorsCountToBeErased = strlen($cleanString) - strlen($onlyNumbersString) - 1;

        $stringWithCommaOrDot = preg_replace('/([,\.])/', '', $cleanString, $separatorsCountToBeErased);
        $removedThousendSeparator = preg_replace('/(\.|,)(?=[0-9]{3,}$)/', '',  $stringWithCommaOrDot);

        return (float) str_replace(',', '.', $removedThousendSeparator);
    }

    public static function cURL($url, $ref = null, $postData = null)
    {
        return (new SteamCommunity())->cURL($url, $ref, $postData);
    }
}
