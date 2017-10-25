<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2015-12-28
 * Time: 2:42 AM
 */

namespace waylaidwanderer\SteamCommunity\TradeOffers;

use waylaidwanderer\SteamCommunity\SteamCommunity;
use waylaidwanderer\SteamCommunity\Helper;
use waylaidwanderer\SteamCommunity\TradeOffers\TradeOffer\Item;

class TradeOffer
{
    private $tradeOfferId;
    private $myAccountId;
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
		if (empty($json)) {
			return null;
		}

        if (isset($json['tradeofferid'])) {
            $this->tradeOfferId = $json['tradeofferid'];
        }
		$this->myAccountId = SteamCommunity::getInstance()->get('steamId');
        if (isset($json['accountid_other'])) {
            $this->otherAccountId = Helper::toCommunityID($json['accountid_other']);
        }
        if (isset($json['message'])) {
            $this->message = $json['message'];
        }
        if (isset($json['trade_offer_state'])) {
            $this->tradeOfferState = $json['trade_offer_state'];
        }
        if (isset($json['items_to_receive'])) {
			$this->itemsToReceive = array();
            if (is_array($json['items_to_receive'])) {
                foreach ($json['items_to_receive'] as $item) {
                    $this->itemsToReceive[] = new Item($item);
                }

				$this->setItemsToReceive($this->otherAccountId);
            }
        }
        if (isset($json['items_to_give'])) {
			$this->itemsToGive = array();
            if (is_array($json['items_to_give'])) {
                foreach ($json['items_to_give'] as $item) {
                    $this->itemsToGive[] = new Item($item);
                }

				$this->setItemsToGive($this->myAccountId);
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

	private function getItemDetailsFromHover($steamId, $items)
	{
		if (empty($items)) {
			return array();
		}

		$steamId = Helper::toCommunityID($steamId);
		$pattern = '/\{(?:[^{}]|(?R))*\}/x';
		$linePattern = '/^.*\BuildHover\b.*$/m';
		$url = 'http://steamcommunity.com/economy/itemclasshover/';

		foreach ($items as &$item) {
			$itemUrl = $url . $item->getAppId() . '/';
			if ($item->getInstanceId()) {
				$itemUrl .= $item->getClassId() . '/' . $item->getInstanceId();
			} else {
                $itemUrl .= $item->getClassId();
			}

			$itemUrl .= '?content_only=1&omit_owner=1&l=english&o=' . $steamId;

			$response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($itemUrl);
            
			preg_match($linePattern, $response, $lineMatch);
			if (!$lineMatch) {
				continue;
			}

			$lineMatch = reset($lineMatch);
			preg_match($pattern, $lineMatch, $jsonMatch);
			if (!isset($jsonMatch[0])) {
				continue;
			}

			$hoverItem = Helper::processJson($jsonMatch[0]);

			if (array_key_exists('id', $hoverItem)) {
				$item->setAssetId($hoverItem['id']);
			}

            if (!$item->getInstanceId() && !empty($hoverItem['instanceid'])) {
                $item->setInstanceId($hoverItem['instanceid']);
            }

            if (!$item->getContextId() && !empty($hoverItem['contextid'])) {
                $item->setContextId($hoverItem['contextid']);
            }
            
			$item->setName($hoverItem['name']);
			$item->setMarketName($hoverItem['market_hash_name']);
			$item->setType($hoverItem['type']);
			$item->setIconUrl($hoverItem['icon_url']);
            if (array_key_exists('icon_url_large', $hoverItem)) {
                $item->setIconUrlLarge($hoverItem['icon_url_large']);
            }
			$item->setColor($hoverItem['name_color']);
		}

		return $items;
	}

	private function getItemDetailsFromInventory($steamId, $items)
	{
		if (empty($items)) {
			return array();
		}

		$steamId = Helper::toCommunityID($steamId);

		$inventoryCache = array();
		$inventory = SteamCommunity::getInstance()->getClassFromCache('User\Inventory');

		foreach ($items as &$item) {
			$identifier = $steamId . '_' . $item->getAppId() . '_' . $item->getContextId();
			if (!array_key_exists($identifier, $inventoryCache)) {
				$inventoryCache[$identifier] = $inventory->loadInventory($steamId, $item->getAppId(), $item->getContextId());
			}

			if (array_key_exists($item->getAssetId(), $inventoryCache[$identifier])) {
				$invItem = $inventoryCache[$identifier][$item->getAssetId()];
				if (!empty($invItem['name'])) { $item->setName($invItem['name']); }
				if (!empty($invItem['market_name'])) { $item->setMarketName($invItem['market_name']); }
				if (!empty($invItem['type'])) { $item->setType($invItem['type']); }
				if (!empty($invItem['icon_url'])) { $item->setIconUrl($invItem['icon_url']); }
				if (!empty($invItem['icon_url_large'])) { $item->setIconUrlLarge($invItem['icon_url_large']); }
				if (!empty($invItem['name_color'])) { $item->setColor($invItem['name_color']); }

			}
		}

		return $items;
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
    public function getMyAccountId()
    {
        return $this->myAccountId;
    }

    /**
     * @param string $myAccountId
     */
    public function setMyAccountId($myAccountId)
    {
        $this->myAccountId = $myAccountId;
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

    public function setItemsToGive($steamId, $itemsToGive = null, $method = 'hover')
    {
		if ($itemsToGive === null) {
			$itemsToGive = $this->itemsToGive;
		}

		if ($method == 'hover') {
			$this->itemsToGive = $this->getItemDetailsFromHover($steamId, $itemsToGive);
		}

		if ($method == 'inventory') {
			$this->itemsToGive = $this->getItemDetailsFromInventory($steamId, $itemsToGive);
		}
    }

    /**
     * @return Item[]
     */
    public function getItemsToReceive()
    {
        return $this->itemsToReceive;
    }

    public function setItemsToReceive($steamId, $itemsToReceive = null, $method = 'hover')
    {
		if ($itemsToReceive === null) {
			$itemsToReceive = $this->itemsToReceive;
		}

		if ($method == 'hover') {
			$this->itemsToReceive = $this->getItemDetailsFromHover($steamId, $itemsToReceive);
		}

		if ($method == 'inventory') {
			$this->itemsToReceive = $this->getItemDetailsFromInventory($steamId, $itemsToReceive);
		}
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
