<?php
/**
 * Created by PhpStorm.
 * User: Joel
 * Date: 2016-02-08
 * Time: 12:53 PM
 */

namespace waylaidwanderer\SteamCommunity\MobileAuth;


use waylaidwanderer\SteamCommunity\SteamCommunity;

class MobileAuth
{
    private $steamCommunity;

    private $sharedSecret;
    private $identitySecret;
    private $deviceId;

    private $oauth;
    private $steamGuard;
    private $confirmations;

    public function __construct($mobileAuth, SteamCommunity $steamCommunity)
    {
        $this->sharedSecret = $mobileAuth['sharedSecret'];
        $this->identitySecret = $mobileAuth['identitySecret'];
        $this->deviceId = $mobileAuth['deviceId'];

        $this->steamGuard = new SteamGuard($mobileAuth['sharedSecret']);
        $this->confirmations = new Confirmations($this);

        $this->steamCommunity = $steamCommunity;
        $this->steamCommunity->setTwoFactorCode($this->steamGuard->generateSteamGuardCode());
        $this->steamCommunity->doLogin(true);
    }

    /**
     * Make a request to populate the cookiefile.
     */
    public function startMobileSession()
    {
        try {
            $this->steamCommunity->cURL('https://steamcommunity.com/login?oauth_client_id=DE45CD61&oauth_scope=read_profile%20write_profile%20read_client%20write_client');
        } catch (\Exception $ex) {

        }
    }

    /**
     * Refreshes the mobile session by retrieving new tokens.
     */
    public function refreshMobileSession()
    {
        try {
            $response = $this->steamCommunity->cURL('https://api.steampowered.com/IMobileAuthService/GetWGToken/v0001', null, ['access_token' => $this->oauth['oauth_token']]);
            $json = json_decode($response, true);
            $this->oauth['wgtoken'] = $json['response']['token'];
            $this->oauth['wgtoken_secure'] = $json['response']['token_secure'];
        } catch (\Exception $ex) {

        }
    }

    /**
     * @return SteamCommunity
     */
    public function steamCommunity()
    {
        return $this->steamCommunity;
    }

    /**
     * @return array
     */
    public function getOauth()
    {
        return $this->oauth;
    }

    /**
     * @param string $oauth
     */
    public function setOauth($oauth)
    {
        $this->oauth = json_decode($oauth, true);
        file_put_contents($this->steamCommunity->getAuthFilePath(), $oauth);
    }

    /**
     * @return string
     */
    public function getSharedSecret()
    {
        return $this->sharedSecret;
    }

    /**
     * @return string
     */
    public function getIdentitySecret()
    {
        return $this->identitySecret;
    }

    /**
     * @return string
     */
    public function getDeviceId()
    {
        return $this->deviceId;
    }

    /**
     * Returns an instance of the SteamGuard class.
     * @return SteamGuard
     */
    public function steamGuard()
    {
        return $this->steamGuard;
    }

    /**
     * Return an instance of the Confirmations class.
     * @return Confirmations
     */
    public function confirmations()
    {
        return $this->confirmations;
    }
}
