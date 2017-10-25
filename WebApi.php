<?php

namespace waylaidwanderer\SteamCommunity;

use waylaidwanderer\SteamCommunity\SteamCommunity;
use waylaidwanderer\SteamCommunity\SteamException;

class WebApi
{
    protected $apiUrl = 'https://api.steampowered.com/';

    public function setApiKey($sessionId, $recursionLevel = 1)
    {
        if (!SteamCommunity::getInstance()->get('apiKey')) {
            $url = 'https://steamcommunity.com/dev/apikey';
            $response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($url);
            if (preg_match('/<h2>Access Denied<\/h2>/', $response)) {
                $apiKey = '';
            } else if (preg_match('/<p>Key: (.*)<\/p>/', $response, $matches)) {
                $apiKey = $matches[1];
            } else if ($recursionLevel < 3 && !empty(SteamCommunity::getInstance()->get('apiKeyDomain'))) {
                $registerUrl = 'https://steamcommunity.com/dev/registerkey';
                $params = [
                    'domain' => SteamCommunity::getInstance()->get('apiKeyDomain'),
                    'agreeToTerms' => 'agreed',
                    'sessionid' => $this->sessionId,
                    'Submit' => 'Register'
                ];
                SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($registerUrl, $url, $params);
                $recursionLevel++;
                $this->setApiKey($sessionId, $recursionLevel);
            } else {
                $apiKey = '';
            }

            SteamCommunity::getInstance()->set('apiKey', $apiKey);
        }
    }

    /**
     * In most cases, you don't need to call this since an API key is registered automatically upon logging in as long as you have set the domain first.
     * @param string $domain
     * @return string
     * @throws SteamException Thrown when Steam gives an unexpected response (e.g. Steam is down/having issues)
     */
    public function registerApiKey($domain = '')
    {
        $this->apiKeyDomain = $domain;
        $this->setApiKey();

        if (empty(SteamCommunity::getInstance()->get('apiKey'))) {
            throw new SteamException('Could not register API key.');
        }

        return SteamCommunity::get('apiKey');
    }

    public function getApiUrl()
    {
        return $this->apiUrl;
    }
}