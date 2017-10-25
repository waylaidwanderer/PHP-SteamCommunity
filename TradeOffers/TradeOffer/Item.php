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
    private $contextId = 2;
    private $assetId;
    private $currencyId;
    private $classId;
    private $name;
    private $marketName;
    private $type;
    private $iconUrl;
    private $iconUrlLarge;
    private $color;
    private $instanceId = 0;
    private $amount = 1;
    private $missing = false;

    public function __construct($json = [])
    {
        if (isset($json['appid'])) {
            $this->appId = $json['appid'];
        }
        if (isset($json['contextid'])) {
            $this->contextId = $json['contextid'];
        }
        if (isset($json['assetid'])) {
            $this->assetId = $json['assetid'];
        }
        if (isset($json['classid'])) {
            $this->classId = $json['classid'];
        }
        if (isset($json['instanceid'])) {
            $this->instanceId = $json['instanceid'];
        }
        if (isset($json['amount'])) {
            $this->amount = $json['amount'];
        }
        if (isset($json['missing'])) {
            $this->missing = $json['missing'];
        }
    }

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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getMarketName()
    {
        return $this->marketName;
    }

    /**
     * @param string $marketName
     */
    public function setMarketName($marketName)
    {
        $this->marketName = $marketName;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getIconUrl()
    {
        return $this->iconUrl;
    }

    /**
     * @param string $iconUrl
     */
    public function setIconUrl($iconUrl)
    {
        $this->iconUrl = $iconUrl;
    }

    /**
     * @return string
     */
    public function getIconUrlLarge()
    {
        return $this->iconUrlLarge;
    }

    /**
     * @param string $iconUrlLarge
     */
    public function setIconUrlLarge($iconUrlLarge)
    {
        $this->iconUrlLarge = $iconUrlLarge;
    }

    /**
     * @return string
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * @param string $color
     */
    public function setColor($color)
    {
        $this->color = $color;
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

    public function toArray()
    {
        return get_object_vars($this);
    }
}
