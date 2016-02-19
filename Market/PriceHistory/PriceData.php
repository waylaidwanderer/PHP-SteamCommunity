<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2015-12-28
 * Time: 7:06 PM
 */

namespace waylaidwanderer\SteamCommunity\Market\PriceHistory;


class PriceData
{
    private $dateAndTime;
    private $medianPrice;
    private $volume;

    public function __construct($priceArray)
    {
        if (count($priceArray) == 3) {
            $this->dateAndTime = $priceArray[0];
            $this->medianPrice = $priceArray[1];
            $this->volume = (int)$priceArray[2];
        }
    }

    /**
     * @return string
     */
    public function getDateAndTime()
    {
        return $this->dateAndTime;
    }

    /**
     * @return float
     */
    public function getMedianPrice()
    {
        return $this->medianPrice;
    }

    /**
     * @return int
     */
    public function getVolume()
    {
        return $this->volume;
    }
}
