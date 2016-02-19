<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2015-12-28
 * Time: 7:09 PM
 */

namespace waylaidwanderer\SteamCommunity\Market;


use waylaidwanderer\SteamCommunity\SteamException;

class PriceOverview
{
    private $lowestPrice;
    private $volume;
    private $medianPrice;

    public function __construct($json)
    {
        if (!is_array($json)) {
            throw new SteamException("Json must be in array format.");
        }
        if (isset($json['success']) && $json['success']) {
            $lowestPrice = isset($json['lowest_price']) ? $json['lowest_price'] : 0;
            $this->lowestPrice = floatval(str_replace('$', '', $lowestPrice));
            $volume = isset($json['volume']) ? $json['volume'] : 0;
            $this->volume = intval(str_replace(',', '', $volume));
            $medianPrice = isset($json['median_price']) ? $json['median_price'] : 0;
            $this->medianPrice = floatval(str_replace('$', '', $medianPrice));
        } else {
            throw new SteamException("Market PriceOverview json must have valid data.");
        }
    }

    /**
     * @return float
     */
    public function getLowestPrice()
    {
        return $this->lowestPrice;
    }

    /**
     * @return int
     */
    public function getVolume()
    {
        return $this->volume;
    }

    /**
     * @return float
     */
    public function getMedianPrice()
    {
        return $this->medianPrice;
    }
}
