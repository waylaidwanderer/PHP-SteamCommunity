<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2015-12-28
 * Time: 4:18 AM
 */

namespace waylaidwanderer\SteamCommunity\TradeOffers\TradeOffer;


class Item
{
    private $appId;
    private $contextId;
    private $assetId;
    private $currencyId;
    private $classId;
    private $instanceId = 0;
    private $amount = 1;
    private $missing = false;

    /**
     * @return string
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * @param string $appId
     */
    public function setAppId($appId)
    {
        $this->appId = $appId;
    }

    /**
     * @return string
     */
    public function getContextId()
    {
        return $this->contextId;
    }

    /**
     * @param string $contextId
     */
    public function setContextId($contextId)
    {
        $this->contextId = $contextId;
    }

    /**
     * @return string
     */
    public function getAssetId()
    {
        return $this->assetId;
    }

    /**
     * @param string $assetId
     */
    public function setAssetId($assetId)
    {
        $this->assetId = $assetId;
    }

    /**
     * @return string
     */
    public function getCurrencyId()
    {
        return $this->currencyId;
    }

    /**
     * @param string $currencyId
     */
    public function setCurrencyId($currencyId)
    {
        $this->currencyId = $currencyId;
    }

    /**
     * @return string
     */
    public function getClassId()
    {
        return $this->classId;
    }

    /**
     * @param string $classId
     */
    public function setClassId($classId)
    {
        $this->classId = $classId;
    }

    /**
     * @return string
     */
    public function getInstanceId()
    {
        return $this->instanceId;
    }

    /**
     * @param string $instanceId
     */
    public function setInstanceId($instanceId)
    {
        $this->instanceId = $instanceId;
    }

    /**
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return boolean
     */
    public function isMissing()
    {
        return $this->missing;
    }

    /**
     * @param boolean $missing
     */
    public function setMissing($missing)
    {
        $this->missing = $missing;
    }
}