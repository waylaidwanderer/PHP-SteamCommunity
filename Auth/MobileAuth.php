<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2016-02-08
 * Time: 12:53 PM
 */

namespace waylaidwanderer\SteamCommunity\Auth;

use waylaidwanderer\SteamCommunity\SteamCommunity;
use waylaidwanderer\SteamCommunity\WebApi;
use waylaidwanderer\SteamCommunity\Auth\SteamGuard;

class MobileAuth extends Auth
{
    public function startSession()
    {
        parent::startSession();

        try {
            SteamCommunity::getInstance()->getClassFromCache('Network')->cURL('https://steamcommunity.com/login?oauth_client_id=DE45CD61&oauth_scope=read_profile%20write_profile%20read_client%20write_client');
        } catch (\Exception $ex) {

        }
    }

    public function refreshSession()
    {
        if (SteamCommunity::getInstance()->get('mobile')) {
            parent::refreshSession();
        }

        $mobileAuth = SteamCommunity::getInstance()->get('mobileAuth');
        if (!isset($mobileAuth['oauth_token'])) {
            $authFile = @file_get_contents($this->getAuthFilePath('mobile'));
            if (!$authFile) {
                return false;
            }

            $mobileAuth = json_decode($authFile, true);
        }

        $response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL(SteamCommunity::getInstance()->getClassFromCache('WebApi')->getApiUrl() . 'IMobileAuthService/GetWGToken/v0001', null, ['access_token' => $mobileAuth['oauth_token']]);

        $json = json_decode($response, true);
        $mobileAuth['wgtoken'] = $json['response']['token'];
        $mobileAuth['wgtoken_secure'] = $json['response']['token_secure'];

        return $this->setAuth($mobileAuth);
    }

    public function getAuth()
    {
        if (!$this->steamCommunity->get('mobileAuth')) {
            return $this->refreshSession();
        }

        return SteamCommunity::getInstance()->get('mobileAuth');
    }

    public function setAuth($auth)
    {
        if (!is_array($auth)) {
            $auth = json_decode($auth, true);
        } else {
            file_put_contents($this->getAuthFilePath('mobile'), json_encode($auth));
        }

        $mobileAuth = array(
            'token' => $auth['wgtoken'],
            'tokenSecure' => $auth['wgtoken_secure'],
            'oauth_token' => $auth['oauth_token'],
        );

        return SteamCommunity::getInstance()->set('mobileAuth', $mobileAuth);
    }
}
