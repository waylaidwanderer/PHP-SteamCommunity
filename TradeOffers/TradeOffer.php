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
    private $tradeOfferId;
    private $otherAccountId;
    private $message = '';
    private $expirationTime;
    private $tradeOfferState;
    private $itemsToGive;
    private $itemsToReceive;
    private $isOurOffer;
    private $timeCreated = 0;
    private $timeUpdated = 0;
    private $tradeId = 0;
    private $fromRealTimeTrade = false;
    private $escrowEndDate = 0;
    private $confirmationMethod = 0;

    public function __construct($json = [])
    {
        if (isset($json['tradeofferid'])) {
            $this->tradeOfferId = $json['tradeofferid'];
        }
        if (isset($json['accountid_other'])) {
            $this->otherAccountId = $json['accountid_other'];
        }
        if (isset($json['message'])) {
            $this->message = $json['message'];
        }
        if (isset($json['trade_offer_state'])) {
            $this->tradeOfferState = $json['trade_offer_state'];
        }
        if (isset($json['items_to_receive'])) {
            $this->itemsToReceive = [];
            if (is_array($json['items_to_receive'])) {
                foreach ($json['items_to_receive'] as $item) {
                    $this->itemsToReceive[] = new Item($item);
                }
            }
        }
        if (isset($json['items_to_give'])) {
            $this->itemsToGive = [];
            if (is_array($json['items_to_give'])) {
                foreach ($json['items_to_give'] as $item) {
                    $this->itemsToGive[] = new Item($item);
                }
            }
        }
        if (isset($json['is_our_offer'])) {
            $this->isOurOffer = $json['is_our_offer'];
        }
        if (isset($json['time_created'])) {
            $this->timeCreated = $json['time_created'];
        }
        if (isset($json['time_updated'])) {
            $this->timeUpdated = $json['time_updated'];
        }
        if (isset($json['tradeid'])) {
            $this->tradeId = $json['tradeid'];
        }
        if (isset($json['from_real_time_trade'])) {
            $this->fromRealTimeTrade = $json['from_real_time_trade'];
        }
        if (isset($json['escrow_end_date'])) {
            $this->escrowEndDate = $json['escrow_end_date'];
        }
        if (isset($json['confirmation_method'])) {
            $this->confirmationMethod = $json['confirmation_method'];
        }
    }

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
     * Limited to 128 chars. Will not be displayed if this limit is exceeded.
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

    /**
     * Get the trade id
     * @return int
     */
    public function getTradeId()
    {
        return $this->tradeId;
    }
}
