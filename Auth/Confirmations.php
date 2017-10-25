<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2016-02-08
 * Time: 1:01 PM
 */

namespace waylaidwanderer\SteamCommunity\Auth;

use waylaidwanderer\SteamCommunity\SteamCommunity;
use waylaidwanderer\SteamCommunity\Auth\TimeAligner;
use waylaidwanderer\SteamCommunity\Auth\Confirmations\Confirmation;
use TrafficCophp\ByteBuffer\Buffer;

class Confirmations
{
    /**
     * Fetch list of confirmations. May need to retry more than once because of Steam occasionally not showing any confirmations.
     * @return Confirmation[]
     * @throws WgTokenInvalidException Thrown when session is invalid.
     */
    public function fetchConfirmations()
    {
        $confirmations = [];

        $failures = 0;
        while ($failures != 5) {
            $response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($this->generateConfirmationUrl());
            if (empty($response)) {
                SteamCommunity::getInstance()->reLogin(false, true); sleep(3); continue;
            }

            if (strpos($response, '<div>Nothing to confirm</div>') !== false) {
                break;
            }

            if (!empty($response) && strpos($response, 'Invalid authenticator') === false) {
                libxml_use_internal_errors(true);

                $doc = new \DOMDocument();
                $doc->loadHTML($response);
                $xpath = new \DOMXPath($doc);

                $confDescRegex = '/((Confirm|Trade with|Sell -).*)/';

                $foundConfirmations = $xpath->query('//div[@class="mobileconf_list_entry"]');
                foreach ($foundConfirmations as $confirmation) {
                    $confId = $confirmation->getAttribute('data-confid');
                    $confKey = $confirmation->getAttribute('data-key');
                    $confOfferId = $confirmation->getAttribute('data-creator');
                    $confDesc = false;

                    $confirmations[] = new Confirmation($confId, $confKey, $confOfferId, $confDesc);
                }

                break;
            }

            $failures++;

            SteamCommunity::getInstance()->getClassFromCache('Auth\MobileAuth')->refreshSession();
            sleep(3);
        }

        return $confirmations;
    }

    public function generateConfirmationUrl($tag = 'conf')
    {
        return 'https://steamcommunity.com/mobileconf/conf?' . $this->generateConfirmationQueryParams($tag);
    }

    public function generateConfirmationQueryParams($tag)
    {
        $time = TimeAligner::GetSteamTime();
        return 'p=' . SteamCommunity::getInstance()->get('deviceId') . '&a=' . SteamCommunity::getInstance()->get('steamId') . '&k=' . $this->_generateConfirmationHashForTime($time, $tag) . '&t=' . $time . '&m=android&tag=' . $tag;
    }

    private function _generateConfirmationHashForTime($time, $tag)
    {
        $identitySecret = base64_decode(SteamCommunity::getInstance()->get('identitySecret'));

        $dataLen = 8;
        if ($tag) {
            if (strlen($tag) > 32) {
                $dataLen += 32;
            } else {
                $dataLen += strlen($tag);
            }
        }

        $buffer = new Buffer($dataLen);
        $buffer->writeInt32BE(0, 0); // This will stop working in 2038!
        $buffer->writeInt32BE($time, 4);

        if ($tag) {
            $buffer->write($tag, 8);
        }

        $code = hash_hmac("sha1", $buffer, $identitySecret, true);
        return base64_encode($code);
    }

    /**
     * Get the trade offer ID of a confirmation. May need to retry more than once due to Steam occasionally failing to load the trade page.
     * @param Confirmation $confirmation
     * @return string
     */
    public function getConfirmationTradeOfferId(Confirmation $confirmation)
    {
        $tradeOfferId = false;
        $failures = 0;

        while ($failures != 5) {
            $response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL('https://steamcommunity.com/mobileconf/details/' . $confirmation->getConfirmationId() . '?' . $this->generateConfirmationQueryParams('details'));
            if (!empty($response)) {
                $json = json_decode($response, true);
                if (isset($json['success']) && $json['success']) {
                    $html = $json['html'];
                    if (preg_match('/<div class="tradeoffer" id="tradeofferid_(\d+)" >/', $html, $matches)) {
                        $tradeOfferId = $matches[1];
                        break;
                    }
                }
            }

            $failures++;

            SteamCommunity::getInstance()->getClassFromCache('Auth\MobileAuth')->refreshSession();
            sleep(3);
        }

        return $tradeOfferId;
    }

    /**
     * Accept a confirmation.
     * @param Confirmation $confirmation
     * @return bool
     */
    public function acceptConfirmation(Confirmation $confirmation)
    {
        return $this->_sendConfirmationAjax($confirmation, 'allow');
    }

    /**
     * Accept multiple confirmations.
     * @param Array $confirmations
     * @return bool
     */
    public function acceptMultipleConfirmations(array $confirmations)
    {
        return $this->_sendMultiConfirmationAjax($confirmations, 'allow');
    }

    /**
     * Cancel a confirmation.
     * @param Confirmation $confirmation
     * @return bool
     */
    public function cancelConfirmation(Confirmation $confirmation)
    {
        return $this->_sendConfirmationAjax($confirmation, 'cancel');
    }

    /**
     * Cancel multiple confirmations.
     * @param Array $confirmations
     * @return bool
     */
    public function cancelMultipleConfirmations(array $confirmations)
    {
        return $this->_sendMultiConfirmationAjax($confirmations, 'cancel');
    }

    private function _sendMultiConfirmationAjax(array $confirmations, $op)
    {
        $url = 'https://steamcommunity.com/mobileconf/ajaxop?op=' . $op . '&' . $this->generateConfirmationQueryParams($op);

        foreach ($confirmations as $confirmation) {
            if ($confirmation instanceof Confirmation) {
                $url .= '&cid[]=' . $confirmation->getConfirmationId() . '&ck[]=' . $confirmation->getConfirmationKey();
            }
        }

        $success = false;
        $failures = 0;
        while ($failures != 5) {
            $response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($url, null, true);

            if (!empty($response)) {
                $json = json_decode($response, true);
                if (isset($json['success'])) {
                    $success = true;
                    break;
                }
            }

            $failures++;
            SteamCommunity::getInstance()->getClassFromCache('Auth\MobileAuth')->refreshSession();
            sleep(3);
        }

        return $success;
    }

    private function _sendConfirmationAjax(Confirmation $confirmation, $op)
    {
        $success = false;
        $failures = 0;
        while ($failures != 5) {
            $response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL('https://steamcommunity.com/mobileconf/ajaxop?op=' . $op . '&' . $this->generateConfirmationQueryParams($op) . '&cid=' . $confirmation->getConfirmationId() . '&ck=' . $confirmation->getConfirmationKey());
            if (!empty($response)) {
                $json = json_decode($response, true);
                $success = isset($json['success']) && $json['success'];
            }

            if ($success) {
                break;
            }

            $failures++;
            if (!$success) {
                SteamCommunity::getInstance()->getClassFromCache('Auth\MobileAuth')->refreshSession();
                sleep(3);
            }
        }

        return $success;
    }
}
