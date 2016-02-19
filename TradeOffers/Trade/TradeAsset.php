<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2015-12-28
 * Time: 2:34 PM
 */

namespace waylaidwanderer\SteamCommunity\TradeOffers\Trade;


class TradeAsset implements \JsonSerializable
{
    private $appId;
    private $contextId;
    private $assetId;
    private $amount;

    public function __construct($appId, $contextId, $assetId, $amount)
    {
        $this->appId = $appId;
        $this->contextId = $contextId;
        $this->assetId = $assetId;
        $this->amount = $amount;
    }

    public function jsonSerialize()
    {
        return [
            'appid' => (int)$this->appId,
            'contextid' => $this->contextId,
            'assetid' => $this->assetId,
            'amount' => (int)$this->amount
        ];
    }

    /**
     * @return int
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * @return string
     */
    public function getContextId()
    {
        return $this->contextId;
    }

    /**
     * @return string
     */
    public function getAssetId()
    {
        return $this->assetId;
    }

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }
}
