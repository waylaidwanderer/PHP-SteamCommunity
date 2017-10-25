<?php

namespace waylaidwanderer\SteamCommunity\Market;

class Listing
{
    private $appId;
    private $secondaryAppId = 0;
    private $marketName;
    private $itemType = 0;
    private $quantity;
    private $normalPrice;
    private $salePrice;
    private $color;
    private $image;

    /**
     * @return int
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * @param int $appId
     */
    public function setAppId($appId)
    {
        $this->appId = $appId;
    }

    /**
     * @return int
     */
    public function getSecondaryAppId()
    {
        return $this->secondaryAppId;
    }

    /**
     * @param int $secondaryAppId
     */
    public function setSecondaryAppId($secondaryAppId)
    {
        $this->secondaryAppId = $secondaryAppId;
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
    public function getItemType()
    {
        return $this->itemType;
    }

    /**
     * @param string $itemType
     */
    public function setItemType($itemType)
    {
        $this->itemType = $itemType;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    }

    /**
     * @return string
     */
    public function getNormalPrice()
    {
        return $this->normalPrice;
    }

    /**
     * @param string $normalPrice
     */
    public function setNormalPrice($normalPrice)
    {
        $this->normalPrice = $normalPrice;
    }

    /**
     * @return string
     */
    public function getSalePrice()
    {
        return $this->salePrice;
    }

    /**
     * @param string $salePrice
     */
    public function setSalePrice($salePrice)
    {
        $this->salePrice = $salePrice;
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
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param string $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    public function toArray()
    {
        return get_object_vars($this);
    }
}