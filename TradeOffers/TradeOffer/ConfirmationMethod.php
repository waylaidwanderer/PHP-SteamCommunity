<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2015-12-28
 * Time: 10:31 AM
 */

namespace waylaidwanderer\SteamCommunity\TradeOffers\TradeOffer;


abstract class ConfirmationMethod
{
    const Invalid = 0;
    const Email = 1;
    const MobileApp = 2;
}
