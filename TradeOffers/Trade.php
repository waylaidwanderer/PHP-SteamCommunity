<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2015-12-28
 * Time: 2:22 PM
 */

namespace waylaidwanderer\SteamCommunity\TradeOffers;

use waylaidwanderer\SteamCommunity\Helper;
use waylaidwanderer\SteamCommunity\SteamCommunity;
use waylaidwanderer\SteamCommunity\Enum\LoginResult;
use waylaidwanderer\SteamCommunity\TradeOffers\Trade\TradeAsset;
use waylaidwanderer\SteamCommunity\TradeOffers\Trade\TradeUser;

class Trade implements \JsonSerializable
{
    private $newVersion = true;
    private $version = 2;
    private $me;
    private $them;
    private $accountId;

    private $error = '';
    private $message = '';

    public function __construct($accountId)
    {
        $this->me = new TradeUser($this);
        $this->them = new TradeUser($this);
        $this->accountId = $accountId;
    }

    public function jsonSerialize()
    {
        return [
            'newversion' => $this->newVersion,
            'version' => $this->version,
            'me' => $this->me,
            'them' => $this->them
        ];
    }

    public function addMyItem($appId, $contextId, $assetId, $amount = 1)
    {
        $asset = new TradeAsset($appId, $contextId, $assetId, $amount);
        return $this->addMyItemByAsset($asset);
    }

    public function addMyItemByAsset(TradeAsset $asset)
    {
        return $this->me->addItem($asset);
    }

    public function addOtherItem($appId, $contextId, $assetId, $amount = 1)
    {
        $asset = new TradeAsset($appId, $contextId, $assetId, $amount);
        return $this->addOtherItemByAsset($asset);
    }

    public function addOtherItemByAsset(TradeAsset $asset)
    {
        return $this->them->addItem($asset);
    }

    public function sendWithToken($token)
    {
        return $this->send($token);
    }

    public function send($token = '')
    {
        $url = 'https://steamcommunity.com/tradeoffer/new/send';
        $referer = 'https://steamcommunity.com/tradeoffer/new/' .
            '?partner=' . $this->accountId . ($token ? '&token=' . $token : '');

        $failures = 0; $tradeOfferId = false;
        while ($failures != 5) {
            sleep(1);

            $params = [
                'sessionid' => SteamCommunity::getInstance()->get('sessionId'),
                'serverid' => '1',
                'partner' => Helper::toCommunityID($this->accountId),
                'tradeoffermessage' => $this->message,
                'json_tradeoffer' => json_encode($this->jsonSerialize()),
                'trade_offer_create_params' => (empty($token) ? "{}" : json_encode([
                    'trade_offer_access_token' => $token
                ]))
            ];

            $response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($url, $referer, $params);

            $json = json_decode($response, true);
            if (is_null($json)) {
                $this->error = 'Empty response';
                SteamCommunity::getInstance()->reLogin(LoginResult::Need2FA);
            } else {
                if (isset($json['tradeofferid'])) {
                    $tradeOfferId = $json['tradeofferid'];
                    break;
                } else {
                    $this->error = $json['strError'];
                    break;
                }
            }

            $failures++;
        }

        return $tradeOfferId;
    }

    /**
     * @return int
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param int $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $this->error;
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
}
