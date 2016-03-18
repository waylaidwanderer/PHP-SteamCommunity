<?php
namespace waylaidwanderer\SteamCommunity\TradeOffers\Trade;


use waylaidwanderer\SteamCommunity\TradeOffers\Trade;

/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2015-12-28
 * Time: 2:29 PM
 */
class TradeUser
{
    /** @var TradeAsset[] $assets */
    private $assets = [];
    private $assetsString = '';
    private $currency = [];
    private $ready = false;

    public function __construct()
    {
    }

    public function addItem(TradeAsset $asset)
    {
        $exists = false;
        foreach ($this->assets as $tradeAsset) {
            if ($tradeAsset->getAppId() == $asset->getAppId() &&
                $tradeAsset->getContextId() == $asset->getContextId() &&
                $tradeAsset->getAssetId() == $asset->getAssetId() &&
                $tradeAsset->getAmount() == $asset->getAmount()) {
                $exists = true;
            }
        }
        if ($exists) {
            return false;
        } else {
            $this->assetsString .= $asset->getEncoded();
            $this->assets[] = $asset;
            return true;
        }
    }

    public function getEncoded()
    {
        if ($this->ready){
            $ready = "true";
        } else {
            $ready = "false";
        }
        return '"assets":[' . substr($this->assetsString, 0, -1) . '],"currency":[],"ready":' . $ready;
    }
}
