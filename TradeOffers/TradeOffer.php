<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2015-12-28
 * Time: 2:42 AM
 */

namespace waylaidwanderer\SteamCommunity\TradeOffers;


use waylaidwanderer\SteamCommunity\TradeOffers\TradeOffer\Item;

class TradeOffer
{
    protected $tradeOfferId;
    protected $otherAccountId;
    protected $message = '';
    protected $expirationTime;
    protected $tradeOfferState;
    protected $itemsToGive;
    protected $itemsToReceive;
    protected $isOurOffer;

    // TODO: figure out how to get these
    protected $timeCreated = 0;
    protected $timeUpdated = 0;
    protected $fromRealTimeTrade = false;
    protected $escrowEndDate = 0;
    protected $confirmationMethod = 0;

    /**
     * @return string
     */
    public function getTradeOfferId()
    {
        return $this->tradeOfferId;
    }

    /**
     * @param string $tradeOfferId
     */
    public function setTradeOfferId($tradeOfferId)
    {
        $this->tradeOfferId = $tradeOfferId;
    }

    /**
     * @return string
     */
    public function getOtherAccountId()
    {
        return $this->otherAccountId;
    }

    /**
     * @param string $otherAccountId
     */
    public function setOtherAccountId($otherAccountId)
    {
        $this->otherAccountId = $otherAccountId;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return int
     */
    public function getExpirationTime()
    {
        return $this->expirationTime;
    }

    /**
     * @param int $expirationTime
     */
    public function setExpirationTime($expirationTime)
    {
        $this->expirationTime = $expirationTime;
    }

    /**
     * @return int
     */
    public function getTradeOfferState()
    {
        return $this->tradeOfferState;
    }

    /**
     * @param int $tradeOfferState
     */
    public function setTradeOfferState($tradeOfferState)
    {
        $this->tradeOfferState = $tradeOfferState;
    }

    /**
     * @return Item[]
     */
    public function getItemsToGive()
    {
        return $this->itemsToGive;
    }

    /**
     * @param Item[] $itemsToGive
     */
    public function setItemsToGive($itemsToGive)
    {
        $this->itemsToGive = $itemsToGive;
    }

    /**
     * @return Item[]
     */
    public function getItemsToReceive()
    {
        return $this->itemsToReceive;
    }

    /**
     * @param Item[] $itemsToReceive
     */
    public function setItemsToReceive($itemsToReceive)
    {
        $this->itemsToReceive = $itemsToReceive;
    }

    /**
     * @return boolean
     */
    public function isOurOffer()
    {
        return $this->isOurOffer;
    }

    /**
     * @param boolean $isOurOffer
     */
    public function setIsOurOffer($isOurOffer)
    {
        $this->isOurOffer = $isOurOffer;
    }

    /**
     * @return int
     */
    public function getTimeCreated()
    {
        return $this->timeCreated;
    }

    /**
     * @param int $timeCreated
     */
    public function setTimeCreated($timeCreated)
    {
        $this->timeCreated = $timeCreated;
    }

    /**
     * @return int
     */
    public function getTimeUpdated()
    {
        return $this->timeUpdated;
    }

    /**
     * @param int $timeUpdated
     */
    public function setTimeUpdated($timeUpdated)
    {
        $this->timeUpdated = $timeUpdated;
    }

    /**
     * @return boolean
     */
    public function isFromRealTimeTrade()
    {
        return $this->fromRealTimeTrade;
    }

    /**
     * @param boolean $fromRealTimeTrade
     */
    public function setFromRealTimeTrade($fromRealTimeTrade)
    {
        $this->fromRealTimeTrade = $fromRealTimeTrade;
    }

    /**
     * @return int
     */
    public function getEscrowEndDate()
    {
        return $this->escrowEndDate;
    }

    /**
     * @param int $escrowEndDate
     */
    public function setEscrowEndDate($escrowEndDate)
    {
        $this->escrowEndDate = $escrowEndDate;
    }

    /**
     * @return int
     */
    public function getConfirmationMethod()
    {
        return $this->confirmationMethod;
    }

    /**
     * @param int $confirmationMethod
     */
    public function setConfirmationMethod($confirmationMethod)
    {
        $this->confirmationMethod = $confirmationMethod;
    }
}