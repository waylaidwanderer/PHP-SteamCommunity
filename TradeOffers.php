<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2015-12-27
 * Time: 4:41 PM
 */

namespace waylaidwanderer\SteamCommunity;


class TradeOffers
{
    protected $steamCommunity;

    public function __construct(SteamCommunity $steamCommunity)
    {
        $this->steamCommunity = $steamCommunity;
    }
}