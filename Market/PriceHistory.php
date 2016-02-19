<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2015-12-28
 * Time: 7:08 PM
 */

namespace waylaidwanderer\SteamCommunity\Market;


use waylaidwanderer\SteamCommunity\Market\PriceHistory\PriceData;
use waylaidwanderer\SteamCommunity\SteamException;

class PriceHistory
{
    private $pricePrefix;
    private $priceSuffix;
    private $prices;

    public function __construct($json)
    {
        if (!is_array($json)) {
            throw new SteamException("Json must be in array format.");
        }
        if (isset($json['success']) && $json['success']) {
            $this->pricePrefix = $json['price_prefix'];
            $this->priceSuffix = $json['price_suffix'];
            $this->prices = [];
            if (is_array($json['prices']) && count($json['prices']) > 0) {
                foreach ($json['prices'] as $priceData) {
                    $this->prices[] = new PriceData($priceData);
                }
            }
        } else {
            throw new SteamException("Market PriceOverview json must have valid data.");
        }
    }

    public function getLastMedianPrice()
    {
        $pricesSize = count($this->getPrices());
        if ($pricesSize > 0) {
            return $this->getPrices()[$pricesSize - 1]->getMedianPrice();
        }
        return 0;
    }

    /**
     * @return string
     */
    public function getPricePrefix()
    {
        return $this->pricePrefix;
    }

    /**
     * @return string
     */
    public function getPriceSuffix()
    {
        return $this->priceSuffix;
    }

    /**
     * @return PriceData[]
     */
    public function getPrices()
    {
        return $this->prices;
    }
}
