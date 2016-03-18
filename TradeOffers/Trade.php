<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2015-12-28
 * Time: 2:22 PM
 */

namespace waylaidwanderer\SteamCommunity\TradeOffers;


use waylaidwanderer\SteamCommunity\Helper;
use waylaidwanderer\SteamCommunity\TradeOffers\Trade\TradeAsset;
use waylaidwanderer\SteamCommunity\TradeOffers\Trade\TradeUser;

class Trade
{
    private $newVersion = true;
    private $version = 1;
    private $me;
    private $them;
    private $steamCookies;
    private $sessionId;
    private $accountId;

    private $message = '';

    public function __construct($sessionId, $accountId)
    {
        $this->me = new TradeUser($this);
        $this->them = new TradeUser($this);
        $this->sessionId = $sessionId;
        $this->accountId = $accountId;
        $this->steamCookies = dirname(__FILE__) . '/../cookies';
    }

    public function getEncoded()
    {
        if ($this->newVersion){
            $newVersionString = "true";
        } else {
            $newVersionString = "false";
        }
        return urlencode('{"newversion":' . $newVersionString . ',"version":' . $this->version . ',"me":{' . $this->me->getEncoded() . '}"them":{' . $this->them->getEncoded() . '}}');
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
        $referer = 'https://steamcommunity.com/tradeoffer/new/?partner=' . $this->accountId;
        
        if ($token != ''){
            $referer .= "&token=" . $token;
        }
        
        $params = [
            'sessionid' => $this->sessionId,
            'serverid' => '1',
            'partner' => Helper::toCommunityID($this->accountId),
            'tradeoffermessage' => $this->message,
            'json_tradeoffer' => json_encode($this),
            'trade_offer_create_params' => (empty($token) ? "{}" : urlencode('{"trade_offer_access_token":"' . $token . '"}'))
        ];
        $response = $this->cURL($url, $referer, $params);
        $json = json_decode($response, true);
        if (is_null($json)) {
            return 0;
        } else {
            if (isset($json['tradeofferid'])) {
                return $json['tradeofferid'];
            } else {
                return 0;
            }
        }
    }

    public function cURL($url, $ref = NULL, $postData = NULL)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->steamCookies);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->steamCookies);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:27.0) Gecko/20100101 Firefox/27.0');
        
        if (isset($ref)) {
            curl_setopt($ch, CURLOPT_REFERER, $ref);
        }
        
        if (isset($postData)) {
            curl_setopt($ch, CURLOPT_POST, true);
            $postStr = "";
            
            foreach ($postData as $key => $value) {
                if ($postStr)
                    $postStr .= "&";
                $postStr .= $key . "=" . $value;
            }
            
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postStr);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type:application/x-www-form-urlencoded; charset=UTF-8","Content-length:" . strlen($postStr)));
        }
        
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;

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
