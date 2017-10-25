<?php

namespace waylaidwanderer\SteamCommunity\User;

use waylaidwanderer\SteamCommunity\Helper;
use waylaidwanderer\SteamCommunity\SteamCommunity;

class Chat
{
	private $token;
	private $uiMode;

    public function refreshToken()
    {
        $this->token = null;
        return $this->getToken();
    }

    private function getToken()
    {
        if ($this->token === null) {
            if ($auth = SteamCommunity::getInstance()->get('mobileAuth')) {
                $this->uiMode = 'mobile';
                $this->token = SteamCommunity::getInstance()->get('mobileAuth')['oauth_token'];
            } else {
                $this->uiMode = 'web';
                if (!$token = SteamCommunity::getInstance()->get('chatToken')) {
                    $this->scrapWebToken();
                }

                $this->token = SteamCommunity::getInstance()->get('chatToken');
            }
        }

        return $this->token;
    }

	private function scrapWebToken()
	{
		$response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL('https://steamcommunity.com/chat/', null, null, ['defaultNetwork' => true]);
        $pattern = '/WebAPI = new CWebAPI\((.*)\);/';
        preg_match($pattern, $response, $matches);

        if (!isset($matches[1])) {
			return false;
        }

		$auth = explode(',', str_replace(array('\'', "\"", ' '), '', $matches[1]));

		SteamCommunity::getInstance()->set('chatToken', $auth[2]);
	}

	public function login()
	{
		$params = [
			'key' => SteamCommunity::getInstance()->get('apiKey'),
			'access_token' => $this->getToken(),
			'umqid' => rand(1, 1000),
		];

        $url = SteamCommunity::getInstance()->getClassFromCache('WebApi')->getApiUrl() . 'ISteamWebUserPresenceOAuth/Logon/v1/?ui_mode=' . $this->uiMode;
        $response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($url, 'https://steamcommunity.com//chat/', $params, ['defaultNetwork' => true]);

		if (!$response = Helper::processJson($response)) {
			return false;
		}

		if (!isset($response['umqid'])) {
			return false;
		}

        $this->setChatInstance($response + array(
            'pollid' => 0,
            'loggedIn' => true
        ));

		return $this->getChatInstance();
	}

	public function logoff()
	{
		$params = [
			'key' => SteamCommunity::getInstance()->get('apiKey'),
			'access_token' => $this->getToken(),
			'umqid' => rand(1, 1000),
		];

		$url = SteamCommunity::getInstance()->getClassFromCache('WebApi')->getApiUrl() . 'ISteamWebUserPresenceOAuth/Logoff/v1/?_=' . time();
		$response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($url, 'https://steamcommunity.com//chat/', $params, ['defaultNetwork' => true]);

		if (!$response = Helper::processJson($response)) {
			return false;
		}

        $this->setChatInstance($response + array(
            'pollid' => 0,
            'loggedIn' => false
        ));

		return $response;
	}

    public function relogin()
    {
        $this->logoff();
        $this->refreshToken();
        $this->login();
    }

	public function poll($useAccountIds = 0)
	{
		if (!$chatInstance = $this->getChatInstance()) {
			return false;
		}

        $chatInstance['pollid']++;

		$params = [
			'key' => SteamCommunity::getInstance()->get('apiKey'),
			'access_token' => $this->getToken(),
			'umqid' => $chatInstance['umqid'],
			'message' => $chatInstance['message'],
			'pollid' => $chatInstance['pollid'],
			'sectimeout' => rand(15, 45),
			'secidletime' => rand(1, 10),
			'use_accountids' => $useAccountIds,
		];

		$url = SteamCommunity::getInstance()->getClassFromCache('WebApi')->getApiUrl() . 'ISteamWebUserPresenceOAuth/Poll/v0001/';
		$response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($url, 'https://steamcommunity.com//chat/', $params, ['defaultNetwork' => true]);

		if (!$response = Helper::processJson($response)) {
            $this->setChatInstance($chatInstance + array(
                'loggedIn' => false
            ));
			return false;
		}

        if ($response['error'] == 'OK') {
            $chatInstance['message'] = $response['messagelast'];
        }

        $this->setChatInstance($chatInstance);

		return $response;
	}

	public function sendMessage($message, $steamId, $type = 'saytext')
	{
		if (!$chatInstance = $this->getChatInstance()) {
			return false;
		}

		$params = [
			'key' => SteamCommunity::getInstance()->get('apiKey'),
			'access_token' => $this->getToken(),
			'umqid' => $chatInstance['umqid'],
			'text' => $message,
			'type' => $type,
			'steamid_dst' => Helper::toCommunityID($steamId)
		];

		$url = SteamCommunity::getInstance()->getClassFromCache('WebApi')->getApiUrl() . 'ISteamWebUserPresenceOAuth/Message/v1/';
		$response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($url, null, $params, ['defaultNetwork' => true]);
		if (!$response = Helper::processJson($response)) {
            $this->setChatInstance($chatInstance + array(
                'loggedIn' => false
            ));
			return false;
		}

        $this->setChatInstance($chatInstance);

		return $response;
	}

	public function getMessages($lastMessage = null)
	{
		if (!$chatInstance = $this->getChatInstance()) {
			return false;
		}

		$params = [
			'key' => SteamCommunity::getInstance()->get('apiKey'),
			'steamid' => Helper::toCommunityID(SteamCommunity::getInstance()->get('steamId')),
			'umqid' => $chatInstance['umqid'],
			'message' => $lastMessage
		];

		$url = SteamCommunity::getInstance()->getClassFromCache('WebApi')->getApiUrl() . 'ISteamWebUserPresenceOAuth/PollStatus/v1/';
		$response = SteamCommunity::getInstance()->getClassFromCache('Network')->cURL($url, null, $params, ['defaultNetwork' => true]);

		if (!$response = Helper::processJson($response)) {
            $this->setChatInstance($chatInstance + array(
                'loggedIn' => false
            ));
			return false;
		}

		return $response;
	}

	public function isLoggedIn()
	{
		if (!empty($this->getChatInstance())) {
            if ($this->getChatInstance()['loggedIn']) {
                return true;
            }
		}

		return false;
	}

    public function getChatInstance()
    {
        return SteamCommunity::getInstance()->get('chatInstance');
    }

    public function setChatInstance($chatInstance)
    {
        SteamCommunity::getInstance()->set('chatInstance', $chatInstance);
    }

    private function getChatTokenPath()
    {
        return SteamCommunity::getInstance()->getFilePath('authfiles', SteamCommunity::getInstance()->get('username') . '_chat_token', 'authfile');
    }
}