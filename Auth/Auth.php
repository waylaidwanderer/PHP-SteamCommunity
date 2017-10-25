<?php

namespace waylaidwanderer\SteamCommunity\Auth;

use waylaidwanderer\SteamCommunity\SteamCommunity;

class Auth
{
    public function startSession()
    {
        $this->refreshSession();
    }

    public function getAuth()
    {
        return SteamCommunity::getInstance()->get('webAuth');
    }

    public function setAuth($auth)
    {
        file_put_contents($this->getAuthFilePath('web'), json_encode($auth));

        $webAuth = array(
            'auth' => $auth['auth'],
            'token' => $auth['token'],
            'tokenSecure' => $auth['token_secure'],
        );

        return SteamCommunity::getInstance()->set('webAuth', $webAuth);
    }

    public function refreshSession()
    {
        $response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL('https://steamcommunity.com/');

        $pattern = '/g_steamID = (.*);/';
        preg_match($pattern, $response, $matches);
        if (!isset($matches[1])) {
            sleep(3);
            return $this->refreshSession();
        }

        $steamId = str_replace('"', '', $matches[1]);
        if ($steamId == 'false') {
            $steamId = 0;
        }

        SteamCommunity::getInstance()->set('steamId', $steamId);

        $pattern = '/g_sessionID = (.*);/';
        preg_match($pattern, $response, $matches);
        if (!isset($matches[1])) {
            throw new SteamException('Unexpected response from Steam.');
        }

        $sessionId = str_replace('"', '', $matches[1]);
        SteamCommunity::getInstance()->getClassFromCache('WebApi')->setApiKey($sessionId);

        SteamCommunity::getInstance()->set('sessionId', $sessionId);
    }

    public function getAuthFilePath($type)
    {
        return SteamCommunity::getInstance()->getFilePath('authfiles', SteamCommunity::getInstance()->get('username') . '_' . $type, 'authfile');
    }

    public function createAuthFile($type)
    {
        SteamCommunity::getInstance()->createFile('authfiles', SteamCommunity::getInstance()->get('username') . '_' . $type, 'authfile');
    }

    public function getCookieFilePath($type)
    {
        return SteamCommunity::getInstance()->getFilePath('cookiefiles', SteamCommunity::getInstance()->get('username') . '_' . $type, 'cookiefile');
    }

    public function buildCookie($cookie)
    {
        $out = "";
        foreach ($cookie as $k => $c) {
            $out .= "{$k}={$c}; ";
        }

        return $out;
    }

    public function createCookieFile($type)
    {
        SteamCommunity::getInstance()->createFile('cookiefiles', SteamCommunity::getInstance()->get('username') . '_' . $type, 'cookiefile');
    }
}